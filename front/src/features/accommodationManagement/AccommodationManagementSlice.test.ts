import type { Mocked } from 'vitest';
vi.mock('../../services/api', async (importOriginal) => ({
  ...((await importOriginal()) as Record<string, unknown>),
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), patch: vi.fn(), delete: vi.fn() },
}));

import { configureStore } from '@reduxjs/toolkit';
import accommodationManagementReducer, {
  setStatusFilter,
  fetchAllAccommodations,
  fetchOwnsAccommodation,
  publishAccommodation,
  unpublishAccommodation,
  dismissPublishError,
} from './AccommodationManagementSlice';
import api from '../../services/api';

const mockedApi = api as Mocked<typeof api>;

const buildStore = () =>
  configureStore({ reducer: { accommodationManagement: accommodationManagementReducer } });

const seedItems = async (store: ReturnType<typeof buildStore>) => {
  mockedApi.get.mockResolvedValue({
    data: { 'hydra:member': [{ id: 'a-1', status: 'draft' }, { id: 'a-2', status: 'published' }] },
  });
  await store.dispatch(fetchAllAccommodations('all'));
};

beforeEach(() => {
  vi.clearAllMocks();
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
    expect(mockedApi.get).toHaveBeenCalledWith('/api/my-accommodations', { params: { status: 'all' } });
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

describe('fetchOwnsAccommodation (gate du back-office)', () => {
  test('hasAccommodation = true quand le total est > 0', async () => {
    mockedApi.get.mockResolvedValue({ data: { 'hydra:totalItems': 3, 'hydra:member': [{ id: 'a-1' }] } });
    const store = buildStore();

    await store.dispatch(fetchOwnsAccommodation());

    const state = store.getState().accommodationManagement;
    expect(state.hasAccommodation).toBe(true);
    expect(state.ownershipStatus).toBe('succeeded');
    expect(mockedApi.get).toHaveBeenCalledWith('/api/my-accommodations', {
      params: { status: 'all', itemsPerPage: 1 },
    });
  });

  test('hasAccommodation = false quand le total est 0', async () => {
    mockedApi.get.mockResolvedValue({ data: { 'hydra:totalItems': 0, 'hydra:member': [] } });
    const store = buildStore();

    await store.dispatch(fetchOwnsAccommodation());

    expect(store.getState().accommodationManagement.hasAccommodation).toBe(false);
  });

  test('fail open : hasAccommodation = true si la requête échoue', async () => {
    mockedApi.get.mockRejectedValue({ response: { data: { detail: 'boom' } } });
    const store = buildStore();

    await store.dispatch(fetchOwnsAccommodation());

    const state = store.getState().accommodationManagement;
    expect(state.hasAccommodation).toBe(true);
    expect(state.ownershipStatus).toBe('failed');
  });

  test('créer un hébergement ouvre le gate immédiatement (pas de refetch requis)', () => {
    const store = buildStore();
    expect(store.getState().accommodationManagement.hasAccommodation).toBeNull();

    store.dispatch({ type: 'accommodation/create/fulfilled', payload: { id: 'new-1' } });

    expect(store.getState().accommodationManagement.hasAccommodation).toBe(true);
  });

  test("un fetch 'all' synchronise aussi le gate, mais pas un fetch filtré", async () => {
    const store = buildStore();

    await seedItems(store); // status 'all' → 2 items
    expect(store.getState().accommodationManagement.hasAccommodation).toBe(true);

    // Un filtre 'draft' qui ne renvoie rien ne doit PAS repasser le gate à false.
    mockedApi.get.mockResolvedValue({ data: { 'hydra:member': [] } });
    await store.dispatch(fetchAllAccommodations('draft'));

    expect(store.getState().accommodationManagement.hasAccommodation).toBe(true);
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

  test('un publish rejeté enregistre le message et laisse l\'hébergement en draft', async () => {
    const store = buildStore();
    await seedItems(store);

    mockedApi.patch.mockRejectedValue({
      response: { data: { detail: 'This accommodation cannot be published yet: it needs at least 3 photos.' } },
    });
    await store.dispatch(publishAccommodation('a-1'));

    const state = store.getState().accommodationManagement;
    expect(state.publishError).toBe('This accommodation cannot be published yet: it needs at least 3 photos.');
    expect(state.items.find((a) => a.id === 'a-1')?.status).toBe('draft');
  });

  test('un publish réussi efface une erreur de publication précédente', async () => {
    const store = buildStore();
    await seedItems(store);

    mockedApi.patch.mockRejectedValueOnce({ response: { data: { detail: 'boom' } } });
    await store.dispatch(publishAccommodation('a-1'));
    expect(store.getState().accommodationManagement.publishError).toBe('boom');

    mockedApi.patch.mockResolvedValue({ data: {} });
    await store.dispatch(publishAccommodation('a-1'));
    expect(store.getState().accommodationManagement.publishError).toBeNull();
  });

  test('dismissPublishError efface le message', async () => {
    const store = buildStore();
    await seedItems(store);

    mockedApi.patch.mockRejectedValue({ response: { data: { detail: 'boom' } } });
    await store.dispatch(publishAccommodation('a-1'));
    expect(store.getState().accommodationManagement.publishError).toBe('boom');

    store.dispatch(dismissPublishError());
    expect(store.getState().accommodationManagement.publishError).toBeNull();
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