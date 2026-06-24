import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import api from '../../services/api';
import { extractErrorMessage } from '../../services/errors';
import { RootState } from '../../store';
import { HostProfile } from './HostProfileTypes';

interface HostProfileState {
  byTeamId: Record<string, HostProfile>;
}

const initialState: HostProfileState = {
  byTeamId: {},
};

/**
 * Fetch a team's public host profile (name, photo, bio). Public endpoint, so it works for
 * anonymous travelers on accommodation pages. Cached by teamId: a no-op once loaded.
 */
export const fetchHostProfile = createAsyncThunk(
  'hostProfile/fetch',
  async (teamId: string, { getState, rejectWithValue }) => {
    if ((getState() as RootState).hostProfile.byTeamId[teamId]) return null;
    try {
      const response = await api.get(`/api/host-profiles/${teamId}`);
      const data = response.data;
      return {
        teamId,
        firstName: data.firstName ?? null,
        lastName: data.lastName ?? null,
        bio: data.bio ?? null,
        avatarUrl: data.avatarUrl ?? null,
      };
    } catch (err) {
      return rejectWithValue(extractErrorMessage(err, 'Profil hôte introuvable'));
    }
  }
);

const hostProfileSlice = createSlice({
  name: 'hostProfile',
  initialState,
  reducers: {},
  extraReducers: (builder) => {
    builder.addCase(fetchHostProfile.fulfilled, (state, action) => {
      if (action.payload) state.byTeamId[action.payload.teamId] = action.payload;
    });
  },
});

export default hostProfileSlice.reducer;
