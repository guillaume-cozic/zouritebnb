import { createAsyncThunk, createSlice } from '@reduxjs/toolkit';
import api, { extractCollection } from '../../services/api';
import type {
  AdminSolidarityProject,
  CreateSolidarityProjectPayload,
  SolidarityProjectStatus,
  SolidarityProjectsState,
} from './SolidarityProjectsTypes';

export const SOLIDARITY_PROJECTS_PER_PAGE = 20;

const MERGE_PATCH = { headers: { 'Content-Type': 'application/merge-patch+json' } };

export interface FetchSolidarityProjectsParams {
  page?: number;
  search?: string;
  status?: string;
}

export const fetchSolidarityProjects = createAsyncThunk<
  { items: AdminSolidarityProject[]; totalItems: number; page: number },
  FetchSolidarityProjectsParams | void,
  { rejectValue: string }
>('solidarityProjects/fetchAll', async (params, { rejectWithValue }) => {
  const { page = 1, search = '', status = '' } = params ?? {};
  try {
    const response = await api.get('/api/admin/solidarity-projects', {
      params: {
        page,
        itemsPerPage: SOLIDARITY_PROJECTS_PER_PAGE,
        ...(search ? { search } : {}),
        ...(status ? { status } : {}),
      },
    });
    const { items, totalItems } = extractCollection<AdminSolidarityProject>(response.data);
    return { items, totalItems, page };
  } catch {
    return rejectWithValue('Impossible de charger les projets solidaires');
  }
});

export const fetchSolidarityProjectById = createAsyncThunk<
  AdminSolidarityProject,
  string,
  { rejectValue: string }
>('solidarityProjects/fetchById', async (id, { rejectWithValue }) => {
  try {
    const response = await api.get(`/api/admin/solidarity-projects/${id}`);
    return response.data as AdminSolidarityProject;
  } catch {
    return rejectWithValue('Impossible de charger le projet solidaire');
  }
});

export const createSolidarityProject = createAsyncThunk<
  void,
  CreateSolidarityProjectPayload,
  { rejectValue: string }
>('solidarityProjects/create', async (payload, { rejectWithValue }) => {
  try {
    await api.post('/api/admin/solidarity-projects', payload, {
      headers: { 'Content-Type': 'application/ld+json' },
    });
  } catch {
    return rejectWithValue('Impossible de créer le projet solidaire');
  }
});

export const updateSolidarityProject = createAsyncThunk<
  void,
  { id: string; payload: CreateSolidarityProjectPayload },
  { rejectValue: string }
>('solidarityProjects/update', async ({ id, payload }, { rejectWithValue }) => {
  try {
    await api.patch(`/api/admin/solidarity-projects/${id}`, payload, MERGE_PATCH);
  } catch {
    return rejectWithValue('Impossible de modifier le projet solidaire');
  }
});

export const setSolidarityProjectStatus = createAsyncThunk<
  void,
  { id: string; status: SolidarityProjectStatus },
  { rejectValue: string }
>('solidarityProjects/setStatus', async ({ id, status }, { rejectWithValue }) => {
  try {
    await api.patch(`/api/admin/solidarity-projects/${id}/status`, { status }, MERGE_PATCH);
  } catch {
    return rejectWithValue('Impossible de changer le statut du projet');
  }
});

export const markSolidarityProjectAsDefault = createAsyncThunk<
  void,
  { id: string },
  { rejectValue: string }
>('solidarityProjects/markDefault', async ({ id }, { rejectWithValue }) => {
  try {
    // Reuses the existing platform-curation endpoint (ROLE_ADMIN).
    await api.patch(`/api/solidarity_projects/${id}/mark-default`, {}, MERGE_PATCH);
  } catch {
    return rejectWithValue('Impossible de définir le coup de cœur');
  }
});

const initialState: SolidarityProjectsState = {
  items: [],
  page: 1,
  itemsPerPage: SOLIDARITY_PROJECTS_PER_PAGE,
  totalItems: 0,
  status: 'idle',
  error: null,
  current: null,
  currentStatus: 'idle',
  currentError: null,
  saveState: 'idle',
  saveError: null,
};

const solidarityProjectsSlice = createSlice({
  name: 'solidarityProjects',
  initialState,
  reducers: {
    saveStateReset(state) {
      state.saveState = 'idle';
      state.saveError = null;
    },
    currentCleared(state) {
      state.current = null;
      state.currentStatus = 'idle';
      state.currentError = null;
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(fetchSolidarityProjects.pending, (state) => {
        state.status = 'loading';
        state.error = null;
      })
      .addCase(fetchSolidarityProjects.fulfilled, (state, action) => {
        state.status = 'succeeded';
        state.items = action.payload.items;
        state.totalItems = action.payload.totalItems;
        state.page = action.payload.page;
      })
      .addCase(fetchSolidarityProjects.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload ?? 'Impossible de charger les projets solidaires';
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
        state.currentError = action.payload ?? 'Impossible de charger le projet solidaire';
      })
      .addCase(createSolidarityProject.pending, (state) => {
        state.saveState = 'saving';
        state.saveError = null;
      })
      .addCase(createSolidarityProject.fulfilled, (state) => {
        state.saveState = 'saved';
      })
      .addCase(createSolidarityProject.rejected, (state, action) => {
        state.saveState = 'error';
        state.saveError = action.payload ?? 'Impossible de créer le projet solidaire';
      })
      .addCase(updateSolidarityProject.pending, (state) => {
        state.saveState = 'saving';
        state.saveError = null;
      })
      .addCase(updateSolidarityProject.fulfilled, (state) => {
        state.saveState = 'saved';
      })
      .addCase(updateSolidarityProject.rejected, (state, action) => {
        state.saveState = 'error';
        state.saveError = action.payload ?? 'Impossible de modifier le projet solidaire';
      });
  },
});

export const { saveStateReset, currentCleared } = solidarityProjectsSlice.actions;
export default solidarityProjectsSlice.reducer;
