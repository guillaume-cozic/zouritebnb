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
  bio: string;
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
  async (payload: { firstName: string | null; lastName: string | null; email: string; bio: string | null }, { rejectWithValue }) => {
    try {
      // The authenticated user is resolved from the JWT server-side (no id in the path).
      await api.patch(
        '/api/users/profile',
        { firstName: payload.firstName, lastName: payload.lastName, email: payload.email, bio: payload.bio },
        { headers: { 'Content-Type': 'application/merge-patch+json' } }
      );
      return { firstName: payload.firstName, lastName: payload.lastName, email: payload.email, bio: payload.bio };
    } catch (err) {
      return rejectWithValue(extractErrorMessage(err, 'Erreur lors de la mise à jour du profil'));
    }
  }
);

export const uploadAvatar = createAsyncThunk(
  'auth/uploadAvatar',
  async (file: File, { rejectWithValue }) => {
    try {
      const formData = new FormData();
      formData.append('file', file);
      const response = await api.post('/api/users/avatar', formData);
      return { avatarUrl: response.data.avatarUrl as string };
    } catch (err) {
      return rejectWithValue(extractErrorMessage(err, 'Erreur lors de l\'envoi de la photo'));
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

/** Exchange a provider-issued token (Google/Apple/Facebook) for the app session. */
export const socialLogin = createAsyncThunk(
  'auth/socialLogin',
  async (payload: { provider: 'google' | 'apple' | 'facebook'; token: string }, { rejectWithValue }) => {
    try {
      const response = await api.post('/api/auth/social', payload, {
        headers: { 'Content-Type': 'application/ld+json' },
      });
      return response.data as AuthUser;
    } catch (err) {
      return rejectWithValue(extractErrorMessage(err, 'La connexion via ce fournisseur a échoué'));
    }
  }
);

/** Ask the API to email a password reset link. Always resolves (the API answers 202
 *  whether or not the address has an account) so the UI cannot be used to probe emails. */
export const requestPasswordReset = createAsyncThunk(
  'auth/requestPasswordReset',
  async (payload: { email: string }, { rejectWithValue }) => {
    try {
      await api.post('/api/forgot-password', payload, {
        headers: { 'Content-Type': 'application/ld+json' },
      });
      return true;
    } catch (err) {
      return rejectWithValue(extractErrorMessage(err, 'Impossible d\'envoyer l\'email de réinitialisation'));
    }
  }
);

/** Set a new password from the token received by email. */
export const resetPassword = createAsyncThunk(
  'auth/resetPassword',
  async (payload: { token: string; password: string }, { rejectWithValue }) => {
    try {
      await api.post('/api/reset-password', payload, {
        headers: { 'Content-Type': 'application/ld+json' },
      });
      return true;
    } catch (err) {
      return rejectWithValue(extractErrorMessage(err, 'Lien de réinitialisation invalide ou expiré'));
    }
  }
);

/** Confirm the account email from the token received by email. */
export const verifyEmail = createAsyncThunk(
  'auth/verifyEmail',
  async (payload: { token: string }, { rejectWithValue }) => {
    try {
      await api.post('/api/verify-email', payload, {
        headers: { 'Content-Type': 'application/ld+json' },
      });
      return true;
    } catch (err) {
      return rejectWithValue(extractErrorMessage(err, 'Lien de vérification invalide ou expiré'));
    }
  }
);

/** Re-send the verification email to the authenticated user. */
export const resendVerificationEmail = createAsyncThunk(
  'auth/resendVerificationEmail',
  async (_: void, { rejectWithValue }) => {
    try {
      await api.post('/api/users/resend-verification-email', null, {
        headers: { 'Content-Type': 'application/ld+json' },
      });
      return true;
    } catch (err) {
      return rejectWithValue(extractErrorMessage(err, 'Impossible de renvoyer l\'email de vérification'));
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
      .addCase(socialLogin.pending, handlePending)
      .addCase(socialLogin.fulfilled, handleFulfilled)
      .addCase(socialLogin.rejected, handleRejected)
      .addCase(updateUserProfile.pending, (state) => {
        state.profileSaveState = 'saving';
      })
      .addCase(updateUserProfile.fulfilled, (state, action) => {
        state.profileSaveState = 'saved';
        if (!state.user) return;
        state.user.firstName = action.payload.firstName;
        state.user.lastName = action.payload.lastName;
        state.user.email = action.payload.email;
        state.user.bio = action.payload.bio;
        localStorage.setItem(STORAGE_KEY, JSON.stringify(state.user));
      })
      .addCase(updateUserProfile.rejected, (state) => {
        state.profileSaveState = 'error';
      })
      .addCase(uploadAvatar.fulfilled, (state, action) => {
        if (!state.user) return;
        state.user.avatarUrl = action.payload.avatarUrl;
        localStorage.setItem(STORAGE_KEY, JSON.stringify(state.user));
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

    // Reflect a successful email confirmation in the stored user, so the
    // verification banner disappears immediately when the user is logged in.
    builder.addCase(verifyEmail.fulfilled, (state) => {
      if (!state.user) return;
      state.user.emailVerified = true;
      localStorage.setItem(STORAGE_KEY, JSON.stringify(state.user));
    });
  },
});

export const { logout, profileSaveStateCleared } = authSlice.actions;
export default authSlice.reducer;
