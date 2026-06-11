jest.mock('../../services/api', () => ({
  __esModule: true,
  default: { get: jest.fn(), post: jest.fn(), put: jest.fn(), patch: jest.fn(), delete: jest.fn() },
  AUTH_USER_KEY: 'auth.user',
  clearStoredAuth: jest.fn(),
  setStoredToken: jest.fn(),
}));

import { configureStore } from '@reduxjs/toolkit';
import authReducer, { profileEdited } from './AuthSlice';
import userProfileReducer from '../userProfile/UserProfileSlice';
import { listenerMiddleware } from '../../store/listenerMiddleware';
import './AuthListeners';
import api from '../../services/api';

const mockedApi = api as jest.Mocked<typeof api>;

const buildStore = () =>
  configureStore({
    reducer: { auth: authReducer, userProfile: userProfileReducer },
    middleware: (gdm) => gdm().prepend(listenerMiddleware.middleware),
  });

const flush = async () => {
  for (let i = 0; i < 30; i++) await Promise.resolve();
};

beforeEach(() => {
  jest.clearAllMocks();
  localStorage.clear();
});

describe('profileEdited', () => {
  test('saves the profile after the debounce delay and clears the badge', async () => {
    jest.useFakeTimers();
    try {
      mockedApi.patch.mockResolvedValue({ data: {} });
      const store = buildStore();

      store.dispatch(profileEdited({
        userId: 'u-1',
        firstName: 'Jane',
        lastName: '',
        email: 'jane@example.com',
      }));
      expect(mockedApi.patch).not.toHaveBeenCalled();

      jest.advanceTimersByTime(801);
      await flush();

      expect(mockedApi.patch).toHaveBeenCalledWith(
        '/api/users/u-1/profile',
        { firstName: 'Jane', lastName: null, email: 'jane@example.com' },
        expect.anything()
      );
      expect(store.getState().auth.profileSaveState).toBe('saved');

      jest.advanceTimersByTime(1501);
      await flush();
      expect(store.getState().auth.profileSaveState).toBe('idle');
    } finally {
      jest.useRealTimers();
    }
  });

  test('skips the save while the email is empty', async () => {
    jest.useFakeTimers();
    try {
      const store = buildStore();

      store.dispatch(profileEdited({ userId: 'u-1', firstName: 'Jane', lastName: 'Doe', email: '' }));
      jest.advanceTimersByTime(801);
      await flush();

      expect(mockedApi.patch).not.toHaveBeenCalled();
      expect(store.getState().auth.profileSaveState).toBe('idle');
    } finally {
      jest.useRealTimers();
    }
  });

  test('debounces successive edits into a single save', async () => {
    jest.useFakeTimers();
    try {
      mockedApi.patch.mockResolvedValue({ data: {} });
      const store = buildStore();

      store.dispatch(profileEdited({ userId: 'u-1', firstName: 'J', lastName: '', email: 'jane@example.com' }));
      jest.advanceTimersByTime(400);
      await flush();
      store.dispatch(profileEdited({ userId: 'u-1', firstName: 'Jane', lastName: '', email: 'jane@example.com' }));
      jest.advanceTimersByTime(801);
      await flush();

      expect(mockedApi.patch).toHaveBeenCalledTimes(1);
      expect(mockedApi.patch).toHaveBeenCalledWith(
        '/api/users/u-1/profile',
        { firstName: 'Jane', lastName: null, email: 'jane@example.com' },
        expect.anything()
      );
    } finally {
      jest.useRealTimers();
    }
  });

  test('marks the profile in error when the save fails', async () => {
    jest.useFakeTimers();
    try {
      mockedApi.patch.mockRejectedValue({ response: { data: { detail: 'boom' } } });
      const store = buildStore();

      store.dispatch(profileEdited({ userId: 'u-1', firstName: 'Jane', lastName: '', email: 'jane@example.com' }));
      jest.advanceTimersByTime(801);
      await flush();

      expect(store.getState().auth.profileSaveState).toBe('error');
    } finally {
      jest.useRealTimers();
    }
  });
});
