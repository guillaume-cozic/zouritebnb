import React from 'react';
import { useTranslation } from 'react-i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { addToWishlist, removeFromWishlist } from '../WishlistSlice';
import { selectIsInWishlist } from '../WishlistSelectors';

interface WishlistButtonProps {
  accommodationId: string;
  /** "overlay" = round icon button over a photo; "inline" = labelled button. */
  variant?: 'overlay' | 'inline';
  className?: string;
}

const HeartIcon: React.FC<{ filled: boolean; size?: number }> = ({ filled, size = 18 }) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={size}
    height={size}
    viewBox="0 0 24 24"
    fill={filled ? 'currentColor' : 'none'}
    stroke="currentColor"
    strokeWidth="2"
    strokeLinecap="round"
    strokeLinejoin="round"
  >
    <path d="M2 9.5a5.5 5.5 0 0 1 9.591-3.676.56.56 0 0 0 .818 0A5.49 5.49 0 0 1 22 9.5c0 2.29-1.5 4-3 5.5l-5.492 5.313a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5" />
  </svg>
);

const WishlistButton: React.FC<WishlistButtonProps> = ({
  accommodationId,
  variant = 'overlay',
  className = '',
}) => {
  const { t } = useTranslation();
  const dispatch = useAppDispatch();
  const isSaved = useAppSelector(selectIsInWishlist(accommodationId));

  const toggle = (e: React.MouseEvent) => {
    // The button often sits inside a card-wide <Link>: don't navigate on toggle.
    e.preventDefault();
    e.stopPropagation();
    if (isSaved) {
      dispatch(removeFromWishlist(accommodationId));
    } else {
      dispatch(addToWishlist(accommodationId));
    }
  };

  const label = isSaved ? t('wishlist.remove') : t('wishlist.add');

  if (variant === 'inline') {
    return (
      <button
        type="button"
        onClick={toggle}
        aria-pressed={isSaved}
        aria-label={label}
        className={`flex items-center gap-2 border h-10 px-4 py-2 rounded-md text-sm font-medium transition-colors ${
          isSaved
            ? 'border-rose-200 bg-rose-50 text-rose-600 hover:bg-rose-100'
            : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
        } ${className}`}
      >
        <HeartIcon filled={isSaved} />
        {isSaved ? t('wishlist.saved') : t('detail.save')}
      </button>
    );
  }

  return (
    <button
      type="button"
      onClick={toggle}
      aria-pressed={isSaved}
      aria-label={label}
      title={label}
      className={`inline-flex items-center justify-center w-9 h-9 rounded-full bg-white/95 backdrop-blur-sm shadow-sm transition-all hover:scale-110 ${
        isSaved ? 'text-rose-600' : 'text-gray-700 hover:text-rose-600'
      } ${className}`}
    >
      <HeartIcon filled={isSaved} />
    </button>
  );
};

export default WishlistButton;
