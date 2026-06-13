import { createAsyncThunk, createSlice } from '@reduxjs/toolkit';
import api, { extractCollection } from '../../services/api';
import type { AdminReview, ReviewsState } from './ReviewsTypes';

export const REVIEWS_PER_PAGE = 20;

export interface FetchReviewsParams {
  page?: number;
  search?: string;
  type?: string;
}

export const fetchReviews = createAsyncThunk<
  { items: AdminReview[]; totalItems: number; page: number },
  FetchReviewsParams | void,
  { rejectValue: string }
>('reviews/fetchAll', async (params, { rejectWithValue }) => {
  const { page = 1, search = '', type = '' } = params ?? {};
  try {
    const response = await api.get('/api/admin/reviews', {
      params: {
        page,
        itemsPerPage: REVIEWS_PER_PAGE,
        ...(search ? { search } : {}),
        ...(type ? { type } : {}),
      },
    });
    const { items, totalItems } = extractCollection<AdminReview>(response.data);
    return { items, totalItems, page };
  } catch {
    return rejectWithValue('Impossible de charger les avis');
  }
});

const initialState: ReviewsState = {
  items: [],
  page: 1,
  itemsPerPage: REVIEWS_PER_PAGE,
  totalItems: 0,
  status: 'idle',
  error: null,
};

const reviewsSlice = createSlice({
  name: 'reviews',
  initialState,
  reducers: {},
  extraReducers: (builder) => {
    builder
      .addCase(fetchReviews.pending, (state) => {
        state.status = 'loading';
        state.error = null;
      })
      .addCase(fetchReviews.fulfilled, (state, action) => {
        state.status = 'succeeded';
        state.items = action.payload.items;
        state.totalItems = action.payload.totalItems;
        state.page = action.payload.page;
      })
      .addCase(fetchReviews.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload ?? 'Impossible de charger les avis';
      });
  },
});

export default reviewsSlice.reducer;
