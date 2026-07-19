import type { Mocked } from 'vitest';
vi.mock('../../services/api', () => {
  const AUTH_TOKEN_KEY = 'auth.token';
  const AUTH_USER_KEY = 'auth.user';
  return {
    __esModule: true,
    default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), patch: vi.fn(), delete: vi.fn() },
    AUTH_TOKEN_KEY,
    AUTH_USER_KEY,
    setStoredToken: (token: string) => globalThis.localStorage.setItem(AUTH_TOKEN_KEY, token),
    clearStoredAuth: () => {
      globalThis.localStorage.removeItem(AUTH_TOKEN_KEY);
      globalThis.localStorage.removeItem(AUTH_USER_KEY);
    },
  };
});

import { configureStore } from '@reduxjs/toolkit';
import authReducer, {
  logout,
  loginUser,
  registerUser,
  socialLogin,
  updateUserProfile,
  requestPasswordReset,
  resetPassword,
  verifyEmail,
} from './AuthSlice';
import api from '../../services/api';

const mockedApi = api as Mocked<typeof api>;

const buildStore = () => configureStore({ reducer: { auth: authReducer } });

const user = { id: 'u-1', email: 'a@b.fr', firstName: null, lastName: null };
const userWithToken = { ...user, token: 'jwt-abc-123' };

beforeEach(() => {
  vi.clearAllMocks();
  localStorage.clear();
});

describe('loginUser', () => {
  test('le store enregistre l\'utilisateur et le persiste après fulfilled', async () => {
    mockedApi.post.mockResolvedValue({ data: user });
    const store = buildStore();

    await store.dispatch(loginUser({ email: 'a@b.fr', password: 'secret' }));

    const state = store.getState().auth;
    expect(state.status).toBe('succeeded');
    expect(state.user).toEqual(user);
    expect(JSON.parse(localStorage.getItem('auth.user')!)).toEqual(user);
  });

  test('le store persiste le token JWT renvoyé au login', async () => {
    mockedApi.post.mockResolvedValue({ data: userWithToken });
    const store = buildStore();

    await store.dispatch(loginUser({ email: 'a@b.fr', password: 'secret' }));

    const state = store.getState().auth;
    expect(state.user?.token).toBe('jwt-abc-123');
    // The raw token is persisted under its own key for the request interceptor.
    expect(localStorage.getItem('auth.token')).toBe('jwt-abc-123');
  });

  test('le store passe à failed avec le message d\'erreur après rejected', async () => {
    mockedApi.post.mockRejectedValue({ response: { data: { detail: 'Identifiants invalides' } } });
    const store = buildStore();

    await store.dispatch(loginUser({ email: 'a@b.fr', password: 'wrong' }));

    const state = store.getState().auth;
    expect(state.status).toBe('failed');
    expect(state.error).toBe('Identifiants invalides');
    expect(state.user).toBeNull();
  });
});

describe('socialLogin', () => {
  test('le store connecte l\'utilisateur et persiste le token après fulfilled', async () => {
    mockedApi.post.mockResolvedValue({ data: userWithToken });
    const store = buildStore();

    await store.dispatch(socialLogin({ provider: 'google', token: 'google-id-token' }));

    expect(mockedApi.post).toHaveBeenCalledWith(
      '/api/auth/social',
      { provider: 'google', token: 'google-id-token' },
      { headers: { 'Content-Type': 'application/ld+json' } }
    );
    const state = store.getState().auth;
    expect(state.status).toBe('succeeded');
    expect(state.user?.token).toBe('jwt-abc-123');
    expect(localStorage.getItem('auth.token')).toBe('jwt-abc-123');
  });

  test('le store expose l\'erreur quand le fournisseur est rejeté', async () => {
    mockedApi.post.mockRejectedValue({
      response: { data: { detail: 'The google token could not be verified.' } },
    });
    const store = buildStore();

    await store.dispatch(socialLogin({ provider: 'google', token: 'forged' }));

    const state = store.getState().auth;
    expect(state.status).toBe('failed');
    expect(state.error).toBe('The google token could not be verified.');
    expect(state.user).toBeNull();
  });
});

describe('registerUser', () => {
  test('le store connecte l\'utilisateur fraîchement inscrit', async () => {
    mockedApi.post.mockResolvedValue({ data: user });
    const store = buildStore();

    await store.dispatch(registerUser({ email: 'a@b.fr', password: 'secret' }));

    expect(store.getState().auth.user).toEqual(user);
  });
});

describe('updateUserProfile', () => {
  test('le store met à jour les champs de profil de l\'utilisateur connecté', async () => {
    mockedApi.post.mockResolvedValue({ data: user });
    mockedApi.patch.mockResolvedValue({ data: {} });
    const store = buildStore();
    await store.dispatch(loginUser({ email: 'a@b.fr', password: 'secret' }));

    await store.dispatch(
      updateUserProfile({ firstName: 'Jane', lastName: 'Doe', email: 'jane@doe.fr', bio: 'Bonjour' })
    );

    const updated = store.getState().auth.user;
    expect(updated?.firstName).toBe('Jane');
    expect(updated?.lastName).toBe('Doe');
    expect(updated?.email).toBe('jane@doe.fr');
    expect(updated?.bio).toBe('Bonjour');
  });
});

describe('requestPasswordReset', () => {
  test('appelle l\'endpoint forgot-password et résout sans erreur', async () => {
    mockedApi.post.mockResolvedValue({ data: '' });
    const store = buildStore();

    const result = await store.dispatch(requestPasswordReset({ email: 'a@b.fr' }));

    expect(requestPasswordReset.fulfilled.match(result)).toBe(true);
    expect(mockedApi.post).toHaveBeenCalledWith('/api/forgot-password', { email: 'a@b.fr' }, expect.anything());
  });
});

describe('resetPassword', () => {
  test('rejette avec un message quand le jeton est invalide', async () => {
    mockedApi.post.mockRejectedValue({ response: { data: { detail: 'Lien invalide' } } });
    const store = buildStore();

    const result = await store.dispatch(resetPassword({ token: 'bad', password: 'newsecret' }));

    expect(resetPassword.rejected.match(result)).toBe(true);
    expect(result.payload).toBe('Lien invalide');
  });
});

describe('verifyEmail', () => {
  test('bascule emailVerified à true sur l\'utilisateur connecté après fulfilled', async () => {
    mockedApi.post.mockResolvedValue({ data: { ...user, emailVerified: false } });
    const store = buildStore();
    await store.dispatch(loginUser({ email: 'a@b.fr', password: 'secret' }));
    expect(store.getState().auth.user?.emailVerified).toBe(false);

    mockedApi.post.mockResolvedValue({ data: '' });
    await store.dispatch(verifyEmail({ token: 'tok' }));

    expect(store.getState().auth.user?.emailVerified).toBe(true);
    expect(JSON.parse(localStorage.getItem('auth.user')!).emailVerified).toBe(true);
  });
});

describe('logout', () => {
  test('le store vide l\'utilisateur et nettoie le localStorage (user + token)', async () => {
    mockedApi.post.mockResolvedValue({ data: userWithToken });
    const store = buildStore();
    await store.dispatch(loginUser({ email: 'a@b.fr', password: 'secret' }));

    store.dispatch(logout());

    expect(store.getState().auth.user).toBeNull();
    expect(localStorage.getItem('auth.user')).toBeNull();
    expect(localStorage.getItem('auth.token')).toBeNull();
  });
});