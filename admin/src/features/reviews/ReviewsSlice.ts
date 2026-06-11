import { createAsyncThunk, createSlice } from '@reduxjs/toolkit';
import api, { extractMembers } from '../../services/api';
import type { AdminReview, ReviewsState } from './ReviewsTypes';

export const fetchReviews = createAsyncThunk<AdminReview[], void, { rejectValue: string }>(
  'reviews/fetchAll',
  async (_, { rejectWithValue }) => {
    try {
      const response = await api.get('/api/admin/reviews');
      return extractMembers<AdminReview>(response.data);
    } catch {
      return rejectWithValue('Impossible de charger les avis');
    }
  }
);

const initialState: ReviewsState = {
  items: [],
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
        state.items = action.payload;
      })
      .addCase(fetchReviews.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload ?? 'Impossible de charger les avis';
      });
  },
});

export default reviewsSlice.reducer;
