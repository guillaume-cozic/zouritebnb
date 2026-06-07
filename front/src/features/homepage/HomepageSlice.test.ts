jest.mock('../../services/api', () => ({
  __esModule: true,
  default: { get: jest.fn(), post: jest.fn(), put: jest.fn(), patch: jest.fn(), delete: jest.fn() },
}));

import { configureStore } from '@reduxjs/toolkit';
import homepageReducer, {
  setFilters,
  fetchPublishedAccommodations,
  fetchHomepageFeatured,
} from './HomepageSlice';
import api from '../../services/api';

const mockedApi = api as jest.Mocked<typeof api>;

const buildStore = () =>
  configureStore({ reducer: { homepage: homepageReducer } });

beforeEach(() => {
  jest.clearAllMocks();
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
});

describe('fetchPublishedAccommodations', () => {
  test('le store passe à succeeded et stocke les hébergements après fulfilled', async () => {
    mockedApi.get.mockResolvedValue({
      data: { 'hydra:member': [{ id: 'a-1' }, { id: 'a-2' }] },
    });
    const store = buildStore();

    await store.dispatch(fetchPublishedAccommodations({ city: 'Paris', amenities: ['wifi'] }));

    const state = store.getState().homepage;
    expect(state.status).toBe('succeeded');
    expect(state.accommodations).toHaveLength(2);
    expect(mockedApi.get).toHaveBeenCalledWith('/api/accommodations', {
      params: { city: 'Paris', 'amenities[]': ['wifi'] },
    });
  });

  test('le store passe à failed et stocke le message d\'erreur après rejected', async () => {
    mockedApi.get.mockRejectedValue({ response: { data: { detail: 'boom' } } });
    const store = buildStore();

    await store.dispatch(fetchPublishedAccommodations());

    const state = store.getState().homepage;
    expect(state.status).toBe('failed');
    expect(state.error).toBe('boom');
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
});