import React, { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { reviewFormOpened, reviewSubmitted } from '../ReviewSlice';
import {
  selectReviewError,
  selectReviewErrorCode,
  selectReviewStatus,
} from '../ReviewSelectors';
import { selectAuthUser } from '../../auth/AuthSelectors';
import { MIN_COMMENT_LENGTH, ReviewTarget } from '../ReviewTypes';
import StarRating from './StarRating';

interface Props {
  target: ReviewTarget;
  reservationId: string;
  accommodationId: string;
  /** Required only when target === 'guest' (the user being rated). */
  guestUserId?: string;
  onSubmitted?: () => void;
  onCancel?: () => void;
}

const ReviewForm: React.FC<Props> = ({
  target,
  reservationId,
  accommodationId,
  guestUserId,
  onSubmitted,
  onCancel,
}) => {
  const { t } = useTranslation();
  const dispatch = useAppDispatch();
  const user = useAppSelector(selectAuthUser);
  const status = useAppSelector(selectReviewStatus);
  const error = useAppSelector(selectReviewError);
  const errorCode = useAppSelector(selectReviewErrorCode);

  const [rating, setRating] = useState(0);
  const [comment, setComment] = useState('');

  // Single intent on mount: clears any previous error so the form starts clean.
  useEffect(() => {
    dispatch(reviewFormOpened());
  }, [dispatch]);

  // Local UI close after a successful mutation (allowed exception in the skill).
  useEffect(() => {
    if (status === 'succeeded' && onSubmitted) onSubmitted();
  }, [status, onSubmitted]);

  const trimmedLength = comment.trim().length;
  const commentTooShort = trimmedLength < MIN_COMMENT_LENGTH;
  const canSubmit =
    !!user &&
    rating >= 1 &&
    rating <= 5 &&
    !commentTooShort &&
    status !== 'loading';

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!user || !canSubmit) return;

    if (target === 'accommodation') {
      dispatch(
        reviewSubmitted({
          target: 'accommodation',
          reservationId,
          payload: {
            accommodationId,
            rating,
            comment: comment.trim(),
          },
        })
      );
    } else {
      if (!guestUserId) return;
      dispatch(
        reviewSubmitted({
          target: 'guest',
          reservationId,
          payload: {
            accommodationId,
            guestUserId,
            rating,
            comment: comment.trim(),
          },
        })
      );
    }
  };

  const localisedError = (): string | null => {
    if (status !== 'failed') return null;
    if (errorCode === 422) return t('review.error.validation');
    if (errorCode === 403) return t('review.error.forbidden');
    if (errorCode === 401) return t('review.error.unauthenticated');
    return error || t('review.error.generic');
  };

  const errorMessage = localisedError();

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1.5">
          {t('review.ratingLabel')}
        </label>
        <StarRating value={rating} onChange={setRating} disabled={status === 'loading'} />
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1.5">
          {t('review.commentLabel')}
        </label>
        <textarea
          value={comment}
          onChange={(e) => setComment(e.target.value)}
          rows={4}
          maxLength={2000}
          placeholder={t('review.commentPlaceholder') as string}
          disabled={status === 'loading'}
          className="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 focus:bg-white resize-none transition-all disabled:opacity-60"
        />
        <p
          className={`mt-1 text-xs ${
            commentTooShort ? 'text-amber-600' : 'text-gray-400'
          }`}
        >
          {commentTooShort
            ? t('review.charsRemaining', {
                count: Math.max(0, MIN_COMMENT_LENGTH - trimmedLength),
              })
            : t('review.charsCount', { count: trimmedLength })}
        </p>
      </div>

      {errorMessage && (
        <div className="text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
          {errorMessage}
        </div>
      )}

      <div className="flex justify-end gap-2">
        {onCancel && (
          <button
            type="button"
            onClick={onCancel}
            className="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-gray-200 text-sm font-medium text-gray-700 hover:bg-gray-50"
          >
            {t('review.cancel')}
          </button>
        )}
        <button
          type="submit"
          disabled={!canSubmit}
          className="inline-flex items-center justify-center h-10 px-5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 shadow-sm shadow-primary-200 disabled:opacity-60 disabled:cursor-not-allowed transition-all"
        >
          {status === 'loading' ? t('review.submitting') : t('review.submit')}
        </button>
      </div>
    </form>
  );
};

export default ReviewForm;
