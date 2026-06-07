jest.mock('../../services/api', () => ({
  __esModule: true,
  default: { get: jest.fn(), post: jest.fn(), put: jest.fn(), patch: jest.fn(), delete: jest.fn() },
}));

import { configureStore } from '@reduxjs/toolkit';
import reservationReducer, { reservationModalOpened, createReservation } from './ReservationSlice';
import accommodationManagementReducer from '../accommodationManagement/AccommodationManagementSlice';
import { listenerMiddleware } from '../../store/listenerMiddleware';
import '../../store/registerListeners';
import api from '../../services/api';

const mockedApi = api as jest.Mocked<typeof api>;

const buildStore = () =>
  configureStore({
    reducer: {
      reservation: reservationReducer,
      accommodationManagement: accommodationManagementReducer,
    },
    middleware: (gdm) => gdm().prepend(listenerMiddleware.middleware),
  });

const flush = async () => {
  for (let i = 0; i < 5; i++) await Promise.resolve();
};

beforeEach(() => {
  jest.clearAllMocks();
});

describe('reservationModalOpened', () => {
  test('le store remet mutationStatus et mutationError à zéro', async () => {
    mockedApi.post.mockRejectedValue({ response: { data: { detail: 'oops' } } });
    const store = buildStore();

    await store.dispatch(
      createReservation({ accommodationId: 'a-1', checkIn: '', checkOut: '', guestName: '', pricePerNight: 0 } as any)
    );
    expect(store.getState().reservation.mutationStatus).toBe('failed');
    expect(store.getState().reservation.mutationError).toBe('oops');

    store.dispatch(reservationModalOpened({ accommodationId: 'a-1' }));

    expect(store.getState().reservation.mutationStatus).toBe('idle');
    expect(store.getState().reservation.mutationError).toBeNull();
  });

  test('le listener charge la liste des hébergements quand aucun n’est en store et pas d’id imposé', async () => {
    mockedApi.get.mockResolvedValue({ data: { 'hydra:member': [{ id: 'a-1', title: 'Loft' }] } });
    const store = buildStore();

    store.dispatch(reservationModalOpened({ accommodationId: undefined }));
    await flush();
    await flush();

    expect(mockedApi.get).toHaveBeenCalledWith('/api/my-accommodations', expect.anything());
    expect(store.getState().accommodationManagement.items).toHaveLength(1);
  });

  test('le listener ne fetch pas si un accommodationId est fourni', async () => {
    const store = buildStore();

    store.dispatch(reservationModalOpened({ accommodationId: 'a-1' }));
    await flush();

    expect(mockedApi.get).not.toHaveBeenCalled();
  });
});
