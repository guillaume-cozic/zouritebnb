import { createAsyncThunk, createSlice } from '@reduxjs/toolkit';
import api, { extractMembers } from '../../services/api';
import type { AdminPlatformUser, UsersState } from './UsersTypes';

export const fetchUsers = createAsyncThunk<AdminPlatformUser[], void, { rejectValue: string }>(
  'users/fetchAll',
  async (_, { rejectWithValue }) => {
    try {
      const response = await api.get('/api/admin/users');
      return extractMembers<AdminPlatformUser>(response.data);
    } catch {
      return rejectWithValue('Impossible de charger les clients');
    }
  }
);

const initialState: UsersState = {
  items: [],
  status: 'idle',
  error: null,
};

const usersSlice = createSlice({
  name: 'users',
  initialState,
  reducers: {},
  extraReducers: (builder) => {
    builder
      .addCase(fetchUsers.pending, (state) => {
        state.status = 'loading';
        state.error = null;
      })
      .addCase(fetchUsers.fulfilled, (state, action) => {
        state.status = 'succeeded';
        state.items = action.payload;
      })
      .addCase(fetchUsers.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload ?? 'Impossible de charger les clients';
      });
  },
});

export default usersSlice.reducer;
