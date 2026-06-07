jest.mock('../../services/api', () => ({
  __esModule: true,
  default: { get: jest.fn(), post: jest.fn(), put: jest.fn(), patch: jest.fn(), delete: jest.fn() },
}));

import { configureStore } from '@reduxjs/toolkit';
import solidarityProjectReducer, {
  fetchSolidarityProjects,
  fetchSolidarityProjectById,
} from './SolidarityProjectSlice';
import api from '../../services/api';

const mockedApi = api as jest.Mocked<typeof api>;

const buildStore = () =>
  configureStore({ reducer: { solidarityProject: solidarityProjectReducer } });

beforeEach(() => {
  jest.clearAllMocks();
});

describe('fetchSolidarityProjects', () => {
  test('le store stocke la liste des projets après fulfilled', async () => {
    mockedApi.get.mockResolvedValue({
      data: { 'hydra:member': [{ id: 'sp-1' }, { id: 'sp-2' }] },
    });
    const store = buildStore();

    await store.dispatch(fetchSolidarityProjects());

    const state = store.getState().solidarityProject;
    expect(state.status).toBe('succeeded');
    expect(state.items).toHaveLength(2);
    expect(mockedApi.get).toHaveBeenCalledWith('/api/solidarity_projects');
  });

  test('le store passe à failed avec le message d\'erreur après rejected', async () => {
    mockedApi.get.mockRejectedValue({ response: { data: { detail: 'down' } } });
    const store = buildStore();

    await store.dispatch(fetchSolidarityProjects());

    const state = store.getState().solidarityProject;
    expect(state.status).toBe('failed');
    expect(state.error).toBe('down');
  });
});

describe('fetchSolidarityProjectById', () => {
  test('le store stocke le projet courant après fulfilled', async () => {
    mockedApi.get.mockResolvedValue({ data: { id: 'sp-1', title: 'Coral' } });
    const store = buildStore();

    await store.dispatch(fetchSolidarityProjectById('sp-1'));

    const state = store.getState().solidarityProject;
    expect(state.currentStatus).toBe('succeeded');
    expect(state.current?.id).toBe('sp-1');
  });
});