import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import api from '../../services/api';
import { extractErrorMessage } from '../../services/errors';
import { Locality, Region } from './GeographyTypes';

interface GeographyState {
  localities: Locality[];
  regions: Region[];
  status: 'idle' | 'loading' | 'succeeded' | 'failed';
  error: string | null;
}

const initialState: GeographyState = {
  localities: [],
  regions: [],
  status: 'idle',
  error: null,
};

export const fetchLocalities = createAsyncThunk<Locality[], string | undefined>(
  'geography/fetchLocalities',
  async (regionCode, { rejectWithValue }) => {
    try {
      const queryParams: Record<string, string> = {};
      if (regionCode) queryParams.regionCode = regionCode;
      const response = await api.get('/api/localities', { params: queryParams });
      const data = response.data;
      return (data['hydra:member'] ?? data['member'] ?? []) as Locality[];
    } catch (err) {
      return rejectWithValue(
        extractErrorMessage(err, 'Erreur lors du chargement des localités')
      );
    }
  }
);

export const fetchRegions = createAsyncThunk<Region[], void>(
  'geography/fetchRegions',
  async (_, { rejectWithValue }) => {
    try {
      const response = await api.get('/api/regions');
      const data = response.data;
      return (data['hydra:member'] ?? data['member'] ?? []) as Region[];
    } catch (err) {
      return rejectWithValue(
        extractErrorMessage(err, 'Erreur lors du chargement des régions')
      );
    }
  }
);

const geographySlice = createSlice({
  name: 'geography',
  initialState,
  reducers: {},
  extraReducers: (builder) => {
    builder
      .addCase(fetchLocalities.pending, (state) => {
        state.status = 'loading';
        state.error = null;
      })
      .addCase(fetchLocalities.fulfilled, (state, action) => {
        state.status = 'succeeded';
        state.localities = action.payload;
      })
      .addCase(fetchLocalities.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload as string;
      })
      .addCase(fetchRegions.pending, (state) => {
        state.status = 'loading';
        state.error = null;
      })
      .addCase(fetchRegions.fulfilled, (state, action) => {
        state.status = 'succeeded';
        state.regions = action.payload;
      })
      .addCase(fetchRegions.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload as string;
      });
  },
});

export default geographySlice.reducer;
