import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import i18n from '../../i18n';
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
  /** Langue de la liste chargée (ou en cours) — sert à dédupliquer les fetchs. */
  language: string | null;
}

const initialState: SolidarityProjectState = {
  items: [],
  current: null,
  status: 'idle',
  currentStatus: 'idle',
  error: null,
  currentError: null,
  language: null,
};

export const fetchSolidarityProjects = createAsyncThunk(
  'solidarityProject/fetchAll',
  async (_, { rejectWithValue }) => {
    try {
      const response = await api.get('/api/solidarity_projects', {
        headers: { 'Accept-Language': i18n.language },
      });
      const data = response.data;
      return (data['hydra:member'] ?? data['member'] ?? []) as SolidarityProject[];
    } catch (err) {
      return rejectWithValue(
        extractErrorMessage(err, 'Erreur lors du chargement des projets solidaires')
      );
    }
  },
  {
    // Plusieurs composants de l'accueil (hero + section projets) demandent la
    // liste : ne (re)partir en requête que si elle n'est ni chargée ni en cours
    // de chargement dans la langue courante.
    condition: (_, { getState }) => {
      const { status, language } = (getState() as { solidarityProject: SolidarityProjectState })
        .solidarityProject;

      return !(
        (status === 'loading' || status === 'succeeded')
        && language === i18n.language
      );
    },
  }
);

export const fetchSolidarityProjectById = createAsyncThunk(
  'solidarityProject/fetchById',
  async (id: string, { rejectWithValue }) => {
    try {
      const response = await api.get(`/api/solidarity_projects/${id}`, {
        headers: { 'Accept-Language': i18n.language },
      });
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
        state.language = i18n.language;
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
