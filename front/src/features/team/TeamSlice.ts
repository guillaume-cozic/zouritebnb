import { createSlice, createAsyncThunk, createAction } from '@reduxjs/toolkit';
import api from '../../services/api';
import { Team, TeamInvitation } from './TeamTypes';

export const teamSettingsPageOpened = createAction<{ teamId: string | null }>(
  'team/settingsPageOpened'
);

interface TeamState {
  current: Team | null;
  status: 'idle' | 'loading' | 'succeeded' | 'failed';
  error: string | null;
  invitations: TeamInvitation[];
  invitationsStatus: 'idle' | 'loading' | 'succeeded' | 'failed';
  inviteStatus: 'idle' | 'loading' | 'succeeded' | 'failed';
  inviteError: string | null;
}

const initialState: TeamState = {
  current: null,
  status: 'idle',
  error: null,
  invitations: [],
  invitationsStatus: 'idle',
  inviteStatus: 'idle',
  inviteError: null,
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

export const fetchTeamInvitations = createAsyncThunk(
  'team/fetchInvitations',
  async (teamId: string, { rejectWithValue }) => {
    try {
      const response = await api.get(`/api/teams/${teamId}/invitations`);
      const data = response.data;
      const items: TeamInvitation[] = data['hydra:member'] ?? data.member ?? data ?? [];
      return items;
    } catch (err: any) {
      return rejectWithValue(err.response?.data?.detail || 'Erreur lors du chargement des invitations');
    }
  }
);

export const cancelTeamInvitation = createAsyncThunk(
  'team/cancelInvitation',
  async (invitationId: string, { rejectWithValue }) => {
    try {
      await api.delete(`/api/team-invitations/${invitationId}`);
      return invitationId;
    } catch (err: any) {
      return rejectWithValue(err.response?.data?.detail || "Erreur lors de l'annulation");
    }
  }
);

export const inviteCoHost = createAsyncThunk(
  'team/inviteCoHost',
  async ({ teamId, email }: { teamId: string; email: string }, { rejectWithValue }) => {
    try {
      const response = await api.post(
        `/api/teams/${teamId}/invitations`,
        { email },
        { headers: { 'Content-Type': 'application/ld+json' } }
      );
      return response.data as TeamInvitation;
    } catch (err: any) {
      return rejectWithValue(err.response?.data?.detail || "Erreur lors de l'invitation");
    }
  }
);

const teamSlice = createSlice({
  name: 'team',
  initialState,
  reducers: {
    clearInviteStatus(state) {
      state.inviteStatus = 'idle';
      state.inviteError = null;
    },
  },
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
      })
      .addCase(fetchTeamInvitations.pending, (state) => {
        state.invitationsStatus = 'loading';
      })
      .addCase(fetchTeamInvitations.fulfilled, (state, action) => {
        state.invitationsStatus = 'succeeded';
        state.invitations = action.payload;
      })
      .addCase(fetchTeamInvitations.rejected, (state) => {
        state.invitationsStatus = 'failed';
      })
      .addCase(inviteCoHost.pending, (state) => {
        state.inviteStatus = 'loading';
        state.inviteError = null;
      })
      .addCase(inviteCoHost.fulfilled, (state, action) => {
        state.inviteStatus = 'succeeded';
        state.invitations.unshift(action.payload);
      })
      .addCase(inviteCoHost.rejected, (state, action) => {
        state.inviteStatus = 'failed';
        state.inviteError = action.payload as string;
      })
      .addCase(cancelTeamInvitation.fulfilled, (state, action) => {
        state.invitations = state.invitations.filter((i) => i.id !== action.payload);
      });
  },
});

export const { clearInviteStatus } = teamSlice.actions;
export default teamSlice.reducer;
