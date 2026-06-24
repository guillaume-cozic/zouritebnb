import type { Mocked } from 'vitest';
vi.mock('../../services/api', () => ({
  __esModule: true,
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), patch: vi.fn(), delete: vi.fn() },
  AUTH_USER_KEY: 'auth.user',
  clearStoredAuth: vi.fn(),
  setStoredToken: vi.fn(),
}));

import { configureStore } from '@reduxjs/toolkit';
import authReducer, { profileEdited } from './AuthSlice';
import userProfileReducer from '../userProfile/UserProfileSlice';
import { listenerMiddleware } from '../../store/listenerMiddleware';
import './AuthListeners';
import api from '../../services/api';

const mockedApi = api as Mocked<typeof api>;

const buildStore = () =>
  configureStore({
    reducer: { auth: authReducer, userProfile: userProfileReducer },
    middleware: (gdm) => gdm().prepend(listenerMiddleware.middleware),
  });

const flush = async () => {
  for (let i = 0; i < 30; i++) await Promise.resolve();
};

beforeEach(() => {
  vi.clearAllMocks();
  localStorage.clear();
});

describe('profileEdited', () => {
  test('saves the profile after the debounce delay and clears the badge', async () => {
    vi.useFakeTimers();
    try {
      mockedApi.patch.mockResolvedValue({ data: {} });
      const store = buildStore();

      store.dispatch(profileEdited({
        userId: 'u-1',
        firstName: 'Jane',
        lastName: '',
        email: 'jane@example.com',
        bio: '',
      }));
      expect(mockedApi.patch).not.toHaveBeenCalled();

      vi.advanceTimersByTime(801);
      await flush();

      expect(mockedApi.patch).toHaveBeenCalledWith(
        '/api/users/profile',
        { firstName: 'Jane', lastName: null, email: 'jane@example.com', bio: null },
        expect.anything()
      );
      expect(store.getState().auth.profileSaveState).toBe('saved');

      vi.advanceTimersByTime(1501);
      await flush();
      expect(store.getState().auth.profileSaveState).toBe('idle');
    } finally {
      vi.useRealTimers();
    }
  });

  test('skips the save while the email is empty', async () => {
    vi.useFakeTimers();
    try {
      const store = buildStore();

      store.dispatch(profileEdited({ userId: 'u-1', firstName: 'Jane', lastName: 'Doe', email: '', bio: '' }));
      vi.advanceTimersByTime(801);
      await flush();

      expect(mockedApi.patch).not.toHaveBeenCalled();
      expect(store.getState().auth.profileSaveState).toBe('idle');
    } finally {
      vi.useRealTimers();
    }
  });

  test('debounces successive edits into a single save', async () => {
    vi.useFakeTimers();
    try {
      mockedApi.patch.mockResolvedValue({ data: {} });
      const store = buildStore();

      store.dispatch(profileEdited({ userId: 'u-1', firstName: 'J', lastName: '', email: 'jane@example.com', bio: '' }));
      vi.advanceTimersByTime(400);
      await flush();
      store.dispatch(profileEdited({ userId: 'u-1', firstName: 'Jane', lastName: '', email: 'jane@example.com', bio: '' }));
      vi.advanceTimersByTime(801);
      await flush();

      expect(mockedApi.patch).toHaveBeenCalledTimes(1);
      expect(mockedApi.patch).toHaveBeenCalledWith(
        '/api/users/profile',
        { firstName: 'Jane', lastName: null, email: 'jane@example.com', bio: null },
        expect.anything()
      );
    } finally {
      vi.useRealTimers();
    }
  });

  test('marks the profile in error when the save fails', async () => {
    vi.useFakeTimers();
    try {
      mockedApi.patch.mockRejectedValue({ response: { data: { detail: 'boom' } } });
      const store = buildStore();

      store.dispatch(profileEdited({ userId: 'u-1', firstName: 'Jane', lastName: '', email: 'jane@example.com', bio: '' }));
      vi.advanceTimersByTime(801);
      await flush();

      expect(store.getState().auth.profileSaveState).toBe('error');
    } finally {
      vi.useRealTimers();
    }
  });
});
