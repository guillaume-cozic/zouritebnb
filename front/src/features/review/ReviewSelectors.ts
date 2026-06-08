import { createSelector } from '@reduxjs/toolkit';
import { RootState } from '../../store';
import { ReviewTarget } from './ReviewTypes';

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
