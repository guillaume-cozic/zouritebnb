import React from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';

const Navbar: React.FC = () => {
  const { t, i18n } = useTranslation();

  const changeLanguage = (lng: string) => {
    i18n.changeLanguage(lng);
    localStorage.setItem('lang', lng);
  };

  return (
    <nav className="fixed top-0 left-0 right-0 bg-white shadow-sm z-50">
      <div className="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
        <Link to="/" className="text-xl font-bold text-blue-600">
          BnB
        </Link>
        <div className="flex items-center gap-6">
          <Link to="/" className="text-gray-600 hover:text-gray-900 text-sm font-medium">
            {t('navbar.home')}
          </Link>
          <div className="flex items-center gap-1 text-sm">
            <button
              onClick={() => changeLanguage('fr')}
              className={`px-2 py-1 rounded ${i18n.language === 'fr' ? 'font-bold text-blue-600' : 'text-gray-500 hover:text-gray-700'}`}
            >
              FR
            </button>
            <span className="text-gray-300">|</span>
            <button
              onClick={() => changeLanguage('en')}
              className={`px-2 py-1 rounded ${i18n.language === 'en' ? 'font-bold text-blue-600' : 'text-gray-500 hover:text-gray-700'}`}
            >
              EN
            </button>
          </div>
          <Link
            to="/create"
            className="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors"
          >
            {t('navbar.createAccommodation')}
          </Link>
        </div>
      </div>
    </nav>
  );
};

export default Navbar;
