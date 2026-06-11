import type { RootState } from '../../store/store';

export const selectReviews = (state: RootState) => state.reviews.items;
export const selectReviewsStatus = (state: RootState) => state.reviews.status;
export const selectReviewsError = (state: RootState) => state.reviews.error;
export const selectReviewsCount = (state: RootState) => state.reviews.items.length;
