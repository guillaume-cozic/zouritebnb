import { useEffect } from 'react';
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

interface StatCardProps {
  label: string;
  count: number;
  loading: boolean;
  to: string;
}

function StatCard({ label, count, loading, to }: StatCardProps) {
  return (
    <Link
      to={to}
      className="block rounded-xl border border-surface-200 bg-white p-6 shadow-sm transition-shadow hover:shadow-md"
    >
      <p className="text-sm font-medium text-surface-500">{label}</p>
      {loading ? (
        <div className="mt-2 h-9 w-16 animate-pulse rounded bg-surface-200" />
      ) : (
        <p className="mt-2 text-3xl font-bold text-surface-900">{count}</p>
      )}
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
    <div>
      <h1 className="text-2xl font-bold text-surface-900">Tableau de bord</h1>
      <div className="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard
          label="Réservations"
          count={reservationsCount}
          loading={reservationsStatus === 'loading' || reservationsStatus === 'idle'}
          to="/reservations"
        />
        <StatCard
          label="Hébergements"
          count={accommodationsCount}
          loading={accommodationsStatus === 'loading' || accommodationsStatus === 'idle'}
          to="/accommodations"
        />
        <StatCard
          label="Avis"
          count={reviewsCount}
          loading={reviewsStatus === 'loading' || reviewsStatus === 'idle'}
          to="/reviews"
        />
        <StatCard
          label="Clients"
          count={usersCount}
          loading={usersStatus === 'loading' || usersStatus === 'idle'}
          to="/users"
        />
      </div>
    </div>
  );
}
