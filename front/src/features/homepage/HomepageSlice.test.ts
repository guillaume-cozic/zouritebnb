import type { Mocked } from 'vitest';
vi.mock('../../services/api', async (importOriginal) => ({
  ...((await importOriginal()) as Record<string, unknown>),
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), patch: vi.fn(), delete: vi.fn() },
}));

import { configureStore } from '@reduxjs/toolkit';
import homepageReducer, {
  setFilters,
  fetchPublishedAccommodations,
  fetchHomepageFeatured,
  ITEMS_PER_PAGE,
} from './HomepageSlice';
import api from '../../services/api';

const mockedApi = api as Mocked<typeof api>;

const buildStore = () =>
  configureStore({ reducer: { homepage: homepageReducer } });

beforeEach(() => {
  vi.clearAllMocks();
});

describe('setFilters', () => {
  test('le store fusionne les filtres fournis sans toucher aux autres', () => {
    const store = buildStore();

    store.dispatch(setFilters({ city: 'Paris', guests: 3 }));

    const { filters } = store.getState().homepage;
    expect(filters.city).toBe('Paris');
    expect(filters.guests).toBe(3);
    expect(filters.checkIn).toBe('');
    expect(filters.amenities).toEqual([]);
  });

  test('le changement de filtres réinitialise la pagination accumulée', async () => {
    mockedApi.get.mockResolvedValue({
      data: { member: [{ id: 'a-1' }, { id: 'a-2' }], totalItems: 50 },
    });
    const store = buildStore();
    await store.dispatch(fetchPublishedAccommodations({ page: 1 }));

    expect(store.getState().homepage.accommodations).toHaveLength(2);
    expect(store.getState().homepage.totalItems).toBe(50);

    store.dispatch(setFilters({ city: 'Lyon' }));

    const state = store.getState().homepage;
    expect(state.accommodations).toEqual([]);
    expect(state.page).toBe(1);
    expect(state.totalItems).toBe(0);
  });
});

describe('fetchPublishedAccommodations', () => {
  test('le store passe à succeeded et stocke la première page avec page+itemsPerPage', async () => {
    mockedApi.get.mockResolvedValue({
      data: { 'hydra:member': [{ id: 'a-1' }, { id: 'a-2' }], 'hydra:totalItems': 30 },
    });
    const store = buildStore();

    await store.dispatch(fetchPublishedAccommodations({ city: 'Paris', amenities: ['wifi'] }));

    const state = store.getState().homepage;
    expect(state.status).toBe('succeeded');
    expect(state.accommodations).toHaveLength(2);
    expect(state.page).toBe(1);
    expect(state.totalItems).toBe(30);
    expect(mockedApi.get).toHaveBeenCalledWith('/api/accommodations', {
      params: {
        page: '1',
        itemsPerPage: String(ITEMS_PER_PAGE),
        city: 'Paris',
        'amenities[]': ['wifi'],
      },
    });
  });

  test('une page suivante est ajoutée (append) aux résultats existants', async () => {
    mockedApi.get
      .mockResolvedValueOnce({ data: { member: [{ id: 'a-1' }, { id: 'a-2' }], totalItems: 5 } })
      .mockResolvedValueOnce({ data: { member: [{ id: 'a-3' }, { id: 'a-4' }], totalItems: 5 } });
    const store = buildStore();

    await store.dispatch(fetchPublishedAccommodations({ page: 1 }));
    await store.dispatch(fetchPublishedAccommodations({ page: 2 }));

    const state = store.getState().homepage;
    expect(state.accommodations.map((a) => a.id)).toEqual(['a-1', 'a-2', 'a-3', 'a-4']);
    expect(state.page).toBe(2);
    expect(mockedApi.get).toHaveBeenLastCalledWith('/api/accommodations', {
      params: { page: '2', itemsPerPage: String(ITEMS_PER_PAGE) },
    });
  });

  test('charger une page suivante passe par loadingMore et non par status loading', async () => {
    mockedApi.get
      .mockResolvedValueOnce({ data: { member: [{ id: 'a-1' }], totalItems: 5 } })
      .mockImplementationOnce(
        () =>
          new Promise((resolve) =>
            setTimeout(() => resolve({ data: { member: [{ id: 'a-2' }], totalItems: 5 } }), 0)
          ) as any
      );
    const store = buildStore();

    await store.dispatch(fetchPublishedAccommodations({ page: 1 }));
    const promise = store.dispatch(fetchPublishedAccommodations({ page: 2 }));

    expect(store.getState().homepage.loadingMore).toBe(true);
    expect(store.getState().homepage.status).toBe('succeeded');

    await promise;
    expect(store.getState().homepage.loadingMore).toBe(false);
  });

  test('le store passe à failed et stocke le message d\'erreur après rejected (page 1)', async () => {
    mockedApi.get.mockRejectedValue({ response: { data: { detail: 'boom' } } });
    const store = buildStore();

    await store.dispatch(fetchPublishedAccommodations());

    const state = store.getState().homepage;
    expect(state.status).toBe('failed');
    expect(state.error).toBe('boom');
  });

  test('une erreur sur une page suivante ne casse pas le status succeeded', async () => {
    mockedApi.get
      .mockResolvedValueOnce({ data: { member: [{ id: 'a-1' }], totalItems: 5 } })
      .mockRejectedValueOnce({ response: { data: { detail: 'boom' } } });
    const store = buildStore();

    await store.dispatch(fetchPublishedAccommodations({ page: 1 }));
    await store.dispatch(fetchPublishedAccommodations({ page: 2 }));

    const state = store.getState().homepage;
    expect(state.status).toBe('succeeded');
    expect(state.loadingMore).toBe(false);
    expect(state.error).toBe('boom');
    expect(state.accommodations).toHaveLength(1);
  });
});

describe('fetchHomepageFeatured', () => {
  test('le store stocke les hébergements mis en avant et demande itemsPerPage=9', async () => {
    mockedApi.get.mockResolvedValue({ data: { member: [{ id: 'a-1' }] } });
    const store = buildStore();

    await store.dispatch(fetchHomepageFeatured());

    const state = store.getState().homepage;
    expect(state.status).toBe('succeeded');
    expect(state.accommodations).toHaveLength(1);
    expect(mockedApi.get).toHaveBeenCalledWith('/api/accommodations', {
      params: { itemsPerPage: '9' },
    });
  });

  test('déduplique les appels concurrents : une seule requête tant que la précédente est en vol', async () => {
    mockedApi.get.mockResolvedValue({ data: { member: [{ id: 'a-1' }] } });
    const store = buildStore();

    // Simule le double-montage StrictMode : deux dispatches avant résolution.
    await Promise.all([
      store.dispatch(fetchHomepageFeatured()),
      store.dispatch(fetchHomepageFeatured()),
    ]);

    expect(mockedApi.get).toHaveBeenCalledTimes(1);
  });
});
