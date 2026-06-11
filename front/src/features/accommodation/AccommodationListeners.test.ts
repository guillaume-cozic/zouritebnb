jest.mock('../../services/api', () => ({
  __esModule: true,
  default: { get: jest.fn(), post: jest.fn(), put: jest.fn(), patch: jest.fn(), delete: jest.fn() },
}));

import { configureStore } from '@reduxjs/toolkit';
import accommodationReducer, {
  accommodationFieldEdited,
  editPageOpened,
} from './AccommodationSlice';
import { listenerMiddleware } from '../../store/listenerMiddleware';
import './AccommodationListeners';
import api from '../../services/api';

const mockedApi = api as jest.Mocked<typeof api>;

const buildStore = () =>
  configureStore({
    reducer: { accommodation: accommodationReducer },
    middleware: (gdm) => gdm().prepend(listenerMiddleware.middleware),
  });

const flush = async () => {
  for (let i = 0; i < 30; i++) await Promise.resolve();
};

beforeEach(() => {
  jest.clearAllMocks();
});

describe('editPageOpened', () => {
  test('loads the accommodation (one event, one effect)', async () => {
    mockedApi.get.mockResolvedValue({ data: { id: 'a-1', title: 'Villa' } });
    const store = buildStore();

    store.dispatch(editPageOpened({ id: 'a-1' }));
    await flush();

    expect(mockedApi.get).toHaveBeenCalledWith('/api/accommodations/a-1');
    expect(store.getState().accommodation.current?.id).toBe('a-1');
  });

  test('resets the edit save badges', () => {
    const store = buildStore();
    store.dispatch(editPageOpened({ id: 'a-1' }));
    expect(store.getState().accommodation.editSaveStatus).toEqual({});
  });
});

describe('accommodationFieldEdited', () => {
  test('saves the price after the debounce delay and clears the badge', async () => {
    jest.useFakeTimers();
    try {
      mockedApi.patch.mockResolvedValue({ data: {} });
      const store = buildStore();

      store.dispatch(accommodationFieldEdited({ field: 'price', id: 'a-1', price: 120 }));
      expect(mockedApi.patch).not.toHaveBeenCalled();

      jest.advanceTimersByTime(1201);
      await flush();

      expect(mockedApi.patch).toHaveBeenCalledWith(
        '/api/accommodations/a-1/price',
        { price: 120 },
        expect.anything()
      );
      expect(store.getState().accommodation.editSaveStatus.price).toBe('saved');

      jest.advanceTimersByTime(2501);
      await flush();
      expect(store.getState().accommodation.editSaveStatus.price).toBe('idle');
    } finally {
      jest.useRealTimers();
    }
  });

  test('debounces successive edits of the same field into a single save', async () => {
    jest.useFakeTimers();
    try {
      mockedApi.patch.mockResolvedValue({ data: {} });
      const store = buildStore();

      store.dispatch(accommodationFieldEdited({ field: 'price', id: 'a-1', price: 100 }));
      jest.advanceTimersByTime(600);
      await flush();
      store.dispatch(accommodationFieldEdited({ field: 'price', id: 'a-1', price: 120 }));
      jest.advanceTimersByTime(1201);
      await flush();

      expect(mockedApi.patch).toHaveBeenCalledTimes(1);
      expect(mockedApi.patch).toHaveBeenCalledWith(
        '/api/accommodations/a-1/price',
        { price: 120 },
        expect.anything()
      );
    } finally {
      jest.useRealTimers();
    }
  });

  test('does not debounce edits of different fields against each other', async () => {
    jest.useFakeTimers();
    try {
      mockedApi.patch.mockResolvedValue({ data: {} });
      mockedApi.put.mockResolvedValue({ data: {} });
      const store = buildStore();

      store.dispatch(accommodationFieldEdited({ field: 'price', id: 'a-1', price: 100 }));
      jest.advanceTimersByTime(600);
      await flush();
      store.dispatch(accommodationFieldEdited({ field: 'amenities', id: 'a-1', codes: ['wifi'] }));
      jest.advanceTimersByTime(1201);
      await flush();

      expect(mockedApi.patch).toHaveBeenCalledWith(
        '/api/accommodations/a-1/price',
        { price: 100 },
        expect.anything()
      );
      expect(mockedApi.put).toHaveBeenCalledWith(
        '/api/accommodations/a-1/amenities',
        { codes: ['wifi'] },
        expect.anything()
      );
    } finally {
      jest.useRealTimers();
    }
  });

  test('skips the save when the payload violates the business rules', async () => {
    jest.useFakeTimers();
    try {
      const store = buildStore();

      store.dispatch(accommodationFieldEdited({ field: 'price', id: 'a-1', price: 0 }));
      store.dispatch(accommodationFieldEdited({ field: 'description', id: 'a-1', title: '', description: 'text' }));
      jest.advanceTimersByTime(1201);
      await flush();

      expect(mockedApi.patch).not.toHaveBeenCalled();
      expect(mockedApi.put).not.toHaveBeenCalled();
      expect(store.getState().accommodation.editSaveStatus.price).toBeUndefined();
    } finally {
      jest.useRealTimers();
    }
  });

  test('marks the section in error when the save fails', async () => {
    jest.useFakeTimers();
    try {
      mockedApi.patch.mockRejectedValue({ response: { data: { detail: 'boom' } } });
      const store = buildStore();

      store.dispatch(accommodationFieldEdited({ field: 'price', id: 'a-1', price: 120 }));
      jest.advanceTimersByTime(1201);
      await flush();

      expect(store.getState().accommodation.editSaveStatus.price).toBe('error');
    } finally {
      jest.useRealTimers();
    }
  });

  test('saves the weekly promotion under the price badge section', async () => {
    jest.useFakeTimers();
    try {
      mockedApi.patch.mockResolvedValue({ data: {} });
      const store = buildStore();

      store.dispatch(accommodationFieldEdited({
        field: 'weeklyPromotion',
        id: 'a-1',
        weeklyPromotionPercentage: 10,
      }));
      jest.advanceTimersByTime(1201);
      await flush();

      expect(mockedApi.patch).toHaveBeenCalledWith(
        '/api/accommodations/a-1/weekly-promotion',
        { weeklyPromotionPercentage: 10 },
        expect.anything()
      );
      expect(store.getState().accommodation.editSaveStatus.price).toBe('saved');
    } finally {
      jest.useRealTimers();
    }
  });
});
