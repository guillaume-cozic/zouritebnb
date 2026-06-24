import type { Mocked } from 'vitest';
vi.mock('../../services/api', async (importOriginal) => ({
  ...((await importOriginal()) as Record<string, unknown>),
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), patch: vi.fn(), delete: vi.fn() },
}));

import { configureStore } from '@reduxjs/toolkit';
import hostProfileReducer, { fetchHostProfile } from './HostProfileSlice';
import { selectHostProfileByTeamId } from './HostProfileSelectors';
import type { RootState } from '../../store';
import api from '../../services/api';

const mockedApi = api as Mocked<typeof api>;

const buildStore = () => configureStore({ reducer: { hostProfile: hostProfileReducer } });

beforeEach(() => {
  vi.clearAllMocks();
});

describe('fetchHostProfile', () => {
  test('le store indexe le profil hôte par teamId après fulfilled', async () => {
    mockedApi.get.mockResolvedValue({
      data: { firstName: 'Marie', lastName: 'Dupont', bio: 'Bonjour', avatarUrl: '/uploads/photos/a.jpg' },
    });
    const store = buildStore();

    await store.dispatch(fetchHostProfile('team-1'));

    expect(mockedApi.get).toHaveBeenCalledWith('/api/host-profiles/team-1');
    expect(selectHostProfileByTeamId('team-1')(store.getState() as unknown as RootState)).toEqual({
      teamId: 'team-1',
      firstName: 'Marie',
      lastName: 'Dupont',
      bio: 'Bonjour',
      avatarUrl: '/uploads/photos/a.jpg',
    });
  });

  test('un teamId déjà en cache ne déclenche pas de seconde requête', async () => {
    mockedApi.get.mockResolvedValue({ data: { firstName: 'Marie', lastName: null, bio: null, avatarUrl: null } });
    const store = buildStore();

    await store.dispatch(fetchHostProfile('team-1'));
    await store.dispatch(fetchHostProfile('team-1'));

    expect(mockedApi.get).toHaveBeenCalledTimes(1);
  });
});
