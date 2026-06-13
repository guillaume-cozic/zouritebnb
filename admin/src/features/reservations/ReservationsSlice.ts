import { createAsyncThunk, createSlice } from '@reduxjs/toolkit';
import api, { extractCollection } from '../../services/api';
import type { AdminReservation, ReservationsState } from './ReservationsTypes';

export const RESERVATIONS_PER_PAGE = 20;

export interface FetchReservationsParams {
  page?: number;
  search?: string;
  status?: string;
}

export const fetchReservations = createAsyncThunk<
  { items: AdminReservation[]; totalItems: number; page: number },
  FetchReservationsParams | void,
  { rejectValue: string }
>('reservations/fetchAll', async (params, { rejectWithValue }) => {
  const { page = 1, search = '', status = '' } = params ?? {};
  try {
    const response = await api.get('/api/admin/reservations', {
      params: {
        page,
        itemsPerPage: RESERVATIONS_PER_PAGE,
        ...(search ? { search } : {}),
        ...(status ? { status } : {}),
      },
    });
    const { items, totalItems } = extractCollection<AdminReservation>(response.data);
    return { items, totalItems, page };
  } catch {
    return rejectWithValue('Impossible de charger les réservations');
  }
});

const initialState: ReservationsState = {
  items: [],
  page: 1,
  itemsPerPage: RESERVATIONS_PER_PAGE,
  totalItems: 0,
  status: 'idle',
  error: null,
};

const reservationsSlice = createSlice({
  name: 'reservations',
  initialState,
  reducers: {},
  extraReducers: (builder) => {
    builder
      .addCase(fetchReservations.pending, (state) => {
        state.status = 'loading';
        state.error = null;
      })
      .addCase(fetchReservations.fulfilled, (state, action) => {
        state.status = 'succeeded';
        state.items = action.payload.items;
        state.totalItems = action.payload.totalItems;
        state.page = action.payload.page;
      })
      .addCase(fetchReservations.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload ?? 'Impossible de charger les réservations';
      });
  },
});

export default reservationsSlice.reducer;
