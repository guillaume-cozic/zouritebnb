jest.mock('../../services/api', () => ({
  __esModule: true,
  default: { get: jest.fn(), post: jest.fn(), put: jest.fn(), patch: jest.fn(), delete: jest.fn() },
}));

import { configureStore } from '@reduxjs/toolkit';
import geographyReducer, { fetchLocalities, fetchRegions } from './GeographySlice';
import api from '../../services/api';

const mockedApi = api as jest.Mocked<typeof api>;

const buildStore = () => configureStore({ reducer: { geography: geographyReducer } });

beforeEach(() => {
  jest.clearAllMocks();
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