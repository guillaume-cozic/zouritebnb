import { createAsyncThunk, createSlice } from '@reduxjs/toolkit';
import api, { extractMembers } from '../../services/api';
import type { AccommodationsState, AdminAccommodation } from './AccommodationsTypes';

export const fetchAccommodations = createAsyncThunk<
  AdminAccommodation[],
  void,
  { rejectValue: string }
>('accommodations/fetchAll', async (_, { rejectWithValue }) => {
  try {
    const response = await api.get('/api/admin/accommodations');
    return extractMembers<AdminAccommodation>(response.data);
  } catch {
    return rejectWithValue('Impossible de charger les hébergements');
  }
});

const initialState: AccommodationsState = {
  items: [],
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
        state.items = action.payload;
      })
      .addCase(fetchAccommodations.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload ?? 'Impossible de charger les hébergements';
      });
  },
});

export default accommodationsSlice.reducer;
