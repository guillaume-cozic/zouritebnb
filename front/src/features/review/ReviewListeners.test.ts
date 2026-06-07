jest.mock('../../services/api', () => ({
  __esModule: true,
  default: { get: jest.fn(), post: jest.fn(), put: jest.fn(), patch: jest.fn(), delete: jest.fn() },
}));

import { configureStore } from '@reduxjs/toolkit';
import reviewReducer, { reviewSubmitted } from './ReviewSlice';
import { listenerMiddleware } from '../../store/listenerMiddleware';
import './ReviewListeners';
import api from '../../services/api';

const mockedApi = api as jest.Mocked<typeof api>;

const buildStore = () =>
  configureStore({
    reducer: { review: reviewReducer },
    middleware: (gdm) => gdm().prepend(listenerMiddleware.middleware),
  });

const flush = async () => {
  for (let i = 0; i < 5; i++) await Promise.resolve();
};

beforeEach(() => {
  jest.clearAllMocks();
});

describe('reviewSubmitted listener', () => {
  test('an accommodation intent triggers the accommodation endpoint (one event, one effect)', async () => {
    mockedApi.post.mockResolvedValue({ data: {} });
    const store = buildStore();

    store.dispatch(
      reviewSubmitted({
        target: 'accommodation',
        reservationId: 'res-1',
        payload: {
          accommodationId: 'acc-1',
          rating: 5,
          comment: 'Séjour vraiment agréable, logement propre et bien situé, hôte réactif.',
        },
      })
    );
    await flush();

    expect(mockedApi.post).toHaveBeenCalledTimes(1);
    expect(mockedApi.post).toHaveBeenCalledWith(
      '/api/reviews/accommodation',
      expect.objectContaining({ accommodationId: 'acc-1' }),
      expect.anything()
    );
    expect(store.getState().review.status).toBe('succeeded');
  });

  test('a guest intent triggers the guest endpoint', async () => {
    mockedApi.post.mockResolvedValue({ data: {} });
    const store = buildStore();

    store.dispatch(
      reviewSubmitted({
        target: 'guest',
        reservationId: 'res-2',
        payload: {
          accommodationId: 'acc-1',
          guestUserId: 'guest-1',
          rating: 4,
          comment: 'Voyageur exemplaire, communication parfaite et logement laissé impeccable.',
        },
      })
    );
    await flush();

    expect(mockedApi.post).toHaveBeenCalledTimes(1);
    expect(mockedApi.post).toHaveBeenCalledWith(
      '/api/reviews/guest',
      expect.objectContaining({ guestUserId: 'guest-1' }),
      expect.anything()
    );
    expect(store.getState().review.status).toBe('succeeded');
  });
});
