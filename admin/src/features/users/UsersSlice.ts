import { createAsyncThunk, createSlice } from '@reduxjs/toolkit';
import api, { extractCollection } from '../../services/api';
import type { AdminPlatformUser, UsersState } from './UsersTypes';

export const USERS_PER_PAGE = 20;

export interface FetchUsersParams {
  page?: number;
  search?: string;
  role?: string;
}

export const fetchUsers = createAsyncThunk<
  { items: AdminPlatformUser[]; totalItems: number; page: number },
  FetchUsersParams | void,
  { rejectValue: string }
>('users/fetchAll', async (params, { rejectWithValue }) => {
  const { page = 1, search = '', role = '' } = params ?? {};
  try {
    const response = await api.get('/api/admin/users', {
      params: {
        page,
        itemsPerPage: USERS_PER_PAGE,
        ...(search ? { search } : {}),
        ...(role ? { role } : {}),
      },
    });
    const { items, totalItems } = extractCollection<AdminPlatformUser>(response.data);
    return { items, totalItems, page };
  } catch {
    return rejectWithValue('Impossible de charger les clients');
  }
});

const initialState: UsersState = {
  items: [],
  page: 1,
  itemsPerPage: USERS_PER_PAGE,
  totalItems: 0,
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
        state.items = action.payload.items;
        state.totalItems = action.payload.totalItems;
        state.page = action.payload.page;
      })
      .addCase(fetchUsers.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload ?? 'Impossible de charger les clients';
      });
  },
});

export default usersSlice.reducer;
