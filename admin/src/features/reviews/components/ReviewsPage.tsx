import { useCallback } from 'react';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { fetchReviews } from '../ReviewsSlice';
import {
  selectReviews,
  selectReviewsCount,
  selectReviewsError,
  selectReviewsPage,
  selectReviewsPerPage,
  selectReviewsStatus,
} from '../ReviewsSelectors';
import type { AdminReview } from '../ReviewsTypes';
import { Badge } from '../../../components/ui/Badge';
import { Card } from '../../../components/ui/Card';
import { ListPage } from '../../../components/ListPage';
import { useCollectionQuery, type CollectionQuery } from '../../../hooks/useCollectionQuery';
import { formatDate } from '../../../services/format';

const TYPE_OPTIONS = [
  { value: 'all', label: 'Tous' },
  { value: 'accommodation', label: 'Hébergements' },
  { value: 'guest', label: 'Voyageurs' },
];

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
    <Card className="p-5">
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
    </Card>
  );
}

export function ReviewsPage() {
  const dispatch = useAppDispatch();
  const reviews = useAppSelector(selectReviews);
  const status = useAppSelector(selectReviewsStatus);
  const error = useAppSelector(selectReviewsError);
  const total = useAppSelector(selectReviewsCount);
  const page = useAppSelector(selectReviewsPage);
  const itemsPerPage = useAppSelector(selectReviewsPerPage);

  const fetchPage = useCallback(
    (query: CollectionQuery) => {
      dispatch(fetchReviews({ page: query.page, search: query.search, type: query.filter }));
    },
    [dispatch]
  );

  const { search, filter, onSearchChange, onFilterChange, setPage } = useCollectionQuery(fetchPage);

  return (
    <ListPage
      title="Avis"
      subtitle="Tous les avis publiés sur la plateforme."
      count={total}
      search={search}
      onSearchChange={onSearchChange}
      searchPlaceholder="Rechercher par commentaire, auteur ou sujet…"
      filterOptions={TYPE_OPTIONS}
      filterValue={filter}
      onFilterChange={onFilterChange}
      status={status}
      error={error}
      isEmpty={reviews.length === 0}
      emptyMessage="Aucun avis ne correspond à votre recherche."
      page={page}
      itemsPerPage={itemsPerPage}
      onPageChange={setPage}
    >
      <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
        {reviews.map((r) => (
          <ReviewCard key={r.id} review={r} />
        ))}
      </div>
    </ListPage>
  );
}
