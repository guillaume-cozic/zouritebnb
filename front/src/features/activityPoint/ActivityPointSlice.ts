import { createAsyncThunk, createSlice } from '@reduxjs/toolkit';
import api from '../../services/api';
import { extractErrorMessage } from '../../services/errors';
import type { RootState } from '../../store';
import { ActivityPoint } from './ActivityPointTypes';

interface ActivityPointState {
  items: ActivityPoint[];
  status: 'idle' | 'loading' | 'succeeded' | 'failed';
  error: string | null;
}

const initialState: ActivityPointState = {
  items: [],
  status: 'idle',
  error: null,
};

export const fetchActivityPoints = createAsyncThunk<ActivityPoint[]>(
  'activityPoint/fetchAll',
  async (_, { rejectWithValue }) => {
    try {
      const response = await api.get('/api/activity-points');
      const data = response.data;
      return (data['hydra:member'] ?? data['member'] ?? []) as ActivityPoint[];
    } catch (err) {
      return rejectWithValue(
        extractErrorMessage(err, 'Erreur lors du chargement des points de la carte')
      );
    }
  },
  {
    // Fetch once: skip when a request is in flight or the points are loaded.
    condition: (_, { getState }) => {
      const { status } = (getState() as RootState).activityPoint;
      return status === 'idle' || status === 'failed';
    },
  }
);

const activityPointSlice = createSlice({
  name: 'activityPoint',
  initialState,
  reducers: {},
  extraReducers: (builder) => {
    builder
      .addCase(fetchActivityPoints.pending, (state) => {
        state.status = 'loading';
        state.error = null;
      })
      .addCase(fetchActivityPoints.fulfilled, (state, action) => {
        state.status = 'succeeded';
        state.items = action.payload;
      })
      .addCase(fetchActivityPoints.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload as string;
      });
  },
});

export default activityPointSlice.reducer;
