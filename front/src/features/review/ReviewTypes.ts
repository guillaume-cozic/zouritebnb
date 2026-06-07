export type ReviewTarget = 'accommodation' | 'guest';

/** Payload sent to POST /api/reviews/accommodation (traveler rates the accommodation). */
export interface SubmitAccommodationReviewPayload {
  authorUserId: string;
  accommodationId: string;
  rating: number;
  comment: string;
}

/** Payload sent to POST /api/reviews/guest (host rates the guest). */
export interface SubmitGuestReviewPayload {
  authorUserId: string;
  accommodationId: string;
  guestUserId: string;
  rating: number;
  comment: string;
}

/** A submitted review, tracked locally so the UI can hide the "rate" button. */
export interface SubmittedReview {
  target: ReviewTarget;
  /** The reservation the review relates to, used as the de-duplication key in the UI. */
  reservationId: string;
}

/** Minimum length of a review comment, enforced both by the UI and the backend. */
export const MIN_COMMENT_LENGTH = 50;
export const MIN_RATING = 1;
export const MAX_RATING = 5;
