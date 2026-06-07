jest.mock('../../services/api', () => ({
  __esModule: true,
  default: { get: jest.fn(), post: jest.fn(), put: jest.fn(), patch: jest.fn(), delete: jest.fn() },
}));

import { configureStore } from '@reduxjs/toolkit';
import homepageReducer, {
  nextPageRequested,
  fetchPublishedAccommodations,
  setFilters,
  ITEMS_PER_PAGE,
} from './HomepageSlice';
import { listenerMiddleware } from '../../store/listenerMiddleware';
import './HomepageListeners';
import api from '../../services/api';

const mockedApi = api as jest.Mocked<typeof api>;

const buildStore = () =>
  configureStore({
    reducer: { homepage: homepageReducer },
    middleware: (gdm) => gdm().prepend(listenerMiddleware.middleware),
  });

const flush = async () => {
  for (let i = 0; i < 5; i++) await Promise.resolve();
};

beforeEach(() => {
  jest.clearAllMocks();
});

describe('nextPageRequested', () => {
  test('déclenche le fetch de la page suivante avec les filtres courants quand hasMore', async () => {
    mockedApi.get
      .mockResolvedValueOnce({ data: { member: [{ id: 'a-1' }], totalItems: 30 } })
      .mockResolvedValueOnce({ data: { member: [{ id: 'a-2' }], totalItems: 30 } });
    const store = buildStore();

    store.dispatch(setFilters({ city: 'Paris' }));
    await store.dispatch(fetchPublishedAccommodations({ city: 'Paris', page: 1 }));

    store.dispatch(nextPageRequested());
    await flush();

    expect(mockedApi.get).toHaveBeenLastCalledWith('/api/accommodations', {
      params: { page: '2', itemsPerPage: String(ITEMS_PER_PAGE), city: 'Paris', guests: '1' },
    });
    expect(store.getState().homepage.accommodations.map((a) => a.id)).toEqual(['a-1', 'a-2']);
  });

  test('ne fetch pas quand toutes les pages sont déjà chargées (hasMore false)', async () => {
    mockedApi.get.mockResolvedValueOnce({ data: { member: [{ id: 'a-1' }, { id: 'a-2' }], totalItems: 2 } });
    const store = buildStore();

    await store.dispatch(fetchPublishedAccommodations({ page: 1 }));
    expect(mockedApi.get).toHaveBeenCalledTimes(1);

    store.dispatch(nextPageRequested());
    await flush();

    expect(mockedApi.get).toHaveBeenCalledTimes(1);
  });

  test('ne déclenche pas un second fetch tant que le précédent est en cours', async () => {
    mockedApi.get
      .mockResolvedValueOnce({ data: { member: [{ id: 'a-1' }], totalItems: 30 } })
      .mockImplementationOnce(
        () =>
          new Promise((resolve) =>
            setTimeout(() => resolve({ data: { member: [{ id: 'a-2' }], totalItems: 30 } }), 0)
          ) as any
      );
    const store = buildStore();

    await store.dispatch(fetchPublishedAccommodations({ page: 1 }));

    store.dispatch(nextPageRequested());
    await flush();
    store.dispatch(nextPageRequested());
    await flush();

    // page 1 + a single page-2 fetch despite two intents
    expect(mockedApi.get).toHaveBeenCalledTimes(2);
  });
});
