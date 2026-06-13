import { createAsyncThunk, createSlice } from '@reduxjs/toolkit';
import api from '../../services/api';
import type { AdminDashboard, DashboardState } from './DashboardTypes';

export const fetchDashboard = createAsyncThunk<AdminDashboard, void, { rejectValue: string }>(
  'dashboard/fetch',
  async (_, { rejectWithValue }) => {
    try {
      const response = await api.get('/api/admin/dashboard');
      return response.data as AdminDashboard;
    } catch {
      return rejectWithValue('Impossible de charger les indicateurs financiers');
    }
  }
);

const initialState: DashboardState = {
  data: null,
  status: 'idle',
  error: null,
};

const dashboardSlice = createSlice({
  name: 'dashboard',
  initialState,
  reducers: {},
  extraReducers: (builder) => {
    builder
      .addCase(fetchDashboard.pending, (state) => {
        state.status = 'loading';
        state.error = null;
      })
      .addCase(fetchDashboard.fulfilled, (state, action) => {
        state.status = 'succeeded';
        state.data = action.payload;
      })
      .addCase(fetchDashboard.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload ?? 'Impossible de charger les indicateurs financiers';
      });
  },
});

export default dashboardSlice.reducer;
