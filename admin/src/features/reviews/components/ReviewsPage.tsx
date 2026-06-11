import { useEffect, useState } from 'react';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { fetchReviews } from '../ReviewsSlice';
import { selectReviews, selectReviewsError, selectReviewsStatus } from '../ReviewsSelectors';
import type { AdminReview } from '../ReviewsTypes';
import { Badge } from '../../../components/ui/Badge';
import { ListSkeleton } from '../../../components/ui/Skeleton';
import { ErrorMessage } from '../../../components/ui/ErrorMessage';
import { EmptyState } from '../../../components/ui/EmptyState';
import { SearchInput } from '../../../components/ui/SearchInput';
import { FilterChips } from '../../../components/ui/FilterChips';
import { formatDate } from '../../../services/format';

const subjectLabel = (review: AdminReview): string =>
  review.type === 'accommodation'
    ? review.subjectAccommodationTitle ?? 'Hébergement inconnu'
    : review.subjectUserName ?? 'Voyageur inconnu';

function Stars({ rating }: { rating: number }) {
  return (
    <span className="text-warning-500" aria-label={`Note : ${rating} sur 5`}>
      {Array.from({ length: 5 }, (_, i) => (i < rating ? '★' : '☆')).join('')}
    </span>
  );
}

function ReviewCard({ review }: { review: AdminReview }) {
  return (
    <article className="rounded-xl border border-surface-200 bg-white p-5 shadow-sm">
      <div className="flex flex-wrap items-center justify-between gap-2">
        <div className="flex items-center gap-3">
          <Stars rating={review.rating} />
          <Badge variant={review.type === 'accommodation' ? 'primary' : 'surface'}>
            {review.type === 'accommodation' ? 'Hébergement' : 'Voyageur'}
          </Badge>
        </div>
        <time className="text-sm text-surface-400">{formatDate(review.createdAt)}</time>
      </div>
      <p className="mt-3 text-sm text-surface-700">{review.comment}</p>
      <p className="mt-3 text-sm text-surface-500">
        <span className="font-medium text-surface-700">{review.authorName ?? 'Auteur inconnu'}</span>
        {' → '}
        <span className="font-medium text-surface-700">{subjectLabel(review)}</span>
      </p>
    </article>
  );
}

export function ReviewsPage() {
  const dispatch = useAppDispatch();
  const reviews = useAppSelector(selectReviews);
  const status = useAppSelector(selectReviewsStatus);
  const error = useAppSelector(selectReviewsError);

  const [search, setSearch] = useState('');
  const [typeFilter, setTypeFilter] = useState('all');

  useEffect(() => {
    dispatch(fetchReviews());
  }, [dispatch]);

  const query = search.trim().toLowerCase();
  const filtered = reviews.filter((r) => {
    if (typeFilter !== 'all' && r.type !== typeFilter) return false;
    if (!query) return true;
    return (
      r.comment.toLowerCase().includes(query) ||
      (r.authorName ?? '').toLowerCase().includes(query) ||
      subjectLabel(r).toLowerCase().includes(query)
    );
  });

  return (
    <div>
      <h1 className="text-2xl font-bold text-surface-900">Avis</h1>

      <div className="mt-6 flex flex-wrap items-center gap-4">
        <SearchInput
          value={search}
          onChange={setSearch}
          placeholder="Rechercher par commentaire, auteur ou sujet…"
        />
        <FilterChips
          options={[
            { value: 'all', label: 'Tous' },
            { value: 'accommodation', label: 'Hébergements' },
            { value: 'guest', label: 'Voyageurs' },
          ]}
          value={typeFilter}
          onChange={setTypeFilter}
        />
      </div>

      <div className="mt-6">
        {status === 'loading' || status === 'idle' ? (
          <ListSkeleton />
        ) : status === 'failed' ? (
          <ErrorMessage message={error} />
        ) : filtered.length === 0 ? (
          <EmptyState message="Aucun avis ne correspond à votre recherche." />
        ) : (
          <div className="space-y-4">
            {filtered.map((r) => (
              <ReviewCard key={r.id} review={r} />
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
