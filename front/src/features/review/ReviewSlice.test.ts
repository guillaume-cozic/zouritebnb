jest.mock('../../services/api', () => ({
  __esModule: true,
  default: { get: jest.fn(), post: jest.fn(), put: jest.fn(), patch: jest.fn(), delete: jest.fn() },
}));

import { configureStore } from '@reduxjs/toolkit';
import reviewReducer, {
  reviewFormOpened,
  submitAccommodationReview,
  submitGuestReview,
} from './ReviewSlice';
import api from '../../services/api';

const mockedApi = api as jest.Mocked<typeof api>;

const buildStore = () => configureStore({ reducer: { review: reviewReducer } });

beforeEach(() => {
  jest.clearAllMocks();
});

describe('reviewFormOpened', () => {
  test('resets status and error in the store', () => {
    const store = buildStore();
    store.dispatch(reviewFormOpened());
    expect(store.getState().review.status).toBe('idle');
    expect(store.getState().review.error).toBeNull();
    expect(store.getState().review.errorCode).toBeNull();
  });
});

describe('submitAccommodationReview', () => {
  const args = {
    reservationId: 'res-1',
    payload: {
      accommodationId: 'acc-1',
      rating: 5,
      comment: 'Séjour vraiment agréable, logement propre et bien situé, hôte réactif.',
    },
  };

  test('marks the reservation as reviewed after fulfilled', async () => {
    mockedApi.post.mockResolvedValue({ data: {} });
    const store = buildStore();

    await store.dispatch(submitAccommodationReview(args));

    expect(store.getState().review.status).toBe('succeeded');
    expect(store.getState().review.submitted).toEqual([
      { target: 'accommodation', reservationId: 'res-1' },
    ]);
    expect(mockedApi.post).toHaveBeenCalledWith(
      '/api/reviews/accommodation',
      args.payload,
      { headers: { 'Content-Type': 'application/ld+json' } }
    );
  });

  test('stores the http status code and detail after rejected', async () => {
    mockedApi.post.mockRejectedValue({
      response: { status: 422, data: { detail: 'comment too short' } },
    });
    const store = buildStore();

    await store.dispatch(submitAccommodationReview(args));

    expect(store.getState().review.status).toBe('failed');
    expect(store.getState().review.errorCode).toBe(422);
    expect(store.getState().review.error).toBe('comment too short');
    expect(store.getState().review.submitted).toHaveLength(0);
  });
});

describe('submitGuestReview', () => {
  const args = {
    reservationId: 'res-2',
    payload: {
      accommodationId: 'acc-1',
      guestUserId: 'guest-1',
      rating: 4,
      comment: 'Voyageur exemplaire, communication parfaite et logement laissé impeccable.',
    },
  };

  test('marks the reservation as reviewed after fulfilled', async () => {
    mockedApi.post.mockResolvedValue({ data: {} });
    const store = buildStore();

    await store.dispatch(submitGuestReview(args));

    expect(store.getState().review.status).toBe('succeeded');
    expect(store.getState().review.submitted).toEqual([
      { target: 'guest', reservationId: 'res-2' },
    ]);
    expect(mockedApi.post).toHaveBeenCalledWith(
      '/api/reviews/guest',
      args.payload,
      { headers: { 'Content-Type': 'application/ld+json' } }
    );
  });

  test('surfaces a 403 forbidden error after rejected', async () => {
    mockedApi.post.mockRejectedValue({
      response: { status: 403, data: { detail: 'not in host team' } },
    });
    const store = buildStore();

    await store.dispatch(submitGuestReview(args));

    expect(store.getState().review.status).toBe('failed');
    expect(store.getState().review.errorCode).toBe(403);
    expect(store.getState().review.error).toBe('not in host team');
  });

  test('does not duplicate an already submitted review for the same reservation', async () => {
    mockedApi.post.mockResolvedValue({ data: {} });
    const store = buildStore();

    await store.dispatch(submitGuestReview(args));
    await store.dispatch(submitGuestReview(args));

    expect(store.getState().review.submitted).toHaveLength(1);
  });
});
