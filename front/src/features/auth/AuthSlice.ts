import { createSlice, createAsyncThunk, createAction, PayloadAction } from '@reduxjs/toolkit';
import api, {
  AUTH_USER_KEY,
  clearStoredAuth,
  setStoredToken,
} from '../../services/api';
import { AuthUser } from './AuthTypes';
import {
  submitIdentityVerification,
  fetchVerificationStatus,
} from '../userProfile/UserProfileSlice';
import { VerificationResult } from '../userProfile/UserProfileTypes';
import { extractErrorMessage } from '../../services/errors';

const STORAGE_KEY = AUTH_USER_KEY;

const loadUser = (): AuthUser | null => {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    return raw ? (JSON.parse(raw) as AuthUser) : null;
  } catch {
    return null;
  }
};

export type ProfileSaveState = 'idle' | 'saving' | 'saved' | 'error';

/**
 * Single business intent dispatched when the user edits their profile form.
 * A listener debounces and runs the update thunk.
 */
export const profileEdited = createAction<{
  userId: string;
  firstName: string;
  lastName: string;
  email: string;
}>('auth/profileEdited');

interface AuthState {
  user: AuthUser | null;
  status: 'idle' | 'loading' | 'succeeded' | 'failed';
  error: string | null;
  profileSaveState: ProfileSaveState;
}

const initialState: AuthState = {
  user: loadUser(),
  status: 'idle',
  error: null,
  profileSaveState: 'idle',
};

export const registerUser = createAsyncThunk(
  'auth/register',
  async (payload: { email: string; password: string }, { rejectWithValue }) => {
    try {
      const response = await api.post('/api/register', payload, {
        headers: { 'Content-Type': 'application/ld+json' },
      });
      return response.data as AuthUser;
    } catch (err) {
      return rejectWithValue(extractErrorMessage(err, 'Erreur lors de l\'inscription'));
    }
  }
);

export const updateUserProfile = createAsyncThunk(
  'auth/updateProfile',
  async (payload: { id: string; firstName: string | null; lastName: string | null; email: string }, { rejectWithValue }) => {
    try {
      await api.patch(
        `/api/users/${payload.id}/profile`,
        { firstName: payload.firstName, lastName: payload.lastName, email: payload.email },
        { headers: { 'Content-Type': 'application/merge-patch+json' } }
      );
      return { firstName: payload.firstName, lastName: payload.lastName, email: payload.email };
    } catch (err) {
      return rejectWithValue(extractErrorMessage(err, 'Erreur lors de la mise à jour du profil'));
    }
  }
);

export const loginUser = createAsyncThunk(
  'auth/login',
  async (payload: { email: string; password: string }, { rejectWithValue }) => {
    try {
      const response = await api.post('/api/login', payload, {
        headers: { 'Content-Type': 'application/ld+json' },
      });
      return response.data as AuthUser;
    } catch (err) {
      return rejectWithValue(extractErrorMessage(err, 'Identifiants invalides'));
    }
  }
);

const authSlice = createSlice({
  name: 'auth',
  initialState,
  reducers: {
    logout(state) {
      state.user = null;
      state.status = 'idle';
      state.error = null;
      clearStoredAuth();
    },
    profileSaveStateCleared(state) {
      state.profileSaveState = 'idle';
    },
  },
  extraReducers: (builder) => {
    const handlePending = (state: AuthState) => {
      state.status = 'loading';
      state.error = null;
    };
    const handleFulfilled = (state: AuthState, action: PayloadAction<AuthUser>) => {
      state.status = 'succeeded';
      state.user = action.payload;
      localStorage.setItem(STORAGE_KEY, JSON.stringify(action.payload));
      if (action.payload.token) {
        setStoredToken(action.payload.token);
      }
    };
    const handleRejected = (state: AuthState, action: { payload?: unknown }) => {
      state.status = 'failed';
      state.error = (action.payload as string) ?? 'Erreur';
    };

    builder
      .addCase(registerUser.pending, handlePending)
      .addCase(registerUser.fulfilled, handleFulfilled)
      .addCase(registerUser.rejected, handleRejected)
      .addCase(loginUser.pending, handlePending)
      .addCase(loginUser.fulfilled, handleFulfilled)
      .addCase(loginUser.rejected, handleRejected)
      .addCase(updateUserProfile.pending, (state) => {
        state.profileSaveState = 'saving';
      })
      .addCase(updateUserProfile.fulfilled, (state, action) => {
        state.profileSaveState = 'saved';
        if (!state.user) return;
        state.user.firstName = action.payload.firstName;
        state.user.lastName = action.payload.lastName;
        state.user.email = action.payload.email;
        localStorage.setItem(STORAGE_KEY, JSON.stringify(state.user));
      })
      .addCase(updateUserProfile.rejected, (state) => {
        state.profileSaveState = 'error';
      });

    const syncVerificationStatus = (
      state: AuthState,
      action: PayloadAction<VerificationResult>
    ) => {
      if (!state.user) return;
      state.user.verificationStatus = action.payload.status;
      localStorage.setItem(STORAGE_KEY, JSON.stringify(state.user));
    };

    builder
      .addCase(submitIdentityVerification.fulfilled, syncVerificationStatus)
      .addCase(fetchVerificationStatus.fulfilled, syncVerificationStatus);
  },
});

export const { logout, profileSaveStateCleared } = authSlice.actions;
export default authSlice.reducer;
