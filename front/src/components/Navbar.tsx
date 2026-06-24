import React, { useEffect, useRef, useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAppDispatch, useAppSelector } from '../store/hooks';
import { logout } from '../features/auth/AuthSlice';
import { selectAuthUser } from '../features/auth/AuthSelectors';
import { fetchConversationsForUser } from '../features/conversation/ConversationSlice';
import { selectUnreadCount } from '../features/conversation/ConversationSelectors';
import { fetchOwnsAccommodation } from '../features/accommodationManagement/AccommodationManagementSlice';
import { selectHasAccommodation } from '../features/accommodationManagement/AccommodationManagementSelectors';
import VerificationBadge from '../features/userProfile/components/VerificationBadge';
import { VerificationStatus } from '../features/userProfile/UserProfileTypes';
import { Avatar } from './ui';

const Navbar: React.FC = () => {
  const { t, i18n } = useTranslation();
  const dispatch = useAppDispatch();
  const navigate = useNavigate();
  const user = useAppSelector(selectAuthUser);
  const location = useLocation();
  const unreadCount = useAppSelector(selectUnreadCount);
  const hasAccommodation = useAppSelector(selectHasAccommodation);
  // A traveler with no listing can still land on /admin (e.g. the default
  // post-login redirect). Treat confirmed non-owners as travelers so menu links
  // (conversations, settings) point to their /account space, not the host backoffice.
  const isHostMode = location.pathname.startsWith('/admin') && hasAccommodation !== false;
  const [menuOpen, setMenuOpen] = useState(false);
  const menuRef = useRef<HTMLDivElement | null>(null);

  // Load the user's conversations so the notification badge reflects unread messages,
  // and resolve whether they own a listing (gates the host/traveler mode switch).
  useEffect(() => {
    if (user) {
      dispatch(fetchConversationsForUser());
      dispatch(fetchOwnsAccommodation());
    }
  }, [dispatch, user]);

  useEffect(() => {
    const handleClickOutside = (e: MouseEvent) => {
      if (menuRef.current && !menuRef.current.contains(e.target as Node)) {
        setMenuOpen(false);
      }
    };
    if (menuOpen) document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, [menuOpen]);

  const handleLogout = () => {
    setMenuOpen(false);
    dispatch(logout());
    navigate('/');
  };

  const displayName = user?.firstName || user?.email.split('@')[0] || '';

  const changeLanguage = (lng: string) => {
    i18n.changeLanguage(lng);
    localStorage.setItem('lang', lng);
  };

  return (
    <header className="fixed top-0 left-0 right-0 z-50 bg-white/90 backdrop-blur-sm border-b">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16">
        {/* Logo + Nav links */}
        <div className="flex items-center gap-6">
          <Link to="/" className="flex items-center" aria-label="ZouriteBnb">
            <img
              src="/logo.png"
              alt="ZouriteBnb"
              className="h-14 w-auto object-contain"
              loading="eager"
            />
          </Link>
          <nav className="hidden md:flex items-center gap-6 text-sm font-medium">
            <Link to="/solidarity-projects" className="hover:text-primary-600 transition-colors">
              {t('projects.title')}
            </Link>
          </nav>
        </div>

        {/* Actions */}
        <div className="flex items-center gap-4">
          {/* Locale picker */}
          <div className="hidden sm:flex items-center bg-gray-100 rounded-lg p-0.5 text-xs font-medium">
            <button
              onClick={() => changeLanguage('fr')}
              className={`px-2.5 py-1 rounded-md transition-all ${i18n.language === 'fr' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'}`}
            >
              FR
            </button>
            <button
              onClick={() => changeLanguage('en')}
              className={`px-2.5 py-1 rounded-md transition-all ${i18n.language === 'en' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'}`}
            >
              EN
            </button>
          </div>

          {/* Create button (host mode only) */}
          {isHostMode && (
            <Link to="/create">
              <button className="justify-center whitespace-nowrap text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 border border-gray-200 bg-white hover:bg-gray-50 h-9 rounded-md px-3 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-4 h-4">
                  <path d="M5 12h14" />
                  <path d="M12 5v14" />
                </svg>
                <span className="hidden sm:inline">{t('navbar.createAccommodation')}</span>
              </button>
            </Link>
          )}

          {/* Mode switch (host ↔ traveler): only for hosts who own at least one listing.
              Hidden while ownership is unresolved (null) to avoid showing it to travelers. */}
          {user && hasAccommodation && (
            <Link
              to={isHostMode ? '/account' : '/admin'}
              className="hidden sm:inline-flex items-center gap-2 h-9 rounded-full px-4 text-sm font-semibold border transition-colors bg-primary-50 text-primary-700 border-primary-200 hover:bg-primary-100"
              title={isHostMode ? t('navbar.switchToTraveler') as string : t('navbar.switchToHost') as string}
            >
              {isHostMode ? (
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                  <circle cx="12" cy="12" r="10" />
                  <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20" />
                  <path d="M2 12h20" />
                </svg>
              ) : (
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                  <path d="M3 9.5 12 3l9 6.5V21a1 1 0 0 1-1 1h-5v-7h-6v7H4a1 1 0 0 1-1-1z" />
                </svg>
              )}
              <span>
                {isHostMode ? t('navbar.switchToTraveler') : t('navbar.switchToHost')}
              </span>
            </Link>
          )}

          {/* Auth */}
          {user ? (
            <div className="hidden sm:block relative" ref={menuRef}>
              <button
                onClick={() => setMenuOpen((v) => !v)}
                className="relative flex items-center gap-2 border border-gray-200 bg-white hover:bg-gray-50 h-9 rounded-md pl-1.5 pr-3 text-sm font-medium"
              >
                <Avatar
                  avatarUrl={user.avatarUrl}
                  name={displayName}
                  sizeClassName="w-6 h-6"
                  textClassName="text-xs"
                />
                <span className="max-w-[120px] truncate">{displayName}</span>
                {unreadCount > 0 && (
                  <span className="min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-semibold flex items-center justify-center">
                    {unreadCount > 9 ? '9+' : unreadCount}
                  </span>
                )}
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={`transition-transform ${menuOpen ? 'rotate-180' : ''}`}>
                  <path d="m6 9 6 6 6-6" />
                </svg>
              </button>
              {menuOpen && (
                <div className="absolute right-0 mt-2 w-[350px] bg-white rounded-xl border border-gray-200 shadow-lg overflow-hidden">
                  <div className="px-5 py-3 border-b border-gray-100 flex items-center gap-3">
                    <Avatar avatarUrl={user.avatarUrl} name={displayName} sizeClassName="w-10 h-10" />
                    <div className="min-w-0">
                      <p className="text-sm font-semibold text-gray-900 truncate">{displayName}</p>
                      <p className="text-xs text-gray-500 truncate">{user.email}</p>
                    </div>
                  </div>
                  <nav className="py-1 text-sm">
                    {/* Listing-gated host pages: hidden in traveler mode and for hosts with no listing */}
                    {isHostMode && hasAccommodation && (
                      <>
                        <Link
                          to="/admin/accommodations"
                          onClick={() => setMenuOpen(false)}
                          className="flex items-center gap-3 px-5 py-3 text-gray-700 hover:bg-gray-50"
                        >
                          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                            <path d="M3 9.5 12 3l9 6.5V21a1 1 0 0 1-1 1h-5v-7h-6v7H4a1 1 0 0 1-1-1z" />
                          </svg>
                          {t('navbar.menu.myAccommodations')}
                        </Link>
                        <Link
                          to="/admin/calendar"
                          onClick={() => setMenuOpen(false)}
                          className="flex items-center gap-3 px-5 py-3 text-gray-700 hover:bg-gray-50"
                        >
                          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" /><line x1="16" y1="2" x2="16" y2="6" /><line x1="8" y1="2" x2="8" y2="6" /><line x1="3" y1="10" x2="21" y2="10" />
                          </svg>
                          {t('navbar.menu.calendar')}
                        </Link>
                      </>
                    )}
                    <Link
                      to={isHostMode ? '/admin/conversations' : '/account/conversations'}
                      onClick={() => setMenuOpen(false)}
                      className="flex items-center gap-3 px-5 py-3 text-gray-700 hover:bg-gray-50"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                      </svg>
                      {t('navbar.menu.conversations')}
                    </Link>
                    <Link
                      to={isHostMode ? '/admin/team' : '/account/settings'}
                      onClick={() => setMenuOpen(false)}
                      className="flex items-center gap-3 px-5 py-3 text-gray-700 hover:bg-gray-50"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                        <circle cx="12" cy="12" r="3" /><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
                      </svg>
                      {t('navbar.menu.settings')}
                    </Link>
                    <Link
                      to="/account/verification"
                      onClick={() => setMenuOpen(false)}
                      className="flex items-center justify-between gap-3 px-5 py-3 text-gray-700 hover:bg-gray-50"
                    >
                      <span className="flex items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                          <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
                          <path d="m9 12 2 2 4-4" />
                        </svg>
                        {t('navbar.menu.verification')}
                      </span>
                      <VerificationBadge
                        status={(user.verificationStatus as VerificationStatus) || 'not_started'}
                        compact
                      />
                    </Link>
                  </nav>
                  <div className="border-t border-gray-100 py-1">
                    <button
                      onClick={handleLogout}
                      className="flex items-center gap-3 w-full text-left px-5 py-3 text-sm text-red-600 hover:bg-red-50"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" /><polyline points="16 17 21 12 16 7" /><line x1="21" x2="9" y1="12" y2="12" />
                      </svg>
                      {t('auth.logout')}
                    </button>
                  </div>
                </div>
              )}
            </div>
          ) : (
            <div className="hidden sm:flex items-center gap-2">
              <Link to="/login" className="border border-gray-200 bg-white hover:bg-gray-50 h-9 rounded-md px-3 text-sm font-medium inline-flex items-center">
                {t('auth.login')}
              </Link>
              <Link to="/register" className="text-white bg-primary-600 hover:bg-primary-700 h-9 rounded-md px-3 text-sm font-medium inline-flex items-center">
                {t('auth.register')}
              </Link>
            </div>
          )}

          {/* Mobile menu */}
          <button className="inline-flex items-center justify-center rounded-md text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 h-10 px-5 py-3 md:hidden">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-4 h-4">
              <path d="M4 5h16" />
              <path d="M4 12h16" />
              <path d="M4 19h16" />
            </svg>
          </button>
        </div>
      </div>
    </header>
  );
};

export default Navbar;
