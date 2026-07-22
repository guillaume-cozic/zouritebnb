import type { Mocked } from 'vitest';
vi.mock('../../services/api', async (importOriginal) => ({
  ...((await importOriginal()) as Record<string, unknown>),
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), patch: vi.fn(), delete: vi.fn() },
}));

import { configureStore } from '@reduxjs/toolkit';
import activityPointReducer, { fetchActivityPoints } from './ActivityPointSlice';
import api from '../../services/api';

const mockedApi = api as Mocked<typeof api>;

const buildStore = () =>
  configureStore({ reducer: { activityPoint: activityPointReducer } });

beforeEach(() => {
  vi.clearAllMocks();
});

const POINT = {
  id: 'p-1',
  name: 'Anse Mourouk',
  description: 'Spot de kitesurf.',
  category: 'kitesurf',
  latitude: -19.7583,
  longitude: 63.4317,
  articleUrl: null,
};

describe('fetchActivityPoints', () => {
  test('le store passe à succeeded et stocke les points (format hydra)', async () => {
    mockedApi.get.mockResolvedValue({ data: { 'hydra:member': [POINT] } });
    const store = buildStore();

    await store.dispatch(fetchActivityPoints());

    const state = store.getState().activityPoint;
    expect(mockedApi.get).toHaveBeenCalledWith('/api/activity-points');
    expect(state.status).toBe('succeeded');
    expect(state.items).toEqual([POINT]);
  });

  test('le store passe à failed avec le message d’erreur en cas d’échec', async () => {
    mockedApi.get.mockRejectedValue(new Error('boom'));
    const store = buildStore();

    await store.dispatch(fetchActivityPoints());

    const state = store.getState().activityPoint;
    expect(state.status).toBe('failed');
    expect(state.items).toEqual([]);
    expect(state.error).toBeTruthy();
  });

  test('un second dispatch après succès ne relance pas la requête', async () => {
    mockedApi.get.mockResolvedValue({ data: { member: [POINT] } });
    const store = buildStore();

    await store.dispatch(fetchActivityPoints());
    await store.dispatch(fetchActivityPoints());

    expect(mockedApi.get).toHaveBeenCalledTimes(1);
  });
});
