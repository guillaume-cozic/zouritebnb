export type ReviewTarget = 'accommodation' | 'guest';

/** Payload sent to POST /api/reviews/accommodation (traveler rates the accommodation). */
export interface SubmitAccommodationReviewPayload {
  accommodationId: string;
  rating: number;
  comment: string;
}

/** Payload sent to POST /api/reviews/guest (host rates the guest). */
export interface SubmitGuestReviewPayload {
  accommodationId: string;
  guestUserId: string;
  rating: number;
  comment: string;
}

/** A review displayed on the accommodation detail page (GET /accommodations/{id}/reviews). */
export interface AccommodationReview {
  id: string;
  rating: number;
  comment: string;
  authorName: string;
  /** Relative URL of the author's photo (prefix with the API base), or null. */
  authorAvatarUrl?: string | null;
  createdAt: string;
  /** Public reply from the host, or null. */
  hostReply?: string | null;
  hostReplyAt?: string | null;
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
