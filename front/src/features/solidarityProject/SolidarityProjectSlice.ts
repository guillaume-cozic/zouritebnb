import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import api from '../../services/api';
import { extractErrorMessage } from '../../services/errors';
import { SolidarityProject } from './SolidarityProjectTypes';

interface SolidarityProjectState {
  items: SolidarityProject[];
  current: SolidarityProject | null;
  status: 'idle' | 'loading' | 'succeeded' | 'failed';
  currentStatus: 'idle' | 'loading' | 'succeeded' | 'failed';
  error: string | null;
  currentError: string | null;
}

const initialState: SolidarityProjectState = {
  items: [],
  current: null,
  status: 'idle',
  currentStatus: 'idle',
  error: null,
  currentError: null,
};

export const fetchSolidarityProjects = createAsyncThunk(
  'solidarityProject/fetchAll',
  async (_, { rejectWithValue }) => {
    try {
      const response = await api.get('/api/solidarity_projects');
      const data = response.data;
      return (data['hydra:member'] ?? data['member'] ?? []) as SolidarityProject[];
    } catch (err) {
      return rejectWithValue(
        extractErrorMessage(err, 'Erreur lors du chargement des projets solidaires')
      );
    }
  }
);

export const fetchSolidarityProjectById = createAsyncThunk(
  'solidarityProject/fetchById',
  async (id: string, { rejectWithValue }) => {
    try {
      const response = await api.get(`/api/solidarity_projects/${id}`);
      return response.data as SolidarityProject;
    } catch (err) {
      return rejectWithValue(
        extractErrorMessage(err, 'Erreur lors du chargement du projet')
      );
    }
  }
);

const solidarityProjectSlice = createSlice({
  name: 'solidarityProject',
  initialState,
  reducers: {},
  extraReducers: (builder) => {
    builder
      .addCase(fetchSolidarityProjects.pending, (state) => {
        state.status = 'loading';
        state.error = null;
      })
      .addCase(fetchSolidarityProjects.fulfilled, (state, action) => {
        state.status = 'succeeded';
        state.items = action.payload;
      })
      .addCase(fetchSolidarityProjects.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload as string;
      })
      .addCase(fetchSolidarityProjectById.pending, (state) => {
        state.currentStatus = 'loading';
        state.currentError = null;
        state.current = null;
      })
      .addCase(fetchSolidarityProjectById.fulfilled, (state, action) => {
        state.currentStatus = 'succeeded';
        state.current = action.payload;
      })
      .addCase(fetchSolidarityProjectById.rejected, (state, action) => {
        state.currentStatus = 'failed';
        state.currentError = action.payload as string;
      });
  },
});

export default solidarityProjectSlice.reducer;
