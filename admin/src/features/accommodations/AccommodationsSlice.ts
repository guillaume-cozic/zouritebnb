import { createAsyncThunk, createSlice } from '@reduxjs/toolkit';
import api, { extractCollection } from '../../services/api';
import type { AccommodationsState, AdminAccommodation } from './AccommodationsTypes';

export const ACCOMMODATIONS_PER_PAGE = 20;

export interface FetchAccommodationsParams {
  page?: number;
  search?: string;
  status?: string;
}

export const fetchAccommodations = createAsyncThunk<
  { items: AdminAccommodation[]; totalItems: number; page: number },
  FetchAccommodationsParams | void,
  { rejectValue: string }
>('accommodations/fetchAll', async (params, { rejectWithValue }) => {
  const { page = 1, search = '', status = '' } = params ?? {};
  try {
    const response = await api.get('/api/admin/accommodations', {
      params: {
        page,
        itemsPerPage: ACCOMMODATIONS_PER_PAGE,
        ...(search ? { search } : {}),
        ...(status ? { status } : {}),
      },
    });
    const { items, totalItems } = extractCollection<AdminAccommodation>(response.data);
    return { items, totalItems, page };
  } catch {
    return rejectWithValue('Impossible de charger les hébergements');
  }
});

const initialState: AccommodationsState = {
  items: [],
  page: 1,
  itemsPerPage: ACCOMMODATIONS_PER_PAGE,
  totalItems: 0,
  status: 'idle',
  error: null,
};

const accommodationsSlice = createSlice({
  name: 'accommodations',
  initialState,
  reducers: {},
  extraReducers: (builder) => {
    builder
      .addCase(fetchAccommodations.pending, (state) => {
        state.status = 'loading';
        state.error = null;
      })
      .addCase(fetchAccommodations.fulfilled, (state, action) => {
        state.status = 'succeeded';
        state.items = action.payload.items;
        state.totalItems = action.payload.totalItems;
        state.page = action.payload.page;
      })
      .addCase(fetchAccommodations.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload ?? 'Impossible de charger les hébergements';
      });
  },
});

export default accommodationsSlice.reducer;
