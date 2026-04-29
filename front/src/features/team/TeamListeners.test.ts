jest.mock('../../services/api', () => ({
  __esModule: true,
  default: { get: jest.fn(), post: jest.fn(), put: jest.fn(), patch: jest.fn(), delete: jest.fn() },
}));

import { configureStore } from '@reduxjs/toolkit';
import teamReducer, { teamSettingsPageOpened, inviteCoHost } from './TeamSlice';
import solidarityProjectReducer from '../solidarityProject/SolidarityProjectSlice';
import { listenerMiddleware } from '../../store/listenerMiddleware';
import '../../store/registerListeners';
import api from '../../services/api';

const mockedApi = api as jest.Mocked<typeof api>;

const buildStore = () =>
  configureStore({
    reducer: {
      team: teamReducer,
      solidarityProject: solidarityProjectReducer,
    },
    middleware: (gdm) => gdm().prepend(listenerMiddleware.middleware),
  });

const flushAll = async () => {
  for (let i = 0; i < 5; i++) {
    await Promise.resolve();
  }
};

beforeEach(() => {
  jest.clearAllMocks();
});

describe('teamSettingsPageOpened', () => {
  test('avec teamId : le store reçoit team, invitations et projets solidaires', async () => {
    mockedApi.get.mockImplementation((url: string) => {
      if (url === '/api/teams/team-1') return Promise.resolve({ data: { id: 'team-1', favoriteSolidarityProjectId: null } });
      if (url === '/api/teams/team-1/invitations') return Promise.resolve({ data: { 'hydra:member': [{ id: 'inv-1', email: 'a@b.fr', createdAt: '2026-04-28' }] } });
      if (url === '/api/solidarity_projects') return Promise.resolve({ data: { 'hydra:member': [{ id: 'sp-1', title: 'Coral', status: 'active' }] } });
      return Promise.reject(new Error('unexpected url ' + url));
    });
    const store = buildStore();

    store.dispatch(teamSettingsPageOpened({ teamId: 'team-1' }));
    await flushAll();

    const state = store.getState();
    expect(state.team.current?.id).toBe('team-1');
    expect(state.team.invitations).toHaveLength(1);
    expect(state.solidarityProject.items).toHaveLength(1);
  });

  test('sans teamId : seul le fetch des projets solidaires est lancé', async () => {
    mockedApi.get.mockResolvedValue({ data: { 'hydra:member': [] } });
    const store = buildStore();

    store.dispatch(teamSettingsPageOpened({ teamId: null }));
    await flushAll();

    const urls = mockedApi.get.mock.calls.map((c) => c[0]);
    expect(urls).toEqual(['/api/solidarity_projects']);
  });
});

describe('inviteCoHost.fulfilled — clear différé', () => {
  test('après succès et 2s, le store remet inviteStatus à idle', async () => {
    jest.useFakeTimers('modern');
    try {
      mockedApi.post.mockResolvedValue({
        data: { id: 'inv-1', email: 'co@host.fr', createdAt: '2026-04-28' },
      });
      const store = buildStore();

      await store.dispatch(inviteCoHost({ teamId: 'team-1', email: 'co@host.fr' }));
      expect(store.getState().team.inviteStatus).toBe('succeeded');

      await flushAll();
      jest.advanceTimersByTime(2001);
      await flushAll();
      await flushAll();

      expect(store.getState().team.inviteStatus).toBe('idle');
    } finally {
      jest.useRealTimers();
    }
  });
});
