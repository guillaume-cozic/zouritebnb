import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import api from '../../services/api';
import { Team } from './TeamTypes';

interface TeamState {
  current: Team | null;
  status: 'idle' | 'loading' | 'succeeded' | 'failed';
  error: string | null;
}

const initialState: TeamState = {
  current: null,
  status: 'idle',
  error: null,
};

export const fetchTeam = createAsyncThunk(
  'team/fetch',
  async (id: string, { rejectWithValue }) => {
    try {
      const response = await api.get(`/api/teams/${id}`);
      return response.data as Team;
    } catch (err: any) {
      return rejectWithValue(err.response?.data?.detail || 'Erreur lors du chargement de l\'équipe');
    }
  }
);

export const updateTeamFavoriteProject = createAsyncThunk(
  'team/updateFavoriteProject',
  async ({ id, favoriteSolidarityProjectId }: { id: string; favoriteSolidarityProjectId: string | null }, { rejectWithValue }) => {
    try {
      await api.patch(
        `/api/teams/${id}/favorite-solidarity-project`,
        { favoriteSolidarityProjectId },
        { headers: { 'Content-Type': 'application/merge-patch+json' } }
      );
      return { favoriteSolidarityProjectId };
    } catch (err: any) {
      return rejectWithValue(err.response?.data?.detail || 'Erreur lors de la mise à jour');
    }
  }
);

const teamSlice = createSlice({
  name: 'team',
  initialState,
  reducers: {},
  extraReducers: (builder) => {
    builder
      .addCase(fetchTeam.pending, (state) => {
        state.status = 'loading';
        state.error = null;
      })
      .addCase(fetchTeam.fulfilled, (state, action) => {
        state.status = 'succeeded';
        state.current = action.payload;
      })
      .addCase(fetchTeam.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload as string;
      })
      .addCase(updateTeamFavoriteProject.fulfilled, (state, action) => {
        if (state.current) {
          state.current.favoriteSolidarityProjectId = action.payload.favoriteSolidarityProjectId;
        }
      });
  },
});

export default teamSlice.reducer;
