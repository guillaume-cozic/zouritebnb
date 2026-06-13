import { useEffect, type ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { useAppDispatch, useAppSelector } from '../store/hooks';
import { fetchReservations } from '../features/reservations/ReservationsSlice';
import { fetchAccommodations } from '../features/accommodations/AccommodationsSlice';
import { fetchReviews } from '../features/reviews/ReviewsSlice';
import { fetchUsers } from '../features/users/UsersSlice';
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
import { PageHeader } from './ui/Card';

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

  useEffect(() => {
    dispatch(fetchReservations());
    dispatch(fetchAccommodations());
    dispatch(fetchReviews());
    dispatch(fetchUsers());
  }, [dispatch]);

  return (
    <div className="space-y-6">
      <PageHeader title="Tableau de bord" subtitle="Vue d'ensemble de la plateforme." />
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
    </div>
  );
}
