import { createSlice, createAsyncThunk, PayloadAction } from '@reduxjs/toolkit';
import api from '../../services/api';
import { AuthUser } from './AuthTypes';

const STORAGE_KEY = 'auth.user';

const loadUser = (): AuthUser | null => {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    return raw ? (JSON.parse(raw) as AuthUser) : null;
  } catch {
    return null;
  }
};

interface AuthState {
  user: AuthUser | null;
  status: 'idle' | 'loading' | 'succeeded' | 'failed';
  error: string | null;
}

const initialState: AuthState = {
  user: loadUser(),
  status: 'idle',
  error: null,
};

export const registerUser = createAsyncThunk(
  'auth/register',
  async (payload: { email: string; password: string }, { rejectWithValue }) => {
    try {
      const response = await api.post('/api/register', payload, {
        headers: { 'Content-Type': 'application/ld+json' },
      });
      return response.data as AuthUser;
    } catch (err: any) {
      return rejectWithValue(err.response?.data?.detail || 'Erreur lors de l\'inscription');
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
    } catch (err: any) {
      return rejectWithValue(err.response?.data?.detail || 'Erreur lors de la mise à jour du profil');
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
    } catch (err: any) {
      return rejectWithValue(err.response?.data?.detail || 'Identifiants invalides');
    }
  }
);

const authSlice = createSlice({
  name: 'auth',
  initialState,
  reducers: {
    logout(state) {
      state.user = null;
      localStorage.removeItem(STORAGE_KEY);
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
      .addCase(updateUserProfile.fulfilled, (state, action) => {
        if (!state.user) return;
        state.user.firstName = action.payload.firstName;
        state.user.lastName = action.payload.lastName;
        state.user.email = action.payload.email;
        localStorage.setItem(STORAGE_KEY, JSON.stringify(state.user));
      });
  },
});

export const { logout } = authSlice.actions;
export default authSlice.reducer;
