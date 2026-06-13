export type ReviewType = 'accommodation' | 'guest';

export interface AdminReview {
  id: string;
  type: ReviewType;
  rating: number;
  comment: string;
  createdAt: string;
  authorUserId: string;
  authorName: string | null;
  subjectAccommodationId: string | null;
  subjectAccommodationTitle: string | null;
  subjectUserId: string | null;
  subjectUserName: string | null;
}

export interface ReviewsState {
  items: AdminReview[];
  page: number;
  itemsPerPage: number;
  totalItems: number;
  status: 'idle' | 'loading' | 'succeeded' | 'failed';
  error: string | null;
}
