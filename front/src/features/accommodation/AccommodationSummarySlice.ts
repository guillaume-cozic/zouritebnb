import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import api from '../../services/api';
import { RootState } from '../../store';

/** Minimal accommodation info (name + city) needed to label a conversation. */
export interface AccommodationSummary {
  id: string;
  title: string | null;
  city: string | null;
}

interface AccommodationSummaryState {
  byId: Record<string, AccommodationSummary>;
}

const initialState: AccommodationSummaryState = {
  byId: {},
};

/**
 * Fetch a lightweight summary (title + city) for a single accommodation, used to
 * label conversations in the traveler inbox. Cached by id: a no-op when already
 * loaded, so dispatching it per conversation costs at most one request each.
 */
export const fetchAccommodationSummary = createAsyncThunk(
  'accommodationSummary/fetchOne',
  async (id: string, { getState }) => {
    if ((getState() as RootState).accommodationSummary.byId[id]) return null;
    const response = await api.get(`/api/accommodations/${id}`);
    const data = response.data;
    return { id, title: data.title ?? null, city: data.city ?? null };
  }
);

const accommodationSummarySlice = createSlice({
  name: 'accommodationSummary',
  initialState,
  reducers: {},
  extraReducers: (builder) => {
    builder.addCase(fetchAccommodationSummary.fulfilled, (state, action) => {
      if (action.payload) state.byId[action.payload.id] = action.payload;
    });
  },
});

export default accommodationSummarySlice.reducer;
