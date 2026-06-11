import { NavLink, Outlet } from 'react-router-dom';
import { useAppDispatch, useAppSelector } from '../store/hooks';
import { selectAuthUser } from '../features/auth/AuthSelectors';
import { loggedOut } from '../features/auth/AuthSlice';

const navItems = [
  { to: '/', label: 'Tableau de bord', end: true },
  { to: '/reservations', label: 'Réservations' },
  { to: '/accommodations', label: 'Hébergements' },
  { to: '/reviews', label: 'Avis' },
  { to: '/users', label: 'Clients' },
];

export function AdminLayout() {
  const dispatch = useAppDispatch();
  const user = useAppSelector(selectAuthUser);

  return (
    <div className="min-h-screen bg-surface-50">
      <aside className="fixed inset-y-0 left-0 flex w-60 flex-col border-r border-surface-200 bg-white">
        <div className="flex h-16 items-center border-b border-surface-200 px-6">
          <span className="text-lg font-bold text-primary-700">BnB Admin</span>
        </div>
        <nav className="flex-1 space-y-1 px-3 py-4">
          {navItems.map((item) => (
            <NavLink
              key={item.to}
              to={item.to}
              end={item.end}
              className={({ isActive }) =>
                `block rounded-lg px-3 py-2 text-sm font-medium transition-colors ${
                  isActive
                    ? 'bg-primary-50 text-primary-700'
                    : 'text-surface-600 hover:bg-surface-100 hover:text-surface-900'
                }`
              }
            >
              {item.label}
            </NavLink>
          ))}
        </nav>
      </aside>

      <div className="pl-60">
        <header className="flex h-16 items-center justify-end gap-4 border-b border-surface-200 bg-white px-6">
          <span className="text-sm text-surface-600">{user?.email}</span>
          <button
            type="button"
            onClick={() => dispatch(loggedOut())}
            className="rounded-lg border border-surface-300 px-3 py-1.5 text-sm font-medium text-surface-700 hover:bg-surface-100"
          >
            Se déconnecter
          </button>
        </header>
        <main className="p-6">
          <Outlet />
        </main>
      </div>
    </div>
  );
}
