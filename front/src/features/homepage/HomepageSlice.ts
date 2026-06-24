import { createSlice, createAsyncThunk, createAction, PayloadAction } from '@reduxjs/toolkit';
import api from '../../services/api';
import { extractErrorMessage } from '../../services/errors';
import type { RootState } from '../../store';
import { AccommodationListItem, SearchFilters } from './HomepageTypes';

export const ITEMS_PER_PAGE = 12;

interface HomepageState {
  accommodations: AccommodationListItem[];
  filters: SearchFilters;
  page: number;
  totalItems: number;
  status: 'idle' | 'loading' | 'succeeded' | 'failed';
  loadingMore: boolean;
  error: string | null;
}

const initialState: HomepageState = {
  accommodations: [],
  filters: { city: '', checkIn: '', checkOut: '', guests: 1, amenities: [], priceMin: null, priceMax: null },
  page: 1,
  totalItems: 0,
  status: 'idle',
  loadingMore: false,
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
  page?: number;
}

interface FetchPublishedResult {
  items: AccommodationListItem[];
  totalItems: number;
  page: number;
}

/**
 * Intent dispatched by the component when the user reaches the bottom of the list.
 * The HomepageListeners middleware decides whether/what to fetch next.
 */
export const nextPageRequested = createAction('homepage/nextPageRequested');

export const fetchPublishedAccommodations = createAsyncThunk<
  FetchPublishedResult,
  FetchPublishedParams | void
>(
  'homepage/fetchPublished',
  async (params, { rejectWithValue }) => {
    try {
      const page = params?.page ?? 1;
      const queryParams: Record<string, string | string[]> = {
        page: String(page),
        itemsPerPage: String(ITEMS_PER_PAGE),
      };
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
      const items = (data['hydra:member'] ?? data['member'] ?? []) as AccommodationListItem[];
      const totalItems = (data['hydra:totalItems'] ?? data['totalItems'] ?? items.length) as number;
      return { items, totalItems, page };
    } catch (err) {
      return rejectWithValue(
        extractErrorMessage(err, 'Erreur lors du chargement des hébergements')
      );
    }
  }
);

export const fetchHomepageFeatured = createAsyncThunk<AccommodationListItem[]>(
  'homepage/fetchFeatured',
  async (_, { rejectWithValue }) => {
    try {
      const response = await api.get('/api/accommodations', { params: { itemsPerPage: '9' } });
      const data = response.data;
      return (data['hydra:member'] ?? data['member'] ?? []) as AccommodationListItem[];
    } catch (err) {
      return rejectWithValue(
        extractErrorMessage(err, 'Erreur lors du chargement des hébergements')
      );
    }
  },
  {
    // Déduplique les appels concurrents : on ne relance pas tant qu'une requête
    // est déjà en vol. Évite les appels redondants dus au double-montage
    // StrictMode et aux remontages du composant pendant le chargement.
    condition: (_, { getState }) => {
      const { status } = (getState() as RootState).homepage;
      return status !== 'loading';
    },
  }
);

const homepageSlice = createSlice({
  name: 'homepage',
  initialState,
  reducers: {
    setFilters(state, action: PayloadAction<Partial<SearchFilters>>) {
      Object.assign(state.filters, action.payload);
      // Changing the filters resets the paginated, accumulated result set.
      state.page = 1;
      state.totalItems = 0;
      state.accommodations = [];
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(fetchPublishedAccommodations.pending, (state, action) => {
        const page = action.meta.arg?.page ?? 1;
        if (page > 1) {
          state.loadingMore = true;
        } else {
          state.status = 'loading';
        }
        state.error = null;
      })
      .addCase(fetchPublishedAccommodations.fulfilled, (state, action) => {
        const { items, totalItems, page } = action.payload;
        state.status = 'succeeded';
        state.loadingMore = false;
        state.totalItems = totalItems;
        state.page = page;
        if (page > 1) {
          state.accommodations = [...state.accommodations, ...items];
        } else {
          state.accommodations = items;
        }
      })
      .addCase(fetchPublishedAccommodations.rejected, (state, action) => {
        const page = action.meta.arg?.page ?? 1;
        state.loadingMore = false;
        if (page <= 1) {
          state.status = 'failed';
        }
        state.error = action.payload as string;
      })
      .addCase(fetchHomepageFeatured.pending, (state) => {
        state.status = 'loading';
        state.error = null;
      })
      .addCase(fetchHomepageFeatured.fulfilled, (state, action) => {
        state.status = 'succeeded';
        state.accommodations = action.payload;
      })
      .addCase(fetchHomepageFeatured.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload as string;
      });
  },
});

export const { setFilters } = homepageSlice.actions;
export default homepageSlice.reducer;
