import { createAsyncThunk, createSlice } from '@reduxjs/toolkit';
import api, { extractCollection } from '../../services/api';
import type {
  ActivityPoint,
  ActivityPointsState,
  SaveActivityPointPayload,
} from './ActivityPointsTypes';

export const ACTIVITY_POINTS_PER_PAGE = 20;

const MERGE_PATCH = { headers: { 'Content-Type': 'application/merge-patch+json' } };

export interface FetchActivityPointsParams {
  page?: number;
  search?: string;
  category?: string;
}

export const fetchActivityPoints = createAsyncThunk<
  { items: ActivityPoint[]; totalItems: number; page: number },
  FetchActivityPointsParams | void,
  { rejectValue: string }
>('activityPoints/fetchAll', async (params, { rejectWithValue }) => {
  const { page = 1, search = '', category = '' } = params ?? {};
  try {
    const response = await api.get('/api/admin/activity-points', {
      params: {
        page,
        itemsPerPage: ACTIVITY_POINTS_PER_PAGE,
        ...(search ? { search } : {}),
        ...(category ? { category } : {}),
      },
    });
    const { items, totalItems } = extractCollection<ActivityPoint>(response.data);
    return { items, totalItems, page };
  } catch {
    return rejectWithValue('Impossible de charger les points de la carte');
  }
});

/**
 * Loads every point (all pages) for the map view: dragging a marker must work
 * on the full dataset, not the current table page.
 */
export const fetchAllActivityPoints = createAsyncThunk<
  ActivityPoint[],
  void,
  { rejectValue: string }
>('activityPoints/fetchAllForMap', async (_, { rejectWithValue }) => {
  try {
    const items: ActivityPoint[] = [];
    let page = 1;
    for (;;) {
      const response = await api.get('/api/admin/activity-points', {
        params: { page, itemsPerPage: 100 },
      });
      const { items: chunk, totalItems } = extractCollection<ActivityPoint>(response.data);
      items.push(...chunk);
      if (items.length >= totalItems || chunk.length === 0) return items;
      page += 1;
    }
  } catch {
    return rejectWithValue('Impossible de charger les points de la carte');
  }
});

export const fetchActivityPointById = createAsyncThunk<
  ActivityPoint,
  string,
  { rejectValue: string }
>('activityPoints/fetchById', async (id, { rejectWithValue }) => {
  try {
    const response = await api.get(`/api/admin/activity-points/${id}`);
    return response.data as ActivityPoint;
  } catch {
    return rejectWithValue('Impossible de charger le point');
  }
});

export const createActivityPoint = createAsyncThunk<
  void,
  SaveActivityPointPayload,
  { rejectValue: string }
>('activityPoints/create', async (payload, { rejectWithValue }) => {
  try {
    await api.post('/api/admin/activity-points', payload, {
      headers: { 'Content-Type': 'application/ld+json' },
    });
  } catch {
    return rejectWithValue('Impossible de créer le point');
  }
});

export const updateActivityPoint = createAsyncThunk<
  void,
  { id: string; payload: SaveActivityPointPayload },
  { rejectValue: string }
>('activityPoints/update', async ({ id, payload }, { rejectWithValue }) => {
  try {
    await api.patch(`/api/admin/activity-points/${id}`, payload, MERGE_PATCH);
  } catch {
    return rejectWithValue('Impossible de modifier le point');
  }
});

export const deleteActivityPoint = createAsyncThunk<
  void,
  { id: string },
  { rejectValue: string }
>('activityPoints/delete', async ({ id }, { rejectWithValue }) => {
  try {
    await api.delete(`/api/admin/activity-points/${id}`);
  } catch {
    return rejectWithValue('Impossible de supprimer le point');
  }
});

const initialState: ActivityPointsState = {
  items: [],
  page: 1,
  itemsPerPage: ACTIVITY_POINTS_PER_PAGE,
  totalItems: 0,
  status: 'idle',
  error: null,
  mapItems: [],
  mapStatus: 'idle',
  mapError: null,
  current: null,
  currentStatus: 'idle',
  currentError: null,
  saveState: 'idle',
  saveError: null,
};

const activityPointsSlice = createSlice({
  name: 'activityPoints',
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
      .addCase(fetchActivityPoints.pending, (state) => {
        state.status = 'loading';
        state.error = null;
      })
      .addCase(fetchActivityPoints.fulfilled, (state, action) => {
        state.status = 'succeeded';
        state.items = action.payload.items;
        state.totalItems = action.payload.totalItems;
        state.page = action.payload.page;
      })
      .addCase(fetchActivityPoints.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload ?? 'Impossible de charger les points de la carte';
      })
      .addCase(fetchAllActivityPoints.pending, (state) => {
        state.mapStatus = 'loading';
        state.mapError = null;
      })
      .addCase(fetchAllActivityPoints.fulfilled, (state, action) => {
        state.mapStatus = 'succeeded';
        state.mapItems = action.payload;
      })
      .addCase(fetchAllActivityPoints.rejected, (state, action) => {
        state.mapStatus = 'failed';
        state.mapError = action.payload ?? 'Impossible de charger les points de la carte';
      })
      .addCase(fetchActivityPointById.pending, (state) => {
        state.currentStatus = 'loading';
        state.currentError = null;
        state.current = null;
      })
      .addCase(fetchActivityPointById.fulfilled, (state, action) => {
        state.currentStatus = 'succeeded';
        state.current = action.payload;
      })
      .addCase(fetchActivityPointById.rejected, (state, action) => {
        state.currentStatus = 'failed';
        state.currentError = action.payload ?? 'Impossible de charger le point';
      })
      .addCase(createActivityPoint.pending, (state) => {
        state.saveState = 'saving';
        state.saveError = null;
      })
      .addCase(createActivityPoint.fulfilled, (state) => {
        state.saveState = 'saved';
      })
      .addCase(createActivityPoint.rejected, (state, action) => {
        state.saveState = 'error';
        state.saveError = action.payload ?? 'Impossible de créer le point';
      })
      .addCase(updateActivityPoint.pending, (state) => {
        state.saveState = 'saving';
        state.saveError = null;
      })
      .addCase(updateActivityPoint.fulfilled, (state, action) => {
        state.saveState = 'saved';
        // Sync both lists so the map (and table) reflect the saved position
        // without a refetch.
        const { id, payload } = action.meta.arg;
        const apply = (point: ActivityPoint) =>
          point.id === id ? { ...point, ...payload } : point;
        state.items = state.items.map(apply);
        state.mapItems = state.mapItems.map(apply);
      })
      .addCase(updateActivityPoint.rejected, (state, action) => {
        state.saveState = 'error';
        state.saveError = action.payload ?? 'Impossible de modifier le point';
      })
      .addCase(deleteActivityPoint.rejected, (state, action) => {
        state.error = action.payload ?? 'Impossible de supprimer le point';
      });
  },
});

export const { saveStateReset, currentCleared } = activityPointsSlice.actions;
export default activityPointsSlice.reducer;
