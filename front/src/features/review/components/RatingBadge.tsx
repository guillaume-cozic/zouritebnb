import React from 'react';
import { useTranslation } from 'react-i18next';

interface Props {
  rating?: number | null;
  count?: number | null;
  /** Show the number of reviews next to the rating. */
  showCount?: boolean;
  className?: string;
}

const StarIcon: React.FC<{ filled: boolean; size: number }> = ({ filled, size }) => (
  <svg
    width={size}
    height={size}
    viewBox="0 0 24 24"
    fill={filled ? '#f59e0b' : 'none'}
    stroke={filled ? '#f59e0b' : '#d1d5db'}
    strokeWidth="1.5"
    strokeLinecap="round"
    strokeLinejoin="round"
  >
    <path d="M12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
  </svg>
);

/**
 * Read-only display of an accommodation's average rating out of 5.
 * Falls back to a "no reviews yet" hint when there is no rating.
 */
const RatingBadge: React.FC<Props> = ({ rating, count = 0, showCount = true, className = '' }) => {
  const { t } = useTranslation();
  const reviewCount = count ?? 0;

  if (rating == null || reviewCount === 0) {
    return (
      <span className={`inline-flex items-center gap-1 text-sm text-gray-400 ${className}`}>
        <StarIcon filled={false} size={16} />
        {t('review.noRatingYet')}
      </span>
    );
  }

  return (
    <span className={`inline-flex items-center gap-1 text-sm text-gray-900 ${className}`}>
      <StarIcon filled size={16} />
      <span className="font-semibold tabular-nums">{rating.toFixed(1)}</span>
      {showCount && (
        <span className="font-normal text-gray-500">
          ({t('review.reviewCount', { count: reviewCount })})
        </span>
      )}
    </span>
  );
};

export default RatingBadge;
