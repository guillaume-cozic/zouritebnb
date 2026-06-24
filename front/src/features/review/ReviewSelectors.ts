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
 * Set (keyed by reservation id) of the current user's stays that can still be rated:
 * completed stays (as guest) not yet reviewed. Mirrors the per-conversation "rate"
 * eligibility, surfaced as a label in the conversation list.
 */
export const selectReviewableReservationIds = createSelector(
  [
    (state: RootState) => state.auth?.user?.id ?? null,
    (state: RootState) => state.reservation?.items ?? [],
    selectSubmittedReviews,
  ],
  (userId, reservations, submitted): Record<string, true> => {
    const now = new Date();
    const map: Record<string, true> = {};
    for (const r of reservations) {
      if (
        !!userId &&
        r.guestUserId === userId &&
        isStayCompleted(r, now) &&
        !submitted.some((s) => s.reservationId === r.id && s.target === 'accommodation')
      ) {
        map[r.id] = true;
      }
    }
    return map;
  }
);
