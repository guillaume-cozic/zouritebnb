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

    await store.dispatch(createPaymentIntent({ reservationId: 'r-1' } as any));

    expect(store.getState().payment.status).toBe('succeeded');
    expect(mockedApi.post).toHaveBeenCalledWith(
      '/api/payment-intents',
      { reservationId: 'r-1' },
      expect.anything()
    );
  });

  test('le store passe à failed avec le message d\'erreur après rejected', async () => {
    mockedApi.post.mockRejectedValue({ response: { data: { detail: 'card declined' } } });
    const store = buildStore();

    await store.dispatch(createPaymentIntent({ reservationId: 'r-1' } as any));

    const state = store.getState().payment;
    expect(state.status).toBe('failed');
    expect(state.error).toBe('card declined');
  });
});

describe('resetPaymentStatus', () => {
  test('le store revient à idle et efface l\'erreur', async () => {
    mockedApi.post.mockRejectedValue({ response: { data: { detail: 'card declined' } } });
    const store = buildStore();
    await store.dispatch(createPaymentIntent({ reservationId: 'r-1' } as any));

    store.dispatch(resetPaymentStatus());

    const state = store.getState().payment;
    expect(state.status).toBe('idle');
    expect(state.error).toBeNull();
  });
});