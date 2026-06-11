jest.mock('../../services/api', () => ({
  __esModule: true,
  default: { get: jest.fn(), post: jest.fn(), put: jest.fn(), patch: jest.fn(), delete: jest.fn() },
}));

import { configureStore } from '@reduxjs/toolkit';
import paymentReducer, { resetPaymentStatus, createPaymentIntent } from './PaymentSlice';
import api from '../../services/api';

const mockedApi = api as jest.Mocked<typeof api>;

const buildStore = () => configureStore({ reducer: { payment: paymentReducer } });

beforeEach(() => {
  jest.clearAllMocks();
});

describe('createPaymentIntent', () => {
  test('le store passe à succeeded après fulfilled', async () => {
    mockedApi.post.mockResolvedValue({
      data: { paymentIntentId: 'pi_1', clientSecret: 'cs_1' },
    });
    const store = buildStore();

    await store.dispatch(
      createPaymentIntent({ accommodationId: 'a-1', checkIn: '2026-06-10T15:00:00', checkOut: '2026-06-15T11:00:00' })
    );

    expect(store.getState().payment.status).toBe('succeeded');
    expect(mockedApi.post).toHaveBeenCalledWith(
      '/api/payment-intents',
      { accommodationId: 'a-1', checkIn: '2026-06-10T15:00:00', checkOut: '2026-06-15T11:00:00' },
      expect.anything()
    );
  });

  test('le store passe à failed avec le message d\'erreur après rejected', async () => {
    mockedApi.post.mockRejectedValue({ response: { data: { detail: 'card declined' } } });
    const store = buildStore();

    await store.dispatch(
      createPaymentIntent({ accommodationId: 'a-1', checkIn: '2026-06-10T15:00:00', checkOut: '2026-06-15T11:00:00' })
    );

    const state = store.getState().payment;
    expect(state.status).toBe('failed');
    expect(state.error).toBe('card declined');
  });
});

describe('resetPaymentStatus', () => {
  test('le store revient à idle et efface l\'erreur', async () => {
    mockedApi.post.mockRejectedValue({ response: { data: { detail: 'card declined' } } });
    const store = buildStore();
    await store.dispatch(
      createPaymentIntent({ accommodationId: 'a-1', checkIn: '2026-06-10T15:00:00', checkOut: '2026-06-15T11:00:00' })
    );

    store.dispatch(resetPaymentStatus());

    const state = store.getState().payment;
    expect(state.status).toBe('idle');
    expect(state.error).toBeNull();
  });
});