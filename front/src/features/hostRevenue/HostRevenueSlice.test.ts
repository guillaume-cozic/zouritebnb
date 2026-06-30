import type { Mocked } from 'vitest';
vi.mock('../../services/api', async (importOriginal) => ({
  ...((await importOriginal()) as Record<string, unknown>),
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), patch: vi.fn(), delete: vi.fn() },
}));

import { configureStore } from '@reduxjs/toolkit';
import hostRevenueReducer, { fetchHostRevenue } from './HostRevenueSlice';
import api from '../../services/api';

const mockedApi = api as Mocked<typeof api>;

const buildStore = () => configureStore({ reducer: { hostRevenue: hostRevenueReducer } });

beforeEach(() => {
  vi.clearAllMocks();
});

describe('fetchHostRevenue', () => {
  test('stores the revenue payload in the store after fulfilled', async () => {
    const payload = {
      id: 'current',
      totalEarned: 800,
      pendingAmount: 400,
      availableAmount: 400,
      confirmedReservations: 2,
      upcomingStays: 1,
      byAccommodation: [],
      byMonth: [],
      payouts: [],
    };
    mockedApi.get.mockResolvedValue({ data: payload });
    const store = buildStore();

    await store.dispatch(fetchHostRevenue());

    expect(store.getState().hostRevenue.status).toBe('succeeded');
    expect(store.getState().hostRevenue.data?.totalEarned).toBe(800);
    expect(mockedApi.get).toHaveBeenCalledWith('/api/host/revenue');
  });

  test('moves to failed with the error message after rejected', async () => {
    mockedApi.get.mockRejectedValue({ response: { data: { detail: 'boom' } } });
    const store = buildStore();

    await store.dispatch(fetchHostRevenue());

    expect(store.getState().hostRevenue.status).toBe('failed');
    expect(store.getState().hostRevenue.error).toBe('boom');
  });
});
