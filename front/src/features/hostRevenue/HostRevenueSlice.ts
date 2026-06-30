import { createSlice, createAsyncThunk, createAction } from '@reduxjs/toolkit';
import api from '../../services/api';
import { extractErrorMessage } from '../../services/errors';
import { HostRevenue } from './HostRevenueTypes';

/** Fired when the host opens the revenue dashboard; the listener loads the data. */
export const revenuePageOpened = createAction('hostRevenue/pageOpened');

type Status = 'idle' | 'loading' | 'succeeded' | 'failed';

interface HostRevenueState {
  data: HostRevenue | null;
  status: Status;
  error: string | null;
}

const initialState: HostRevenueState = {
  data: null,
  status: 'idle',
  error: null,
};

export const fetchHostRevenue = createAsyncThunk(
  'hostRevenue/fetch',
  async (_: void, { rejectWithValue }) => {
    try {
      const response = await api.get('/api/host/revenue');
      return response.data as HostRevenue;
    } catch (err) {
      return rejectWithValue(
        extractErrorMessage(err, 'Erreur lors du chargement des revenus')
      );
    }
  }
);

const hostRevenueSlice = createSlice({
  name: 'hostRevenue',
  initialState,
  reducers: {},
  extraReducers: (builder) => {
    builder
      .addCase(fetchHostRevenue.pending, (state) => {
        state.status = 'loading';
        state.error = null;
      })
      .addCase(fetchHostRevenue.fulfilled, (state, action) => {
        state.status = 'succeeded';
        state.data = action.payload;
      })
      .addCase(fetchHostRevenue.rejected, (state, action) => {
        state.status = 'failed';
        state.error = (action.payload as string) ?? 'Erreur inconnue';
      });
  },
});

export default hostRevenueSlice.reducer;
