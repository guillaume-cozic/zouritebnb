import React, { useEffect } from 'react';
import { NavLink, Outlet, useLocation } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { fetchOwnsAccommodation } from '../AccommodationManagementSlice';
import { selectHasAccommodation } from '../AccommodationManagementSelectors';
import { selectUnreadCount } from '../../conversation/ConversationSelectors';
import { UnreadBadge } from '../../../components/ui';

interface MenuItem {
  to: string;
  labelKey: string;
  icon: React.ReactNode;
}

// Host menu entries that are pointless without a listing — hidden until the host owns one.
const LISTING_GATED_ROUTES = ['/admin/accommodations', '/admin/calendar', '/admin/reservations'];

const HOST_MENU: MenuItem[] = [
  {
    to: '/admin',
    labelKey: 'backoffice.menu.home',
    icon: (
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M2 12h3" />
        <path d="M19 12h3" />
        <path d="m4.93 4.93 2.12 2.12" />
        <path d="m16.95 16.95 2.12 2.12" />
        <path d="M12 2v3" />
        <circle cx="12" cy="14" r="6" />
        <path d="M12 8v6l3 2" />
      </svg>
    ),
  },
  {
    to: '/admin/accommodations',
    labelKey: 'backoffice.menu.accommodations',
    icon: (
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M3 9.5 12 3l9 6.5V21a1 1 0 0 1-1 1h-5v-7h-6v7H4a1 1 0 0 1-1-1z" />
      </svg>
    ),
  },
  {
    to: '/admin/calendar',
    labelKey: 'backoffice.menu.calendar',
    icon: (
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <rect x="3" y="4" width="18" height="18" rx="2" ry="2" /><line x1="16" y1="2" x2="16" y2="6" /><line x1="8" y1="2" x2="8" y2="6" /><line x1="3" y1="10" x2="21" y2="10" />
      </svg>
    ),
  },
  {
    to: '/admin/reservations',
    labelKey: 'backoffice.menu.reservations',
    icon: (
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
        <polyline points="14 2 14 8 20 8" />
        <line x1="9" y1="13" x2="15" y2="13" />
        <line x1="9" y1="17" x2="15" y2="17" />
      </svg>
    ),
  },
  {
    to: '/admin/conversations',
    labelKey: 'backoffice.menu.conversations',
    icon: (
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
      </svg>
    ),
  },
  {
    to: '/admin/team',
    labelKey: 'backoffice.menu.team',
    icon: (
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M22 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" />
      </svg>
    ),
  },
];

const TRAVELER_MENU: MenuItem[] = [
  {
    to: '/account',
    labelKey: 'backoffice.menu.home',
    icon: (
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M3 9.5 12 3l9 6.5V21a1 1 0 0 1-1 1h-5v-7h-6v7H4a1 1 0 0 1-1-1z" />
      </svg>
    ),
  },
  {
    to: '/account/conversations',
    labelKey: 'backoffice.menu.conversations',
    icon: (
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
      </svg>
    ),
  },
  {
    to: '/account/settings',
    labelKey: 'backoffice.menu.team',
    icon: (
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M22 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" />
      </svg>
    ),
  },
];

const BackofficeLayout: React.FC = () => {
  const { t } = useTranslation();
  const location = useLocation();
  const dispatch = useAppDispatch();
  const hasAccommodation = useAppSelector(selectHasAccommodation);
  const unreadCount = useAppSelector(selectUnreadCount);
  // Confirmed non-owners are travelers even on /admin (e.g. landing there via the
  // default post-login redirect), so they get the traveler menu pointing to /account.
  const isTraveler = location.pathname.startsWith('/account') || hasAccommodation === false;
  const titleKey = isTraveler ? 'backoffice.menu.travelerTitle' : 'backoffice.menu.title';
  const footerKey = isTraveler ? 'backoffice.travelerSidebarFooter' : 'backoffice.sidebarFooter';

  useEffect(() => {
    if (!isTraveler) {
      dispatch(fetchOwnsAccommodation());
    }
  }, [dispatch, isTraveler]);

  // Hide listing-gated entries only once we know the host owns nothing — avoids a flicker
  // for hosts who do have listings (and the route guard catches clicks made meanwhile).
  const hostMenu =
    hasAccommodation === false
      ? HOST_MENU.filter((item) => !LISTING_GATED_ROUTES.includes(item.to))
      : HOST_MENU;
  const menu = isTraveler ? TRAVELER_MENU : hostMenu;

  return (
    <div className="flex min-h-[calc(100vh-4rem)]">
      <aside className="w-64 shrink-0 border-r border-primary-100/70 bg-gradient-to-b from-white via-primary-50/30 to-white flex flex-col">
        <div className="px-5 pt-6 pb-5 border-b border-primary-100/70">
          <div className="flex items-center gap-3">
            <div className="w-9 h-9 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-white shadow-sm shadow-primary-200">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                <path d="M3 9.5 12 3l9 6.5V21a1 1 0 0 1-1 1h-5v-7h-6v7H4a1 1 0 0 1-1-1z" />
              </svg>
            </div>
            <div className="min-w-0">
              <p className="text-sm font-bold text-gray-900 leading-tight">BnB</p>
              <p className="text-[11px] text-primary-700/70 font-medium leading-tight mt-0.5">
                {t(titleKey)}
              </p>
            </div>
          </div>
        </div>

        <div className="px-3 py-4 flex-1">
          <nav className="flex flex-col gap-0.5">
            {menu.map((item) => (
              <NavLink
                key={item.to}
                to={item.to}
                end
                className={({ isActive }) =>
                  `group relative flex items-center gap-3 pl-4 pr-3 py-2 rounded-lg text-sm font-medium transition-all ${
                    isActive
                      ? 'bg-primary-600 text-white shadow-sm shadow-primary-200'
                      : 'text-gray-600 hover:bg-primary-50/70 hover:text-primary-700'
                  }`
                }
              >
                {({ isActive }) => (
                  <>
                    <span
                      aria-hidden="true"
                      className={`absolute left-0 top-1/2 -translate-y-1/2 h-5 w-0.5 rounded-r transition-all ${
                        isActive ? 'bg-primary-200' : 'bg-transparent group-hover:bg-primary-300'
                      }`}
                    />
                    <span className={isActive ? 'text-white' : 'text-gray-400 group-hover:text-primary-600'}>
                      {item.icon}
                    </span>
                    <span className="flex-1">{t(item.labelKey)}</span>
                    {item.labelKey === 'backoffice.menu.conversations' && (
                      <UnreadBadge count={unreadCount} />
                    )}
                  </>
                )}
              </NavLink>
            ))}
          </nav>
        </div>

        <div className="px-5 py-4 border-t border-primary-100/70">
          <p className="text-[11px] text-gray-400 leading-relaxed">
            {t(footerKey)}
          </p>
        </div>
      </aside>
      <main className="flex-1 bg-gradient-to-br from-primary-50/30 via-gray-50 to-white">
        <Outlet />
      </main>
    </div>
  );
};

export default BackofficeLayout;
