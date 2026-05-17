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
  filters: { city: '', checkIn: '', checkOut: '', guests: null, amenities: [], priceMin: null, priceMax: null },
  status: 'idle',
  error: null,
};

interface FetchPublishedParams {
  checkIn?: string;
  checkOut?: string;
  city?: string;
  guests?: number | null;
  priceMin?: number | null;
  priceMax?: number | null;
  amenities?: string[];
}

export const fetchPublishedAccommodations = createAsyncThunk<
  AccommodationListItem[],
  FetchPublishedParams | void
>(
  'homepage/fetchPublished',
  async (params, { rejectWithValue }) => {
    try {
      const queryParams: Record<string, string | string[]> = {};
      if (params?.checkIn) queryParams.checkIn = params.checkIn;
      if (params?.checkOut) queryParams.checkOut = params.checkOut;
      if (params?.city) queryParams.city = params.city;
      if (params?.guests) queryParams.guests = String(params.guests);
      if (params?.priceMin !== undefined && params.priceMin !== null) {
        queryParams.priceMin = String(params.priceMin);
      }
      if (params?.priceMax !== undefined && params.priceMax !== null) {
        queryParams.priceMax = String(params.priceMax);
      }
      if (params?.amenities && params.amenities.length > 0) {
        queryParams['amenities[]'] = params.amenities;
      }
      const response = await api.get('/api/accommodations', { params: queryParams });
      const data = response.data;
      return (data['hydra:member'] ?? data['member'] ?? []) as AccommodationListItem[];
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
