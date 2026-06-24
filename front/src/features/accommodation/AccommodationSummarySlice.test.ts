import type { Mocked } from 'vitest';
vi.mock('../../services/api', async (importOriginal) => ({
  ...((await importOriginal()) as Record<string, unknown>),
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), patch: vi.fn(), delete: vi.fn() },
}));

import { configureStore } from '@reduxjs/toolkit';
import accommodationSummaryReducer, {
  fetchAccommodationSummary,
} from './AccommodationSummarySlice';
import { selectAccommodationSummaryById } from './AccommodationSummarySelectors';
import api from '../../services/api';

const mockedApi = api as Mocked<typeof api>;

const buildStore = () =>
  configureStore({ reducer: { accommodationSummary: accommodationSummaryReducer } });

beforeEach(() => {
  vi.clearAllMocks();
});

describe('fetchAccommodationSummary', () => {
  test('le store ne garde que le titre et la ville après fulfilled', async () => {
    mockedApi.get.mockResolvedValue({
      data: { id: 'a-1', title: 'Villa Tropicale', city: 'Saint-Denis', price: 120, description: 'x' },
    });
    const store = buildStore();

    await store.dispatch(fetchAccommodationSummary('a-1'));

    expect(mockedApi.get).toHaveBeenCalledWith('/api/accommodations/a-1');
    expect(selectAccommodationSummaryById('a-1')(store.getState())).toEqual({
      id: 'a-1',
      title: 'Villa Tropicale',
      city: 'Saint-Denis',
    });
  });

  test('titre et ville manquants retombent sur null', async () => {
    mockedApi.get.mockResolvedValue({ data: { id: 'a-2' } });
    const store = buildStore();

    await store.dispatch(fetchAccommodationSummary('a-2'));

    expect(selectAccommodationSummaryById('a-2')(store.getState())).toEqual({
      id: 'a-2',
      title: null,
      city: null,
    });
  });

  test('un id déjà en cache ne déclenche pas de seconde requête', async () => {
    mockedApi.get.mockResolvedValue({ data: { id: 'a-1', title: 'Villa', city: 'Sainte-Marie' } });
    const store = buildStore();

    await store.dispatch(fetchAccommodationSummary('a-1'));
    await store.dispatch(fetchAccommodationSummary('a-1'));

    expect(mockedApi.get).toHaveBeenCalledTimes(1);
  });
});
