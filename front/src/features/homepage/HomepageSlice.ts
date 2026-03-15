import { createSlice, createAsyncThunk, PayloadAction } from '@reduxjs/toolkit';
import api from '../../services/api';
import { AccommodationListItem, SearchFilters } from './HomepageTypes';

interface HomepageState {
  accommodations: AccommodationListItem[];
  filters: SearchFilters;
  status: 'idle' | 'loading' | 'succeeded' | 'failed';
  error: string | null;
}

const initialState: HomepageState = {
  accommodations: [],
  filters: { city: '', guests: null },
  status: 'idle',
  error: null,
};

export const fetchPublishedAccommodations = createAsyncThunk(
  'homepage/fetchPublished',
  async (_, { rejectWithValue }) => {
    try {
      const response = await api.get('/api/accommodations');
      return response.data['hydra:member'] as AccommodationListItem[];
    } catch (err: any) {
      return rejectWithValue(
        err.response?.data?.detail || 'Erreur lors du chargement des hébergements'
      );
    }
  }
);

const homepageSlice = createSlice({
  name: 'homepage',
  initialState,
  reducers: {
    setFilters(state, action: PayloadAction<Partial<SearchFilters>>) {
      Object.assign(state.filters, action.payload);
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(fetchPublishedAccommodations.pending, (state) => {
        state.status = 'loading';
        state.error = null;
      })
      .addCase(fetchPublishedAccommodations.fulfilled, (state, action) => {
        state.status = 'succeeded';
        state.accommodations = action.payload;
      })
      .addCase(fetchPublishedAccommodations.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload as string;
      });
  },
});

export const { setFilters } = homepageSlice.actions;
export default homepageSlice.reducer;
