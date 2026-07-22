import type { Mocked } from 'vitest';
vi.mock('../../services/api', async (importOriginal) => ({
  ...((await importOriginal()) as Record<string, unknown>),
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), patch: vi.fn(), delete: vi.fn() },
}));

import { configureStore } from '@reduxjs/toolkit';
import donationReducer, { resetDonationStatus, createDonationIntent } from './DonationSlice';
import api from '../../services/api';

const mockedApi = api as Mocked<typeof api>;

const buildStore = () => configureStore({ reducer: { donation: donationReducer } });

beforeEach(() => {
  vi.clearAllMocks();
});

describe('createDonationIntent', () => {
  test('le store passe à succeeded après fulfilled', async () => {
    mockedApi.post.mockResolvedValue({
      data: { paymentIntentId: 'pi_don_1', clientSecret: 'cs_don_1' },
    });
    const store = buildStore();

    const result = await store.dispatch(
      createDonationIntent({ solidarityProjectId: 'sp-1', amountCents: 2500 })
    );

    expect(store.getState().donation.status).toBe('succeeded');
    expect(createDonationIntent.fulfilled.match(result)).toBe(true);
    if (createDonationIntent.fulfilled.match(result)) {
      expect(result.payload).toEqual({ paymentIntentId: 'pi_don_1', clientSecret: 'cs_don_1' });
    }
    expect(mockedApi.post).toHaveBeenCalledWith(
      '/api/donation-intents',
      { solidarityProjectId: 'sp-1', amountCents: 2500 },
      expect.anything()
    );
  });

  test('le store passe à failed avec le message d\'erreur après rejected', async () => {
    mockedApi.post.mockRejectedValue({ response: { data: { detail: 'amount below minimum' } } });
    const store = buildStore();

    await store.dispatch(
      createDonationIntent({ solidarityProjectId: 'sp-1', amountCents: 50 })
    );

    const state = store.getState().donation;
    expect(state.status).toBe('failed');
    expect(state.error).toBe('amount below minimum');
  });
});

describe('resetDonationStatus', () => {
  test('le store revient à idle et efface l\'erreur', async () => {
    mockedApi.post.mockRejectedValue({ response: { data: { detail: 'amount below minimum' } } });
    const store = buildStore();
    await store.dispatch(
      createDonationIntent({ solidarityProjectId: 'sp-1', amountCents: 50 })
    );

    store.dispatch(resetDonationStatus());

    const state = store.getState().donation;
    expect(state.status).toBe('idle');
    expect(state.error).toBeNull();
  });
});
