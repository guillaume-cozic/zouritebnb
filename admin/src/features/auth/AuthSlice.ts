import { createAsyncThunk, createSlice } from '@reduxjs/toolkit';
import api, {
  clearStoredAuth,
  getStoredToken,
  getStoredUser,
  setStoredToken,
  setStoredUser,
} from '../../services/api';

export interface AdminUser {
  id: string;
  email: string;
  firstName: string | null;
  lastName: string | null;
}

export interface AuthState {
  user: AdminUser | null;
  status: 'idle' | 'loading' | 'succeeded' | 'failed';
  error: string | null;
}

export interface LoginCredentials {
  email: string;
  password: string;
}

/** Decodes the payload of a JWT (base64url) without verifying the signature. */
const decodeJwtPayload = (token: string): Record<string, unknown> | null => {
  try {
    const payload = token.split('.')[1];
    if (!payload) return null;
    const base64 = payload.replace(/-/g, '+').replace(/_/g, '/');
    return JSON.parse(atob(base64)) as Record<string, unknown>;
  } catch {
    return null;
  }
};

export const login = createAsyncThunk<AdminUser, LoginCredentials, { rejectValue: string }>(
  'auth/login',
  async (credentials, { rejectWithValue }) => {
    try {
      const response = await api.post('/api/login', credentials, {
        headers: { 'Content-Type': 'application/ld+json' },
      });
      const { token, id, email, firstName, lastName } = response.data as {
        token: string;
        id: string;
        email: string;
        firstName: string | null;
        lastName: string | null;
      };

      const payload = decodeJwtPayload(token);
      const roles = Array.isArray(payload?.roles) ? (payload?.roles as string[]) : [];
      if (!roles.includes('ROLE_ADMIN')) {
        return rejectWithValue('Accès réservé aux administrateurs');
      }

      const user: AdminUser = {
        id,
        email,
        firstName: firstName ?? null,
        lastName: lastName ?? null,
      };
      setStoredToken(token);
      setStoredUser(user);
      return user;
    } catch (error) {
      const status = (error as { response?: { status?: number } })?.response?.status;
      if (status === 401) {
        return rejectWithValue('Identifiants invalides');
      }
      return rejectWithValue('Connexion impossible, veuillez réessayer');
    }
  }
);

/** Rehydrate the session persisted in localStorage (slice owns the storage access). */
const storedUser = getStoredToken() ? getStoredUser<AdminUser>() : null;

const initialState: AuthState = {
  user: storedUser,
  status: 'idle',
  error: null,
};

const authSlice = createSlice({
  name: 'auth',
  initialState,
  reducers: {
    loggedOut: (state) => {
      clearStoredAuth();
      state.user = null;
      state.status = 'idle';
      state.error = null;
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(login.pending, (state) => {
        state.status = 'loading';
        state.error = null;
      })
      .addCase(login.fulfilled, (state, action) => {
        state.status = 'succeeded';
        state.user = action.payload;
      })
      .addCase(login.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload ?? 'Connexion impossible, veuillez réessayer';
      });
  },
});

export const { loggedOut } = authSlice.actions;
export default authSlice.reducer;
