import { useEffect, type ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { useAppDispatch, useAppSelector } from '../store/hooks';
import { fetchReservations } from '../features/reservations/ReservationsSlice';
import { fetchAccommodations } from '../features/accommodations/AccommodationsSlice';
import { fetchReviews } from '../features/reviews/ReviewsSlice';
import { fetchUsers } from '../features/users/UsersSlice';
import { fetchDashboard } from '../features/dashboard/DashboardSlice';
import {
  selectReservationsCount,
  selectReservationsStatus,
} from '../features/reservations/ReservationsSelectors';
import {
  selectAccommodationsCount,
  selectAccommodationsStatus,
} from '../features/accommodations/AccommodationsSelectors';
import { selectReviewsCount, selectReviewsStatus } from '../features/reviews/ReviewsSelectors';
import { selectUsersCount, selectUsersStatus } from '../features/users/UsersSelectors';
import {
  selectDashboard,
  selectDashboardStatus,
} from '../features/dashboard/DashboardSelectors';
import { Card, PageHeader } from './ui/Card';
import { EmptyState } from './ui/EmptyState';
import { formatMoney } from '../services/format';

const formatRate = (rate: number) => `${Math.round(rate * 1000) / 10} %`;

interface StatCardProps {
  label: string;
  count: number;
  loading: boolean;
  to: string;
  icon: ReactNode;
  accent: string;
}

function StatCard({ label, count, loading, to, icon, accent }: StatCardProps) {
  return (
    <Link
      to={to}
      className="group flex items-center gap-4 rounded-2xl border border-surface-200 bg-white p-6 shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md"
    >
      <span className={`flex h-12 w-12 items-center justify-center rounded-xl text-xl ${accent}`}>
        {icon}
      </span>
      <div>
        <p className="text-sm font-medium text-surface-500">{label}</p>
        {loading ? (
          <div className="mt-1.5 h-8 w-16 animate-pulse rounded bg-surface-200" />
        ) : (
          <p className="text-3xl font-bold tracking-tight text-surface-900">{count}</p>
        )}
      </div>
    </Link>
  );
}

interface MoneyCardProps {
  label: string;
  amount: number;
  hint?: string;
  loading: boolean;
  accent: string;
}

function MoneyCard({ label, amount, hint, loading, accent }: MoneyCardProps) {
  return (
    <div className={`rounded-2xl border p-6 shadow-sm ${accent}`}>
      <p className="text-sm font-medium opacity-80">{label}</p>
      {loading ? (
        <div className="mt-2 h-9 w-32 animate-pulse rounded bg-black/10" />
      ) : (
        <p className="mt-1 text-3xl font-bold tracking-tight">{formatMoney(amount)}</p>
      )}
      {hint && !loading && <p className="mt-1 text-xs opacity-70">{hint}</p>}
    </div>
  );
}

export function DashboardPage() {
  const dispatch = useAppDispatch();

  const reservationsCount = useAppSelector(selectReservationsCount);
  const reservationsStatus = useAppSelector(selectReservationsStatus);
  const accommodationsCount = useAppSelector(selectAccommodationsCount);
  const accommodationsStatus = useAppSelector(selectAccommodationsStatus);
  const reviewsCount = useAppSelector(selectReviewsCount);
  const reviewsStatus = useAppSelector(selectReviewsStatus);
  const usersCount = useAppSelector(selectUsersCount);
  const usersStatus = useAppSelector(selectUsersStatus);

  const dashboard = useAppSelector(selectDashboard);
  const dashboardStatus = useAppSelector(selectDashboardStatus);
  const financeLoading = dashboardStatus === 'loading' || dashboardStatus === 'idle';

  useEffect(() => {
    dispatch(fetchDashboard());
    dispatch(fetchReservations());
    dispatch(fetchAccommodations());
    dispatch(fetchReviews());
    dispatch(fetchUsers());
  }, [dispatch]);

  return (
    <div className="space-y-8">
      <PageHeader title="Tableau de bord" subtitle="Vue d'ensemble de la plateforme." />

      <section className="space-y-4">
        <h2 className="text-sm font-semibold uppercase tracking-wider text-surface-500">Finances</h2>
        <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
          <MoneyCard
            label="Chiffre d'affaires"
            amount={dashboard?.totalRevenue ?? 0}
            hint={
              dashboard ? `${dashboard.confirmedReservations} réservations confirmées` : undefined
            }
            loading={financeLoading}
            accent="border-primary-200 bg-primary-50 text-primary-800"
          />
          <MoneyCard
            label="Marge plateforme"
            amount={dashboard?.totalMargin ?? 0}
            hint={dashboard ? `Commission de ${formatRate(dashboard.commissionRate)}` : undefined}
            loading={financeLoading}
            accent="border-success-200 bg-success-50 text-success-800"
          />
          <MoneyCard
            label="Reversé aux projets"
            amount={dashboard?.totalDonated ?? 0}
            hint={
              dashboard ? `Contribution solidaire de ${formatRate(dashboard.donationRate)}` : undefined
            }
            loading={financeLoading}
            accent="border-warning-200 bg-warning-50 text-warning-800"
          />
        </div>

        <Card className="p-6">
          <h3 className="mb-4 text-base font-semibold text-surface-900">Reversé par projet</h3>
          {financeLoading ? (
            <div className="space-y-2">
              {Array.from({ length: 3 }, (_, i) => (
                <div key={i} className="h-10 animate-pulse rounded-lg bg-surface-100" />
              ))}
            </div>
          ) : !dashboard || dashboard.donationsByProject.length === 0 ? (
            <EmptyState message="Aucun montant reversé pour le moment." />
          ) : (
            <ul className="divide-y divide-surface-100">
              {dashboard.donationsByProject.map((row) => (
                <li key={row.projectId} className="flex items-center justify-between py-3">
                  <span className="text-sm font-medium text-surface-700">{row.title}</span>
                  <span className="text-sm font-semibold text-surface-900">
                    {formatMoney(row.amount)}
                  </span>
                </li>
              ))}
            </ul>
          )}
        </Card>
      </section>

      <section className="space-y-4">
        <h2 className="text-sm font-semibold uppercase tracking-wider text-surface-500">Activité</h2>

        <Link
          to="/reservations"
          className="flex items-center justify-between rounded-2xl border border-primary-200 bg-primary-50 p-6 shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md"
        >
          <div className="flex items-center gap-4">
            <span className="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-600 text-xl text-white">
              🧳
            </span>
            <div>
              <p className="text-sm font-medium text-primary-700">Séjours à venir</p>
              {financeLoading ? (
                <div className="mt-1.5 h-8 w-16 animate-pulse rounded bg-primary-200" />
              ) : (
                <p className="text-3xl font-bold tracking-tight text-primary-900">
                  {dashboard?.upcomingStays ?? 0}
                </p>
              )}
            </div>
          </div>
          <span className="hidden text-sm font-medium text-primary-700 sm:inline">
            Voir les réservations →
          </span>
        </Link>

        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <StatCard
            label="Réservations"
            count={reservationsCount}
            loading={reservationsStatus === 'loading' || reservationsStatus === 'idle'}
            to="/reservations"
            icon="📅"
            accent="bg-primary-50 text-primary-600"
          />
          <StatCard
            label="Hébergements"
            count={accommodationsCount}
            loading={accommodationsStatus === 'loading' || accommodationsStatus === 'idle'}
            to="/accommodations"
            icon="🏠"
            accent="bg-success-50 text-success-600"
          />
          <StatCard
            label="Avis"
            count={reviewsCount}
            loading={reviewsStatus === 'loading' || reviewsStatus === 'idle'}
            to="/reviews"
            icon="⭐"
            accent="bg-warning-50 text-warning-600"
          />
          <StatCard
            label="Clients"
            count={usersCount}
            loading={usersStatus === 'loading' || usersStatus === 'idle'}
            to="/users"
            icon="👥"
            accent="bg-surface-100 text-surface-600"
          />
        </div>
      </section>
    </div>
  );
}
