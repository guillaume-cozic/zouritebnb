import { createSelector } from '@reduxjs/toolkit';
import { RootState } from '../../store';
import { ReviewTarget } from './ReviewTypes';
import { isStayCompleted } from './reviewEligibility';

export const selectReviewStatus = (state: RootState) => state.review.status;
export const selectAccommodationReviews = (state: RootState) => state.review.list;
export const selectAccommodationReviewsStatus = (state: RootState) => state.review.listStatus;
export const selectReviewError = (state: RootState) => state.review.error;
export const selectReviewErrorCode = (state: RootState) => state.review.errorCode;
export const selectSubmittedReviews = (state: RootState) => state.review.submitted;

/** True when a review for this reservation + target has already been submitted. */
export const selectHasReviewed = (reservationId: string, target: ReviewTarget) =>
  createSelector(selectSubmittedReviews, (submitted) =>
    submitted.some((s) => s.reservationId === reservationId && s.target === target)
  );

/**
 * How many of the current user's stays can still be rated: completed stays (as guest)
 * not yet reviewed. Mirrors the per-conversation "rate" eligibility, surfaced in the menu.
 */
export const selectReviewableCount = createSelector(
  [
    (state: RootState) => state.auth?.user?.id ?? null,
    (state: RootState) => state.reservation?.items ?? [],
    selectSubmittedReviews,
  ],
  (userId, reservations, submitted): number => {
    const now = new Date();
    return reservations.filter(
      (r) =>
        !!userId &&
        r.guestUserId === userId &&
        isStayCompleted(r, now) &&
        !submitted.some((s) => s.reservationId === r.id && s.target === 'accommodation')
    ).length;
  }
);
