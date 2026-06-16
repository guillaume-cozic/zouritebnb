import React from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';

const BLOG_URL = import.meta.env.VITE_BLOG_URL ?? '/blog';

const Footer: React.FC = () => {
  const { t } = useTranslation();

  return (
    <footer className="bg-gray-900 text-gray-400">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="py-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
          <div>
            <div className="flex items-center gap-2 mb-4">
              <div className="w-8 h-8 rounded-lg bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                  <path d="M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
              </div>
              <span className="text-lg font-bold text-white">BnB</span>
            </div>
            <p className="text-sm leading-relaxed">{t('footer.description')}</p>
          </div>

          <div>
            <h4 className="font-semibold text-white mb-4 text-sm uppercase tracking-wider">{t('footer.discover')}</h4>
            <ul className="space-y-2.5 text-sm">
              <li>
                <Link to="/" className="hover:text-white transition-colors">{t('navbar.home')}</Link>
              </li>
              <li>
                <Link to="/accommodations" className="hover:text-white transition-colors">{t('footer.accommodations')}</Link>
              </li>
              <li>
                <Link to="/solidarity-projects" className="hover:text-white transition-colors">{t('footer.solidarityProjects')}</Link>
              </li>
              <li>
                <a href={BLOG_URL} className="hover:text-white transition-colors">{t('footer.blog')}</a>
              </li>
            </ul>
          </div>

          <div>
            <h4 className="font-semibold text-white mb-4 text-sm uppercase tracking-wider">{t('footer.hosts')}</h4>
            <ul className="space-y-2.5 text-sm">
              <li>
                <Link to="/create" className="hover:text-white transition-colors">{t('navbar.createAccommodation')}</Link>
              </li>
              <li>
                <Link to="/login" className="hover:text-white transition-colors">{t('auth.login')}</Link>
              </li>
            </ul>
          </div>

          <div>
            <h4 className="font-semibold text-white mb-4 text-sm uppercase tracking-wider">{t('footer.contact')}</h4>
            <ul className="space-y-2.5 text-sm">
              <li>contact@bnb.com</li>
            </ul>
          </div>
        </div>
        <div className="border-t border-gray-800 py-6 text-center text-sm text-gray-500">
          {t('footer.copyright')}
        </div>
      </div>
    </footer>
  );
};

export default Footer;
