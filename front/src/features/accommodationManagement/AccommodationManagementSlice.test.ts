jest.mock('../../services/api', () => ({
  __esModule: true,
  default: { get: jest.fn(), post: jest.fn(), put: jest.fn(), patch: jest.fn(), delete: jest.fn() },
}));

import { configureStore } from '@reduxjs/toolkit';
import accommodationManagementReducer, {
  setStatusFilter,
  fetchAllAccommodations,
  publishAccommodation,
  unpublishAccommodation,
} from './AccommodationManagementSlice';
import api from '../../services/api';

const mockedApi = api as jest.Mocked<typeof api>;

const buildStore = () =>
  configureStore({ reducer: { accommodationManagement: accommodationManagementReducer } });

const seedItems = async (store: ReturnType<typeof buildStore>) => {
  mockedApi.get.mockResolvedValue({
    data: { 'hydra:member': [{ id: 'a-1', status: 'draft' }, { id: 'a-2', status: 'published' }] },
  });
  await store.dispatch(fetchAllAccommodations('all'));
};

beforeEach(() => {
  jest.clearAllMocks();
});

describe('setStatusFilter', () => {
  test('le store mémorise le filtre de statut', () => {
    const store = buildStore();

    store.dispatch(setStatusFilter('published'));

    expect(store.getState().accommodationManagement.statusFilter).toBe('published');
  });
});

describe('fetchAllAccommodations', () => {
  test('le store stocke les hébergements et filtre par statut après fulfilled', async () => {
    const store = buildStore();
    await seedItems(store);

    const state = store.getState().accommodationManagement;
    expect(state.status).toBe('succeeded');
    expect(state.items).toHaveLength(2);
    expect(mockedApi.get).toHaveBeenCalledWith('/api/accommodations', { params: { status: 'all' } });
  });

  test('le store passe à failed avec le message d\'erreur après rejected', async () => {
    mockedApi.get.mockRejectedValue({ response: { data: { detail: 'nope' } } });
    const store = buildStore();

    await store.dispatch(fetchAllAccommodations('draft'));

    const state = store.getState().accommodationManagement;
    expect(state.status).toBe('failed');
    expect(state.error).toBe('nope');
  });
});

describe('publishAccommodation / unpublishAccommodation', () => {
  test('publish fait passer l\'hébergement ciblé à published dans le store', async () => {
    const store = buildStore();
    await seedItems(store);

    mockedApi.patch.mockResolvedValue({ data: {} });
    await store.dispatch(publishAccommodation('a-1'));

    const item = store.getState().accommodationManagement.items.find((a) => a.id === 'a-1');
    expect(item?.status).toBe('published');
  });

  test('unpublish fait passer l\'hébergement ciblé à draft dans le store', async () => {
    const store = buildStore();
    await seedItems(store);

    mockedApi.patch.mockResolvedValue({ data: {} });
    await store.dispatch(unpublishAccommodation('a-2'));

    const item = store.getState().accommodationManagement.items.find((a) => a.id === 'a-2');
    expect(item?.status).toBe('draft');
  });
});