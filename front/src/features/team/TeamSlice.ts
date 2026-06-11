import { createSlice, createAsyncThunk, createAction } from '@reduxjs/toolkit';
import api from '../../services/api';
import { extractErrorMessage } from '../../services/errors';
import { BankAccountPayload, Team, TeamInvitation } from './TeamTypes';

export const teamSettingsPageOpened = createAction<{ teamId: string | null }>(
  'team/settingsPageOpened'
);

/**
 * Single business intent dispatched when the user edits the bank account form.
 * A listener debounces, normalises the values and runs the update thunk.
 */
export const bankAccountEdited = createAction<{
  teamId: string;
  iban: string;
  bic: string;
  holderName: string;
}>('team/bankAccountEdited');

export type SaveState = 'idle' | 'saving' | 'saved' | 'error';

interface TeamState {
  current: Team | null;
  status: 'idle' | 'loading' | 'succeeded' | 'failed';
  error: string | null;
  invitations: TeamInvitation[];
  invitationsStatus: 'idle' | 'loading' | 'succeeded' | 'failed';
  inviteStatus: 'idle' | 'loading' | 'succeeded' | 'failed';
  inviteError: string | null;
  bankSaveState: SaveState;
  bankSaveError: string | null;
  favoriteSaveState: SaveState;
}

const initialState: TeamState = {
  current: null,
  status: 'idle',
  error: null,
  invitations: [],
  invitationsStatus: 'idle',
  inviteStatus: 'idle',
  inviteError: null,
  bankSaveState: 'idle',
  bankSaveError: null,
  favoriteSaveState: 'idle',
};

export const fetchTeam = createAsyncThunk(
  'team/fetch',
  async (id: string, { rejectWithValue }) => {
    try {
      const response = await api.get(`/api/teams/${id}`);
      return response.data as Team;
    } catch (err) {
      return rejectWithValue(extractErrorMessage(err, 'Erreur lors du chargement de l\'équipe'));
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
    } catch (err) {
      return rejectWithValue(extractErrorMessage(err, 'Erreur lors de la mise à jour'));
    }
  }
);

export const updateTeamBankAccount = createAsyncThunk(
  'team/updateBankAccount',
  async ({ id, payload }: { id: string; payload: BankAccountPayload }, { rejectWithValue }) => {
    try {
      await api.patch(
        `/api/teams/${id}/bank-account`,
        payload,
        { headers: { 'Content-Type': 'application/merge-patch+json' } }
      );
      return payload;
    } catch (err) {
      return rejectWithValue(extractErrorMessage(err, 'Erreur lors de la mise à jour du compte bancaire'));
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
    } catch (err) {
      return rejectWithValue(extractErrorMessage(err, 'Erreur lors du chargement des invitations'));
    }
  }
);

export const cancelTeamInvitation = createAsyncThunk(
  'team/cancelInvitation',
  async (invitationId: string, { rejectWithValue }) => {
    try {
      await api.delete(`/api/team-invitations/${invitationId}`);
      return invitationId;
    } catch (err) {
      return rejectWithValue(extractErrorMessage(err, "Erreur lors de l'annulation"));
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
    } catch (err) {
      return rejectWithValue(extractErrorMessage(err, "Erreur lors de l'invitation"));
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
    bankSaveStateCleared(state) {
      state.bankSaveState = 'idle';
    },
    favoriteSaveStateCleared(state) {
      state.favoriteSaveState = 'idle';
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
      .addCase(updateTeamFavoriteProject.pending, (state) => {
        state.favoriteSaveState = 'saving';
      })
      .addCase(updateTeamFavoriteProject.fulfilled, (state, action) => {
        state.favoriteSaveState = 'saved';
        if (state.current) {
          state.current.favoriteSolidarityProjectId = action.payload.favoriteSolidarityProjectId;
        }
      })
      .addCase(updateTeamFavoriteProject.rejected, (state) => {
        state.favoriteSaveState = 'error';
      })
      .addCase(updateTeamBankAccount.pending, (state) => {
        state.bankSaveState = 'saving';
        state.bankSaveError = null;
      })
      .addCase(updateTeamBankAccount.fulfilled, (state, action) => {
        state.bankSaveState = 'saved';
        if (state.current) {
          const { iban, bic, holderName } = action.payload;
          state.current.iban = iban;
          state.current.bic = bic;
          state.current.bankAccountHolderName = holderName;
        }
      })
      .addCase(updateTeamBankAccount.rejected, (state, action) => {
        state.bankSaveState = 'error';
        state.bankSaveError = (action.payload as string) ?? null;
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

export const { clearInviteStatus, bankSaveStateCleared, favoriteSaveStateCleared } = teamSlice.actions;
export default teamSlice.reducer;
