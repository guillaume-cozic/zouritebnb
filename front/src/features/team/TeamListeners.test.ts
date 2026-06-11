jest.mock('../../services/api', () => ({
  __esModule: true,
  default: { get: jest.fn(), post: jest.fn(), put: jest.fn(), patch: jest.fn(), delete: jest.fn() },
}));

import { configureStore } from '@reduxjs/toolkit';
import teamReducer, {
  teamSettingsPageOpened,
  inviteCoHost,
  bankAccountEdited,
  updateTeamFavoriteProject,
} from './TeamSlice';
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
  for (let i = 0; i < 30; i++) {
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

describe('bankAccountEdited', () => {
  test('après le debounce, le compte bancaire normalisé est sauvegardé puis le badge est effacé', async () => {
    jest.useFakeTimers('modern');
    try {
      mockedApi.patch.mockResolvedValue({ data: {} });
      const store = buildStore();

      store.dispatch(bankAccountEdited({
        teamId: 'team-1',
        iban: ' FR76 3000 1007 9412 ',
        bic: '',
        holderName: 'Jane Doe',
      }));
      expect(mockedApi.patch).not.toHaveBeenCalled();

      jest.advanceTimersByTime(801);
      await flushAll();
      await flushAll();

      expect(mockedApi.patch).toHaveBeenCalledWith(
        '/api/teams/team-1/bank-account',
        { iban: 'FR76 3000 1007 9412', bic: null, holderName: 'Jane Doe' },
        expect.anything()
      );
      expect(store.getState().team.bankSaveState).toBe('saved');

      jest.advanceTimersByTime(1501);
      await flushAll();
      await flushAll();
      expect(store.getState().team.bankSaveState).toBe('idle');
    } finally {
      jest.useRealTimers();
    }
  });

  test('un IBAN sans titulaire ne déclenche pas de sauvegarde', async () => {
    jest.useFakeTimers('modern');
    try {
      const store = buildStore();

      store.dispatch(bankAccountEdited({
        teamId: 'team-1',
        iban: 'FR76 3000 1007 9412',
        bic: '',
        holderName: '',
      }));
      jest.advanceTimersByTime(801);
      await flushAll();
      await flushAll();

      expect(mockedApi.patch).not.toHaveBeenCalled();
      expect(store.getState().team.bankSaveState).toBe('idle');
    } finally {
      jest.useRealTimers();
    }
  });

  test('plusieurs frappes rapprochées ne produisent qu\'une seule sauvegarde', async () => {
    jest.useFakeTimers('modern');
    try {
      mockedApi.patch.mockResolvedValue({ data: {} });
      const store = buildStore();

      store.dispatch(bankAccountEdited({ teamId: 'team-1', iban: '', bic: '', holderName: 'J' }));
      jest.advanceTimersByTime(400);
      await flushAll();
      await flushAll();
      store.dispatch(bankAccountEdited({ teamId: 'team-1', iban: '', bic: '', holderName: 'Jane' }));
      jest.advanceTimersByTime(801);
      await flushAll();
      await flushAll();

      expect(mockedApi.patch).toHaveBeenCalledTimes(1);
      expect(mockedApi.patch).toHaveBeenCalledWith(
        '/api/teams/team-1/bank-account',
        { iban: null, bic: null, holderName: 'Jane' },
        expect.anything()
      );
    } finally {
      jest.useRealTimers();
    }
  });

  test('en cas d\'échec, le store expose l\'erreur API', async () => {
    jest.useFakeTimers('modern');
    try {
      mockedApi.patch.mockRejectedValue({ response: { data: { detail: 'IBAN invalide' } } });
      const store = buildStore();

      store.dispatch(bankAccountEdited({ teamId: 'team-1', iban: '', bic: '', holderName: 'Jane' }));
      jest.advanceTimersByTime(801);
      await flushAll();
      await flushAll();

      expect(store.getState().team.bankSaveState).toBe('error');
      expect(store.getState().team.bankSaveError).toBe('IBAN invalide');
    } finally {
      jest.useRealTimers();
    }
  });
});

describe('updateTeamFavoriteProject — badge différé', () => {
  test('après succès et 1,5s, le store remet favoriteSaveState à idle', async () => {
    jest.useFakeTimers('modern');
    try {
      mockedApi.patch.mockResolvedValue({ data: {} });
      const store = buildStore();

      await store.dispatch(updateTeamFavoriteProject({ id: 'team-1', favoriteSolidarityProjectId: 'sp-1' }));
      expect(store.getState().team.favoriteSaveState).toBe('saved');

      await flushAll();
      jest.advanceTimersByTime(1501);
      await flushAll();
      await flushAll();

      expect(store.getState().team.favoriteSaveState).toBe('idle');
    } finally {
      jest.useRealTimers();
    }
  });
});
