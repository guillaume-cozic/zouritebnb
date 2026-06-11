import { createAsyncThunk, createSlice } from '@reduxjs/toolkit';
import api, { extractMembers } from '../../services/api';
import type { AdminReservation, ReservationsState } from './ReservationsTypes';

export const fetchReservations = createAsyncThunk<
  AdminReservation[],
  void,
  { rejectValue: string }
>('reservations/fetchAll', async (_, { rejectWithValue }) => {
  try {
    const response = await api.get('/api/admin/reservations');
    return extractMembers<AdminReservation>(response.data);
  } catch {
    return rejectWithValue('Impossible de charger les réservations');
  }
});

const initialState: ReservationsState = {
  items: [],
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
        state.items = action.payload;
      })
      .addCase(fetchReservations.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload ?? 'Impossible de charger les réservations';
      });
  },
});

export default reservationsSlice.reducer;
