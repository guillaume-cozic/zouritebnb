import type { Mocked } from 'vitest';
vi.mock('../../services/api', async (importOriginal) => ({
  ...((await importOriginal()) as Record<string, unknown>),
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), patch: vi.fn(), delete: vi.fn() },
}));

import { configureStore } from '@reduxjs/toolkit';
import geographyReducer, { fetchLocalities, fetchRegions } from './GeographySlice';
import api from '../../services/api';

const mockedApi = api as Mocked<typeof api>;

const buildStore = () => configureStore({ reducer: { geography: geographyReducer } });

beforeEach(() => {
  vi.clearAllMocks();
});

describe('fetchLocalities', () => {
  test('le store stocke les localités et transmet le regionCode après fulfilled', async () => {
    mockedApi.get.mockResolvedValue({ data: { 'hydra:member': [{ code: 'PAR' }] } });
    const store = buildStore();

    await store.dispatch(fetchLocalities('IDF'));

    const state = store.getState().geography;
    expect(state.status).toBe('succeeded');
    expect(state.localities).toHaveLength(1);
    expect(mockedApi.get).toHaveBeenCalledWith('/api/localities', { params: { regionCode: 'IDF' } });
  });

  test('le store passe à failed avec le message d\'erreur après rejected', async () => {
    mockedApi.get.mockRejectedValue({ response: { data: { detail: 'ko' } } });
    const store = buildStore();

    await store.dispatch(fetchLocalities(undefined));

    const state = store.getState().geography;
    expect(state.status).toBe('failed');
    expect(state.error).toBe('ko');
  });
});

describe('fetchRegions', () => {
  test('le store stocke les régions après fulfilled', async () => {
    mockedApi.get.mockResolvedValue({ data: { member: [{ code: 'IDF' }, { code: 'PACA' }] } });
    const store = buildStore();

    await store.dispatch(fetchRegions());

    const state = store.getState().geography;
    expect(state.status).toBe('succeeded');
    expect(state.regions).toHaveLength(2);
    expect(mockedApi.get).toHaveBeenCalledWith('/api/regions');
  });
});