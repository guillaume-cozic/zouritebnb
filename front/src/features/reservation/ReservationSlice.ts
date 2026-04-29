import { createSlice, createAsyncThunk, createAction } from '@reduxjs/toolkit';
import api from '../../services/api';
import {
  CreateReservationPayload,
  FetchReservationsParams,
  Reservation,
} from './ReservationTypes';

export const reservationModalOpened = createAction<{ accommodationId?: string }>(
  'reservation/modalOpened'
);

type Status = 'idle' | 'loading' | 'succeeded' | 'failed';

interface ReservationState {
  items: Reservation[];
  status: Status;
  error: string | null;
  mutationStatus: Status;
  mutationError: string | null;
}

const initialState: ReservationState = {
  items: [],
  status: 'idle',
  error: null,
  mutationStatus: 'idle',
  mutationError: null,
};

export const fetchReservations = createAsyncThunk(
  'reservation/fetchAll',
  async (params: FetchReservationsParams = {}, { rejectWithValue }) => {
    try {
      const query: Record<string, string> = {};
      if (params.accommodationId) query.accommodationId = params.accommodationId;
      if (params.from) query.from = params.from;
      if (params.to) query.to = params.to;
      const response = await api.get('/api/reservations', { params: query });
      const data = response.data;
      return (data['hydra:member'] ?? data['member'] ?? []) as Reservation[];
    } catch (err: any) {
      return rejectWithValue(
        err.response?.data?.detail || 'Erreur lors du chargement des réservations'
      );
    }
  }
);

export const createReservation = createAsyncThunk(
  'reservation/create',
  async (payload: CreateReservationPayload, { rejectWithValue }) => {
    try {
      const response = await api.post('/api/reservations', payload, {
        headers: { 'Content-Type': 'application/ld+json' },
      });
      return response.data as Reservation;
    } catch (err: any) {
      return rejectWithValue(
        err.response?.data?.detail || 'Erreur lors de la création de la réservation'
      );
    }
  }
);

export const confirmReservation = createAsyncThunk(
  'reservation/confirm',
  async (id: string, { rejectWithValue }) => {
    try {
      const response = await api.patch(
        `/api/reservations/${id}/confirm`,
        {},
        { headers: { 'Content-Type': 'application/merge-patch+json' } }
      );
      return response.data as Reservation;
    } catch (err: any) {
      return rejectWithValue(
        err.response?.data?.detail || 'Erreur lors de la confirmation'
      );
    }
  }
);

export const cancelReservation = createAsyncThunk(
  'reservation/cancel',
  async (id: string, { rejectWithValue }) => {
    try {
      const response = await api.patch(
        `/api/reservations/${id}/cancel`,
        {},
        { headers: { 'Content-Type': 'application/merge-patch+json' } }
      );
      return response.data as Reservation;
    } catch (err: any) {
      return rejectWithValue(
        err.response?.data?.detail || 'Erreur lors de l\'annulation'
      );
    }
  }
);

const reservationSlice = createSlice({
  name: 'reservation',
  initialState,
  reducers: {
    clearMutationError(state) {
      state.mutationError = null;
      state.mutationStatus = 'idle';
    },
  },
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
        state.error = (action.payload as string) || action.error.message || null;
      })
      .addCase(createReservation.pending, (state) => {
        state.mutationStatus = 'loading';
        state.mutationError = null;
      })
      .addCase(createReservation.fulfilled, (state, action) => {
        state.mutationStatus = 'succeeded';
        state.items.push(action.payload);
      })
      .addCase(createReservation.rejected, (state, action) => {
        state.mutationStatus = 'failed';
        state.mutationError = (action.payload as string) || null;
      })
      .addCase(confirmReservation.fulfilled, (state, action) => {
        const idx = state.items.findIndex((r) => r.id === action.payload.id);
        if (idx >= 0) state.items[idx] = action.payload;
      })
      .addCase(confirmReservation.rejected, (state, action) => {
        state.mutationError = (action.payload as string) || null;
      })
      .addCase(cancelReservation.fulfilled, (state, action) => {
        const idx = state.items.findIndex((r) => r.id === action.payload.id);
        if (idx >= 0) state.items[idx] = action.payload;
      })
      .addCase(cancelReservation.rejected, (state, action) => {
        state.mutationError = (action.payload as string) || null;
      })
      .addCase(reservationModalOpened, (state) => {
        state.mutationError = null;
        state.mutationStatus = 'idle';
      });
  },
});

export const { clearMutationError } = reservationSlice.actions;
export default reservationSlice.reducer;
