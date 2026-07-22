import { createSlice, createAsyncThunk, PayloadAction } from '@reduxjs/toolkit';
import api from '../../services/api';
import { extractErrorMessage } from '../../services/errors';
import { ManagedAccommodation, StatusFilter } from './AccommodationManagementTypes';
import { createAccommodation } from '../accommodation/AccommodationSlice';

interface AccommodationManagementState {
  items: ManagedAccommodation[];
  statusFilter: StatusFilter;
  status: 'idle' | 'loading' | 'succeeded' | 'failed';
  error: string | null;
  // Whether the host owns at least one accommodation (any status). `null` = not resolved yet.
  // Tracked separately from `items` so an active status filter can never make it a false negative.
  hasAccommodation: boolean | null;
  ownershipStatus: 'idle' | 'loading' | 'succeeded' | 'failed';
  // Message returned when a publish attempt is rejected (e.g. the listing is still incomplete).
  // Kept separate from `error` so it never hides the accommodations table.
  publishError: string | null;
}

const initialState: AccommodationManagementState = {
  items: [],
  statusFilter: 'all',
  status: 'idle',
  error: null,
  hasAccommodation: null,
  ownershipStatus: 'idle',
  publishError: null,
};

export const fetchAllAccommodations = createAsyncThunk(
  'accommodationManagement/fetchAll',
  async (statusFilter: StatusFilter, { rejectWithValue }) => {
    try {
      const response = await api.get('/api/my-accommodations', {
        params: { status: statusFilter },
      });
      const data = response.data;
      return (data['hydra:member'] ?? data['member'] ?? []) as ManagedAccommodation[];
    } catch (err) {
      return rejectWithValue(
        extractErrorMessage(err, 'Erreur lors du chargement des hébergements')
      );
    }
  }
);

/**
 * Lightweight check used to gate the host back-office: does the current team own at
 * least one accommodation? Kept independent from `fetchAllAccommodations` so the status
 * filter on the management page never interferes with the gate.
 */
export const fetchOwnsAccommodation = createAsyncThunk(
  'accommodationManagement/fetchOwnsAccommodation',
  async (_: void, { rejectWithValue }) => {
    try {
      const response = await api.get('/api/my-accommodations', {
        params: { status: 'all', itemsPerPage: 1 },
      });
      const data = response.data;
      const total = data['hydra:totalItems'] ?? data['totalItems'];
      if (typeof total === 'number') return total > 0;
      const members = (data['hydra:member'] ?? data['member'] ?? []) as unknown[];
      return members.length > 0;
    } catch (err) {
      return rejectWithValue(extractErrorMessage(err, 'ownership check failed'));
    }
  }
);

export const publishAccommodation = createAsyncThunk(
  'accommodationManagement/publish',
  async (id: string, { rejectWithValue }) => {
    try {
      await api.patch(
        `/api/accommodations/${id}/publish`,
        {},
        { headers: { 'Content-Type': 'application/merge-patch+json' } }
      );
      return id;
    } catch (err) {
      return rejectWithValue(
        extractErrorMessage(err, 'Erreur lors de la publication')
      );
    }
  }
);

export const unpublishAccommodation = createAsyncThunk(
  'accommodationManagement/unpublish',
  async (id: string, { rejectWithValue }) => {
    try {
      await api.patch(
        `/api/accommodations/${id}/unpublish`,
        {},
        { headers: { 'Content-Type': 'application/merge-patch+json' } }
      );
      return id;
    } catch (err) {
      return rejectWithValue(
        extractErrorMessage(err, 'Erreur lors de la dépublication')
      );
    }
  }
);

const accommodationManagementSlice = createSlice({
  name: 'accommodationManagement',
  initialState,
  reducers: {
    setStatusFilter(state, action: PayloadAction<StatusFilter>) {
      state.statusFilter = action.payload;
    },
    dismissPublishError(state) {
      state.publishError = null;
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(fetchAllAccommodations.pending, (state) => {
        state.status = 'loading';
        state.error = null;
      })
      .addCase(fetchAllAccommodations.fulfilled, (state, action) => {
        state.status = 'succeeded';
        state.items = action.payload;
        // Keep the ownership gate fresh, but only from an unfiltered ('all') fetch.
        if (action.meta.arg === 'all') {
          state.hasAccommodation = action.payload.length > 0;
          state.ownershipStatus = 'succeeded';
        }
      })
      .addCase(fetchOwnsAccommodation.pending, (state) => {
        state.ownershipStatus = 'loading';
      })
      .addCase(fetchOwnsAccommodation.fulfilled, (state, action) => {
        state.ownershipStatus = 'succeeded';
        state.hasAccommodation = action.payload;
      })
      .addCase(fetchOwnsAccommodation.rejected, (state) => {
        // Fail open: a transient error must not lock a host out of their back-office.
        state.ownershipStatus = 'failed';
        state.hasAccommodation = true;
      })
      .addCase(fetchAllAccommodations.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload as string;
      })
      .addCase(publishAccommodation.pending, (state) => {
        state.publishError = null;
      })
      .addCase(publishAccommodation.fulfilled, (state, action) => {
        state.publishError = null;
        const item = state.items.find((a) => a.id === action.payload);
        if (item) item.status = 'published';
      })
      .addCase(publishAccommodation.rejected, (state, action) => {
        state.publishError = action.payload as string;
      })
      .addCase(unpublishAccommodation.fulfilled, (state, action) => {
        const item = state.items.find((a) => a.id === action.payload);
        if (item) item.status = 'draft';
      })
      // Creating a listing immediately opens the back-office gate, without waiting for a refetch.
      .addCase(createAccommodation.fulfilled, (state) => {
        state.hasAccommodation = true;
        state.ownershipStatus = 'succeeded';
      });
  },
});

export const { setStatusFilter, dismissPublishError } = accommodationManagementSlice.actions;
export default accommodationManagementSlice.reducer;
