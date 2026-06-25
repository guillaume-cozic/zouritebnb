import type { Mocked } from 'vitest';
vi.mock('../../services/api', async (importOriginal) => ({
  ...((await importOriginal()) as Record<string, unknown>),
  default: { get: vi.fn(), post: vi.fn(), delete: vi.fn() },
  getStoredToken: vi.fn(() => null),
}));
vi.mock('./wishlistCookie', () => ({
  getWishlistCorrelationId: () => 'cid-1',
  getOrCreateWishlistCorrelationId: () => 'cid-1',
  clearWishlistCorrelationId: vi.fn(),
}));

import { configureStore } from '@reduxjs/toolkit';
import wishlistReducer, {
  addToWishlist,
  removeFromWishlist,
  fetchWishlist,
} from './WishlistSlice';
import api from '../../services/api';

const mockedApi = api as Mocked<typeof api>;

const buildStore = () => configureStore({ reducer: { wishlist: wishlistReducer } });

beforeEach(() => {
  vi.clearAllMocks();
});

describe('addToWishlist', () => {
  test('marque l’hébergement comme sauvegardé de façon optimiste', async () => {
    mockedApi.post.mockResolvedValue({ data: {} });
    const store = buildStore();

    const promise = store.dispatch(addToWishlist('a-1'));
    // Optimistic: saved before the request resolves.
    expect(store.getState().wishlist.savedIds).toContain('a-1');

    await promise;
    expect(store.getState().wishlist.savedIds).toContain('a-1');
    expect(mockedApi.post).toHaveBeenCalledWith(
      '/api/wishlist',
      { accommodationId: 'a-1' },
      expect.anything()
    );
  });

  test('annule l’ajout optimiste si l’API échoue', async () => {
    mockedApi.post.mockRejectedValue({ response: { data: { detail: 'boom' } } });
    const store = buildStore();

    await store.dispatch(addToWishlist('a-1'));

    expect(store.getState().wishlist.savedIds).not.toContain('a-1');
  });
});

describe('removeFromWishlist', () => {
  test('retire l’hébergement de façon optimiste', async () => {
    mockedApi.get.mockResolvedValue({
      data: { member: [{ accommodationId: 'a-1', title: 'Loft', city: null, country: null, price: 100, photoUrl: null }] },
    });
    mockedApi.delete.mockResolvedValue({ data: {} });
    const store = buildStore();
    await store.dispatch(fetchWishlist());
    expect(store.getState().wishlist.savedIds).toContain('a-1');

    await store.dispatch(removeFromWishlist('a-1'));

    expect(store.getState().wishlist.savedIds).not.toContain('a-1');
    expect(store.getState().wishlist.items).toHaveLength(0);
  });
});

describe('fetchWishlist', () => {
  test('charge les items et les ids sauvegardés', async () => {
    mockedApi.get.mockResolvedValue({
      data: { member: [{ accommodationId: 'a-1', title: 'Loft', city: 'Saint-Denis', country: 'La Réunion', price: 120, photoUrl: '/uploads/photos/x.jpg' }] },
    });
    const store = buildStore();

    await store.dispatch(fetchWishlist());

    expect(store.getState().wishlist.items).toHaveLength(1);
    expect(store.getState().wishlist.savedIds).toEqual(['a-1']);
    expect(store.getState().wishlist.status).toBe('succeeded');
  });
});
