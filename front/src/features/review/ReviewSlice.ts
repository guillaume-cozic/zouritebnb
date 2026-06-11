import { createSlice, createAsyncThunk, createAction } from '@reduxjs/toolkit';
import api from '../../services/api';
import { extractErrorMessage, extractErrorStatus } from '../../services/errors';
import {
  AccommodationReview,
  ReviewTarget,
  SubmitAccommodationReviewPayload,
  SubmitGuestReviewPayload,
  SubmittedReview,
} from './ReviewTypes';

type Status = 'idle' | 'loading' | 'succeeded' | 'failed';

/**
 * Single business intent dispatched by the review form. A listener decides which
 * thunk to run depending on the target. The component never orchestrates the call.
 */
export type ReviewSubmittedPayload =
  | { target: 'accommodation'; reservationId: string; payload: SubmitAccommodationReviewPayload }
  | { target: 'guest'; reservationId: string; payload: SubmitGuestReviewPayload };

export const reviewSubmitted = createAction<ReviewSubmittedPayload>('review/submitted');

/** Intent: the user opened/closed the rating form, so any previous error is cleared. */
export const reviewFormOpened = createAction('review/formOpened');

interface ReviewState {
  status: Status;
  error: string | null;
  /** HTTP status code of the last failed submission (used to localise 422/403). */
  errorCode: number | null;
  /** Reviews already submitted in this session, keyed by reservation + target. */
  submitted: SubmittedReview[];
  /** Reviews of the accommodation currently displayed on the detail page. */
  list: AccommodationReview[];
  listStatus: Status;
}

const initialState: ReviewState = {
  status: 'idle',
  error: null,
  errorCode: null,
  submitted: [],
  list: [],
  listStatus: 'idle',
};

interface RejectMeta {
  status?: number;
  detail?: string;
}

const extractError = (err: unknown): RejectMeta => ({
  status: extractErrorStatus(err),
  detail: extractErrorMessage(err, 'Une erreur est survenue'),
});

export const submitAccommodationReview = createAsyncThunk<
  { reservationId: string },
  { reservationId: string; payload: SubmitAccommodationReviewPayload },
  { rejectValue: RejectMeta }
>('review/submitAccommodation', async ({ reservationId, payload }, { rejectWithValue }) => {
  try {
    await api.post('/api/reviews/accommodation', payload, {
      headers: { 'Content-Type': 'application/ld+json' },
    });
    return { reservationId };
  } catch (err) {
    return rejectWithValue(extractError(err));
  }
});

export const submitGuestReview = createAsyncThunk<
  { reservationId: string },
  { reservationId: string; payload: SubmitGuestReviewPayload },
  { rejectValue: RejectMeta }
>('review/submitGuest', async ({ reservationId, payload }, { rejectWithValue }) => {
  try {
    await api.post('/api/reviews/guest', payload, {
      headers: { 'Content-Type': 'application/ld+json' },
    });
    return { reservationId };
  } catch (err) {
    return rejectWithValue(extractError(err));
  }
});

export const fetchAccommodationReviews = createAsyncThunk<AccommodationReview[], string>(
  'review/fetchAccommodationReviews',
  async (accommodationId, { rejectWithValue }) => {
    try {
      const response = await api.get(`/api/accommodations/${accommodationId}/reviews`);
      const data = response.data;
      return (data['hydra:member'] ?? data['member'] ?? []) as AccommodationReview[];
    } catch (err) {
      return rejectWithValue(extractError(err));
    }
  }
);

const markSubmitted = (state: ReviewState, target: ReviewTarget, reservationId: string) => {
  state.status = 'succeeded';
  state.error = null;
  state.errorCode = null;
  const already = state.submitted.some(
    (s) => s.reservationId === reservationId && s.target === target
  );
  if (!already) state.submitted.push({ target, reservationId });
};

const markFailed = (state: ReviewState, meta: RejectMeta | undefined) => {
  state.status = 'failed';
  state.errorCode = meta?.status ?? null;
  state.error = meta?.detail ?? null;
};

const reviewSlice = createSlice({
  name: 'review',
  initialState,
  reducers: {},
  extraReducers: (builder) => {
    builder
      .addCase(reviewFormOpened, (state) => {
        state.status = 'idle';
        state.error = null;
        state.errorCode = null;
      })
      .addCase(submitAccommodationReview.pending, (state) => {
        state.status = 'loading';
        state.error = null;
        state.errorCode = null;
      })
      .addCase(submitAccommodationReview.fulfilled, (state, action) => {
        markSubmitted(state, 'accommodation', action.payload.reservationId);
      })
      .addCase(submitAccommodationReview.rejected, (state, action) => {
        markFailed(state, action.payload);
      })
      .addCase(submitGuestReview.pending, (state) => {
        state.status = 'loading';
        state.error = null;
        state.errorCode = null;
      })
      .addCase(submitGuestReview.fulfilled, (state, action) => {
        markSubmitted(state, 'guest', action.payload.reservationId);
      })
      .addCase(submitGuestReview.rejected, (state, action) => {
        markFailed(state, action.payload);
      })
      .addCase(fetchAccommodationReviews.pending, (state) => {
        state.listStatus = 'loading';
      })
      .addCase(fetchAccommodationReviews.fulfilled, (state, action) => {
        state.listStatus = 'succeeded';
        state.list = action.payload;
      })
      .addCase(fetchAccommodationReviews.rejected, (state) => {
        state.listStatus = 'failed';
        state.list = [];
      });
  },
});

export default reviewSlice.reducer;
