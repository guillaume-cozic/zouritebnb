import type { Mocked } from 'vitest';
vi.mock('../../services/api', async (importOriginal) => ({
  ...((await importOriginal()) as Record<string, unknown>),
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), patch: vi.fn(), delete: vi.fn() },
}));

import { configureStore } from '@reduxjs/toolkit';
import accommodationReducer, {
  wizardStepLeft,
  addressSubmitted,
  setLocation,
} from './AccommodationSlice';
import { listenerMiddleware } from '../../store/listenerMiddleware';
import '../../store/registerListeners';
import api from '../../services/api';
import type { AddressDraft } from './AccommodationTypes';

const mockedApi = api as Mocked<typeof api>;

const buildStore = () =>
  configureStore({
    reducer: { accommodation: accommodationReducer },
    middleware: (gdm) => gdm().prepend(listenerMiddleware.middleware),
  });

const flush = async () => {
  for (let i = 0; i < 5; i++) await Promise.resolve();
};

beforeEach(() => {
  vi.clearAllMocks();
});

describe('wizardStepLeft', () => {
  test('le store reflète la step et le draft après dispatch', () => {
    const store = buildStore();

    store.dispatch(
      wizardStepLeft({
        draft: { capacity: { bedrooms: 2, bathrooms: 1, maxGuests: 4, singleBeds: 0, doubleBeds: 1 } },
        target: 'amenities',
      })
    );

    const state = store.getState().accommodation;
    expect(state.wizardStep).toBe('amenities');
    expect(state.formDrafts.capacity).toEqual({
      bedrooms: 2, bathrooms: 1, maxGuests: 4, singleBeds: 0, doubleBeds: 1,
    });
  });

  test('change la step sans toucher aux drafts si non fourni', () => {
    const store = buildStore();
    store.dispatch(wizardStepLeft({ draft: { amenities: ['wifi'] }, target: 'address' }));
    store.dispatch(wizardStepLeft({ target: 'photos' }));

    const state = store.getState().accommodation;
    expect(state.wizardStep).toBe('photos');
    expect(state.formDrafts.amenities).toEqual(['wifi']);
  });
});

describe('addressSubmitted', () => {
  const address: AddressDraft = {
    street: '1 rue de Rivoli',
    city: 'Paris',
    zipCode: '75001',
    country: 'France',
    latitude: 48.85,
    longitude: 2.35,
  };

  test('le store enregistre l’adresse dans formDrafts.address', () => {
    const store = buildStore();
    store.dispatch(addressSubmitted({ id: 'acc-1', address }));

    expect(store.getState().accommodation.formDrafts.address).toEqual(address);
  });

  test('le listener déclenche setLocation sur le même store (un événement, deux effets)', async () => {
    mockedApi.put.mockResolvedValue({ data: {} });
    const store = buildStore();

    store.dispatch(addressSubmitted({ id: 'acc-42', address }));
    await flush();

    const calls = mockedApi.put.mock.calls.map((c) => c[0]);
    expect(calls).toContain('/api/accommodations/acc-42/address');
    expect(calls).toContain('/api/accommodations/acc-42/geolocation');
  });

  test('après setLocation.fulfilled, le store passe à la step photos', async () => {
    mockedApi.put.mockResolvedValue({ data: {} });
    const store = buildStore();

    await store.dispatch(
      setLocation({ id: 'acc-1', street: 'X', city: 'Y', zipCode: '00000', country: 'FR' })
    );

    expect(store.getState().accommodation.wizardStep).toBe('photos');
  });
});
