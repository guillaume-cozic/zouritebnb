import { createSlice, createAsyncThunk, PayloadAction } from '@reduxjs/toolkit';
import api from '../../services/api';
import { ManagedAccommodation, StatusFilter } from './AccommodationManagementTypes';

interface AccommodationManagementState {
  items: ManagedAccommodation[];
  statusFilter: StatusFilter;
  status: 'idle' | 'loading' | 'succeeded' | 'failed';
  error: string | null;
}

const initialState: AccommodationManagementState = {
  items: [],
  statusFilter: 'all',
  status: 'idle',
  error: null,
};

export const fetchAllAccommodations = createAsyncThunk(
  'accommodationManagement/fetchAll',
  async (statusFilter: StatusFilter, { rejectWithValue }) => {
    try {
      const response = await api.get('/api/accommodations', {
        params: { status: statusFilter },
      });
      const data = response.data;
      return (data['hydra:member'] ?? data['member'] ?? []) as ManagedAccommodation[];
    } catch (err: any) {
      return rejectWithValue(
        err.response?.data?.detail || 'Erreur lors du chargement des hébergements'
      );
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
    } catch (err: any) {
      return rejectWithValue(
        err.response?.data?.detail || 'Erreur lors de la publication'
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
    } catch (err: any) {
      return rejectWithValue(
        err.response?.data?.detail || 'Erreur lors de la dépublication'
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
      })
      .addCase(fetchAllAccommodations.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload as string;
      })
      .addCase(publishAccommodation.fulfilled, (state, action) => {
        const item = state.items.find((a) => a.id === action.payload);
        if (item) item.status = 'published';
      })
      .addCase(unpublishAccommodation.fulfilled, (state, action) => {
        const item = state.items.find((a) => a.id === action.payload);
        if (item) item.status = 'draft';
      });
  },
});

export const { setStatusFilter } = accommodationManagementSlice.actions;
export default accommodationManagementSlice.reducer;
