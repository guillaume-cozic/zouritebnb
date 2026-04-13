import React from 'react';
import { NavLink, Outlet } from 'react-router-dom';
import { useTranslation } from 'react-i18next';

interface MenuItem {
  to: string;
  labelKey: string;
  icon: React.ReactNode;
}

const menu: MenuItem[] = [
  {
    to: '/admin/accommodations',
    labelKey: 'backoffice.menu.accommodations',
    icon: (
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M3 9.5 12 3l9 6.5V21a1 1 0 0 1-1 1h-5v-7h-6v7H4a1 1 0 0 1-1-1z" />
      </svg>
    ),
  },
];

const BackofficeLayout: React.FC = () => {
  const { t } = useTranslation();

  return (
    <div className="flex min-h-[calc(100vh-4rem)]">
      <aside className="w-64 shrink-0 border-r border-gray-100 bg-white">
        <div className="px-5 py-6">
          <p className="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-4">
            {t('backoffice.menu.title')}
          </p>
          <nav className="flex flex-col gap-1">
            {menu.map((item) => (
              <NavLink
                key={item.to}
                to={item.to}
                end
                className={({ isActive }) =>
                  `flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors ${
                    isActive
                      ? 'bg-blue-50 text-blue-700'
                      : 'text-gray-700 hover:bg-gray-50'
                  }`
                }
              >
                {item.icon}
                {t(item.labelKey)}
              </NavLink>
            ))}
          </nav>
        </div>
      </aside>
      <main className="flex-1 bg-gray-50/50">
        <Outlet />
      </main>
    </div>
  );
};

export default BackofficeLayout;
