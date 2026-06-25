import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import api, { getStoredToken } from '../../services/api';
import { extractErrorMessage } from '../../services/errors';
import { WishlistItem } from './WishlistTypes';
import {
  clearWishlistCorrelationId,
  getOrCreateWishlistCorrelationId,
  getWishlistCorrelationId,
} from './wishlistCookie';

const CORRELATION_HEADER = 'X-Wishlist-Id';

/** Headers for write calls: authenticated users rely on their JWT; anonymous
 *  visitors get (and create on first use) a correlation id sent as a header. */
const writeHeaders = (): Record<string, string> =>
  getStoredToken() ? {} : { [CORRELATION_HEADER]: getOrCreateWishlistCorrelationId() };

type Status = 'idle' | 'loading' | 'succeeded' | 'failed';

interface WishlistState {
  items: WishlistItem[];
  /** Accommodation ids currently saved — drives the heart toggle everywhere. */
  savedIds: string[];
  status: Status;
  error: string | null;
}

const initialState: WishlistState = {
  items: [],
  savedIds: [],
  status: 'idle',
  error: null,
};

export const fetchWishlist = createAsyncThunk(
  'wishlist/fetch',
  async (_: void, { rejectWithValue }) => {
    try {
      // Anonymous visitor without a correlation id yet: nothing saved, skip the call.
      const correlationId = getWishlistCorrelationId();
      const headers = getStoredToken()
        ? {}
        : correlationId
          ? { [CORRELATION_HEADER]: correlationId }
          : null;
      if (headers === null) return [] as WishlistItem[];

      const response = await api.get('/api/wishlist', { headers });
      const data = response.data;
      return (data['hydra:member'] ?? data['member'] ?? []) as WishlistItem[];
    } catch (err) {
      return rejectWithValue(extractErrorMessage(err, 'Erreur lors du chargement de la wishlist'));
    }
  }
);

export const addToWishlist = createAsyncThunk(
  'wishlist/add',
  async (accommodationId: string, { rejectWithValue }) => {
    try {
      await api.post(
        '/api/wishlist',
        { accommodationId },
        { headers: { 'Content-Type': 'application/ld+json', ...writeHeaders() } }
      );
      return accommodationId;
    } catch (err) {
      return rejectWithValue(extractErrorMessage(err, "Erreur lors de l'ajout à la wishlist"));
    }
  }
);

export const removeFromWishlist = createAsyncThunk(
  'wishlist/remove',
  async (accommodationId: string, { rejectWithValue }) => {
    try {
      await api.delete(`/api/wishlist/${accommodationId}`, { headers: writeHeaders() });
      return accommodationId;
    } catch (err) {
      return rejectWithValue(extractErrorMessage(err, 'Erreur lors du retrait de la wishlist'));
    }
  }
);

/** Merges the anonymous wishlist (cookie) into the account after sign-in, then
 *  drops the cookie. No-op when there is no anonymous wishlist. */
export const mergeWishlist = createAsyncThunk(
  'wishlist/merge',
  async (_: void, { rejectWithValue }) => {
    const correlationId = getWishlistCorrelationId();
    if (!correlationId) return;
    try {
      await api.post(
        '/api/wishlist/merge',
        {},
        { headers: { 'Content-Type': 'application/ld+json', [CORRELATION_HEADER]: correlationId } }
      );
      clearWishlistCorrelationId();
    } catch (err) {
      return rejectWithValue(extractErrorMessage(err, 'Erreur lors de la fusion de la wishlist'));
    }
  }
);

const wishlistSlice = createSlice({
  name: 'wishlist',
  initialState,
  reducers: {},
  extraReducers: (builder) => {
    builder
      .addCase(fetchWishlist.pending, (state) => {
        state.status = 'loading';
        state.error = null;
      })
      .addCase(fetchWishlist.fulfilled, (state, action) => {
        state.status = 'succeeded';
        state.items = action.payload;
        state.savedIds = action.payload.map((i) => i.accommodationId);
      })
      .addCase(fetchWishlist.rejected, (state, action) => {
        state.status = 'failed';
        state.error = (action.payload as string) || null;
      })
      // Optimistic toggle: reflect the change immediately, roll back on failure.
      .addCase(addToWishlist.pending, (state, action) => {
        if (!state.savedIds.includes(action.meta.arg)) state.savedIds.push(action.meta.arg);
      })
      .addCase(addToWishlist.rejected, (state, action) => {
        state.savedIds = state.savedIds.filter((id) => id !== action.meta.arg);
      })
      .addCase(removeFromWishlist.pending, (state, action) => {
        state.savedIds = state.savedIds.filter((id) => id !== action.meta.arg);
        state.items = state.items.filter((i) => i.accommodationId !== action.meta.arg);
      })
      .addCase(removeFromWishlist.rejected, (state, action) => {
        if (!state.savedIds.includes(action.meta.arg)) state.savedIds.push(action.meta.arg);
      });
  },
});

export default wishlistSlice.reducer;
