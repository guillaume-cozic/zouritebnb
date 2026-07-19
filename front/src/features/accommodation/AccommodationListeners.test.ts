import type { Mocked } from 'vitest';
vi.mock('../../services/api', async (importOriginal) => ({
  ...((await importOriginal()) as Record<string, unknown>),
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), patch: vi.fn(), delete: vi.fn() },
}));

import { configureStore } from '@reduxjs/toolkit';
import accommodationReducer, {
  accommodationFieldEdited,
  editPageOpened,
} from './AccommodationSlice';
import { listenerMiddleware } from '../../store/listenerMiddleware';
import './AccommodationListeners';
import api from '../../services/api';

const mockedApi = api as Mocked<typeof api>;

const buildStore = () =>
  configureStore({
    reducer: { accommodation: accommodationReducer },
    middleware: (gdm) => gdm().prepend(listenerMiddleware.middleware),
  });

const flush = async () => {
  for (let i = 0; i < 30; i++) await Promise.resolve();
};

beforeEach(() => {
  vi.clearAllMocks();
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
    vi.useFakeTimers();
    try {
      mockedApi.patch.mockResolvedValue({ data: {} });
      const store = buildStore();

      store.dispatch(accommodationFieldEdited({ field: 'price', id: 'a-1', price: 120 }));
      expect(mockedApi.patch).not.toHaveBeenCalled();

      vi.advanceTimersByTime(1201);
      await flush();

      expect(mockedApi.patch).toHaveBeenCalledWith(
        '/api/accommodations/a-1/price',
        { price: 120 },
        expect.anything()
      );
      expect(store.getState().accommodation.editSaveStatus.price).toBe('saved');

      vi.advanceTimersByTime(2501);
      await flush();
      expect(store.getState().accommodation.editSaveStatus.price).toBe('idle');
    } finally {
      vi.useRealTimers();
    }
  });

  test('debounces successive edits of the same field into a single save', async () => {
    vi.useFakeTimers();
    try {
      mockedApi.patch.mockResolvedValue({ data: {} });
      const store = buildStore();

      store.dispatch(accommodationFieldEdited({ field: 'price', id: 'a-1', price: 100 }));
      vi.advanceTimersByTime(600);
      await flush();
      store.dispatch(accommodationFieldEdited({ field: 'price', id: 'a-1', price: 120 }));
      vi.advanceTimersByTime(1201);
      await flush();

      expect(mockedApi.patch).toHaveBeenCalledTimes(1);
      expect(mockedApi.patch).toHaveBeenCalledWith(
        '/api/accommodations/a-1/price',
        { price: 120 },
        expect.anything()
      );
    } finally {
      vi.useRealTimers();
    }
  });

  test('does not debounce edits of different fields against each other', async () => {
    vi.useFakeTimers();
    try {
      mockedApi.patch.mockResolvedValue({ data: {} });
      mockedApi.put.mockResolvedValue({ data: {} });
      const store = buildStore();

      store.dispatch(accommodationFieldEdited({ field: 'price', id: 'a-1', price: 100 }));
      vi.advanceTimersByTime(600);
      await flush();
      store.dispatch(accommodationFieldEdited({ field: 'amenities', id: 'a-1', codes: ['wifi'] }));
      vi.advanceTimersByTime(1201);
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
      vi.useRealTimers();
    }
  });

  test('skips the save when the payload violates the business rules', async () => {
    vi.useFakeTimers();
    try {
      const store = buildStore();

      store.dispatch(accommodationFieldEdited({ field: 'price', id: 'a-1', price: 0 }));
      store.dispatch(accommodationFieldEdited({ field: 'description', id: 'a-1', title: '', description: 'text' }));
      vi.advanceTimersByTime(1201);
      await flush();

      expect(mockedApi.patch).not.toHaveBeenCalled();
      expect(mockedApi.put).not.toHaveBeenCalled();
      expect(store.getState().accommodation.editSaveStatus.price).toBeUndefined();
    } finally {
      vi.useRealTimers();
    }
  });

  test('marks the section in error when the save fails', async () => {
    vi.useFakeTimers();
    try {
      mockedApi.patch.mockRejectedValue({ response: { data: { detail: 'boom' } } });
      const store = buildStore();

      store.dispatch(accommodationFieldEdited({ field: 'price', id: 'a-1', price: 120 }));
      vi.advanceTimersByTime(1201);
      await flush();

      expect(store.getState().accommodation.editSaveStatus.price).toBe('error');
    } finally {
      vi.useRealTimers();
    }
  });

  test('saves the cancellation policy under the cancellation badge section', async () => {
    vi.useFakeTimers();
    try {
      mockedApi.patch.mockResolvedValue({ data: {} });
      const store = buildStore();

      store.dispatch(accommodationFieldEdited({
        field: 'cancellationPolicy',
        id: 'a-1',
        cancellationPolicy: 'moderate',
      }));
      vi.advanceTimersByTime(1201);
      await flush();

      expect(mockedApi.patch).toHaveBeenCalledWith(
        '/api/accommodations/a-1/cancellation-policy',
        { cancellationPolicy: 'moderate' },
        expect.anything()
      );
      expect(store.getState().accommodation.editSaveStatus.cancellation).toBe('saved');
    } finally {
      vi.useRealTimers();
    }
  });

  test('saves the instant booking toggle under the cancellation badge section', async () => {
    vi.useFakeTimers();
    try {
      mockedApi.patch.mockResolvedValue({ data: {} });
      const store = buildStore();

      store.dispatch(accommodationFieldEdited({
        field: 'instantBooking',
        id: 'a-1',
        instantBooking: true,
      }));
      vi.advanceTimersByTime(1201);
      await flush();

      expect(mockedApi.patch).toHaveBeenCalledWith(
        '/api/accommodations/a-1/instant-booking',
        { instantBooking: true },
        expect.anything()
      );
      expect(store.getState().accommodation.editSaveStatus.cancellation).toBe('saved');
    } finally {
      vi.useRealTimers();
    }
  });

  test('saves the type under the description badge section', async () => {
    vi.useFakeTimers();
    try {
      mockedApi.patch.mockResolvedValue({ data: {} });
      const store = buildStore();

      store.dispatch(accommodationFieldEdited({ field: 'type', id: 'a-1', type: 'villa' }));
      vi.advanceTimersByTime(1201);
      await flush();

      expect(mockedApi.patch).toHaveBeenCalledWith(
        '/api/accommodations/a-1/type',
        { type: 'villa' },
        expect.anything()
      );
      expect(store.getState().accommodation.editSaveStatus.description).toBe('saved');
    } finally {
      vi.useRealTimers();
    }
  });

  test('saves stay constraints under the cancellation badge section', async () => {
    vi.useFakeTimers();
    try {
      mockedApi.patch.mockResolvedValue({ data: {} });
      const store = buildStore();

      store.dispatch(accommodationFieldEdited({ field: 'stayConstraints', id: 'a-1', minNights: 2, maxNights: 30 }));
      vi.advanceTimersByTime(1201);
      await flush();

      expect(mockedApi.patch).toHaveBeenCalledWith(
        '/api/accommodations/a-1/stay-constraints',
        { minNights: 2, maxNights: 30 },
        expect.anything()
      );
      expect(store.getState().accommodation.editSaveStatus.cancellation).toBe('saved');
    } finally {
      vi.useRealTimers();
    }
  });

  test('skips saving stay constraints when min is greater than max', async () => {
    vi.useFakeTimers();
    try {
      const store = buildStore();

      store.dispatch(accommodationFieldEdited({ field: 'stayConstraints', id: 'a-1', minNights: 10, maxNights: 5 }));
      vi.advanceTimersByTime(1201);
      await flush();

      expect(mockedApi.patch).not.toHaveBeenCalled();
    } finally {
      vi.useRealTimers();
    }
  });

  test('saves the house rules under the checkinout badge section', async () => {
    vi.useFakeTimers();
    try {
      mockedApi.patch.mockResolvedValue({ data: {} });
      const store = buildStore();

      store.dispatch(accommodationFieldEdited({
        field: 'houseRules',
        id: 'a-1',
        smokingAllowed: false,
        petsAllowed: true,
        partiesAllowed: false,
        houseRulesNotes: 'Merci de retirer vos chaussures.',
      }));
      expect(mockedApi.patch).not.toHaveBeenCalled();
      vi.advanceTimersByTime(1201);
      await flush();

      expect(mockedApi.patch).toHaveBeenCalledWith(
        '/api/accommodations/a-1/house-rules',
        { smokingAllowed: false, petsAllowed: true, partiesAllowed: false, houseRulesNotes: 'Merci de retirer vos chaussures.' },
        expect.anything()
      );
      expect(store.getState().accommodation.editSaveStatus.checkinout).toBe('saved');
    } finally {
      vi.useRealTimers();
    }
  });

  test('skips saving house rules when the notes exceed 1000 characters', async () => {
    vi.useFakeTimers();
    try {
      const store = buildStore();

      store.dispatch(accommodationFieldEdited({
        field: 'houseRules',
        id: 'a-1',
        smokingAllowed: false,
        petsAllowed: false,
        partiesAllowed: false,
        houseRulesNotes: 'a'.repeat(1001),
      }));
      vi.advanceTimersByTime(1201);
      await flush();

      expect(mockedApi.patch).not.toHaveBeenCalled();
    } finally {
      vi.useRealTimers();
    }
  });

  test('saves the weekly promotion under the price badge section', async () => {
    vi.useFakeTimers();
    try {
      mockedApi.patch.mockResolvedValue({ data: {} });
      const store = buildStore();

      store.dispatch(accommodationFieldEdited({
        field: 'weeklyPromotion',
        id: 'a-1',
        weeklyPromotionPercentage: 10,
      }));
      vi.advanceTimersByTime(1201);
      await flush();

      expect(mockedApi.patch).toHaveBeenCalledWith(
        '/api/accommodations/a-1/weekly-promotion',
        { weeklyPromotionPercentage: 10 },
        expect.anything()
      );
      expect(store.getState().accommodation.editSaveStatus.price).toBe('saved');
    } finally {
      vi.useRealTimers();
    }
  });
});
