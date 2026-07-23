import axios, { AxiosError, InternalAxiosRequestConfig } from 'axios';

/** localStorage key holding the raw JWT Bearer token returned at login. */
export const AUTH_TOKEN_KEY = 'auth.token';
/** localStorage key holding the long-lived refresh token used to renew the JWT. */
export const AUTH_REFRESH_TOKEN_KEY = 'auth.refreshToken';
/** localStorage key holding the persisted authenticated user. */
export const AUTH_USER_KEY = 'auth.user';

export const getStoredToken = (): string | null => {
  try {
    return localStorage.getItem(AUTH_TOKEN_KEY);
  } catch {
    return null;
  }
};

export const setStoredToken = (token: string): void => {
  try {
    localStorage.setItem(AUTH_TOKEN_KEY, token);
  } catch {
    /* ignore storage errors */
  }
};

export const getStoredRefreshToken = (): string | null => {
  try {
    return localStorage.getItem(AUTH_REFRESH_TOKEN_KEY);
  } catch {
    return null;
  }
};

export const setStoredRefreshToken = (token: string): void => {
  try {
    localStorage.setItem(AUTH_REFRESH_TOKEN_KEY, token);
  } catch {
    /* ignore storage errors */
  }
};

/**
 * True when a stored JWT exists and its `exp` claim is still in the future.
 * The persisted user alone does not prove an active session: the API token
 * expires (lexik TTL) while localStorage keeps the user forever.
 */
export const hasValidStoredToken = (): boolean => {
  const token = getStoredToken();
  if (!token) return false;
  const parts = token.split('.');
  if (parts.length !== 3) return false;
  try {
    const payload = JSON.parse(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')));
    return typeof payload.exp === 'number' && payload.exp * 1000 > Date.now();
  } catch {
    return false;
  }
};

/**
 * A session is renewable when the JWT is still valid, or when it has expired but
 * a refresh token remains: the next request will transparently exchange it for a
 * fresh JWT. Only when neither holds is the session truly dead.
 */
export const hasRenewableSession = (): boolean =>
  hasValidStoredToken() || getStoredRefreshToken() !== null;

export const clearStoredAuth = (): void => {
  try {
    localStorage.removeItem(AUTH_TOKEN_KEY);
    localStorage.removeItem(AUTH_REFRESH_TOKEN_KEY);
    localStorage.removeItem(AUTH_USER_KEY);
  } catch {
    /* ignore storage errors */
  }
};

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8080',
  headers: { Accept: 'application/ld+json' },
});

/** Path of the token-renewal endpoint (relative to the API base URL). */
const REFRESH_PATH = '/api/token/refresh';

// Attach the Bearer token automatically when one is stored.
api.interceptors.request.use((config) => {
  const token = getStoredToken();
  if (token) {
    config.headers = config.headers ?? {};
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

/**
 * Callback invoked when the session cannot be recovered (no/invalid refresh
 * token). The store wires this to dispatch a logout and redirect, keeping
 * `services/api` free of any Redux/router import.
 */
let onUnauthorized: (() => void) | null = null;

export const setUnauthorizedHandler = (handler: (() => void) | null): void => {
  onUnauthorized = handler;
};

/**
 * Exchange the stored refresh token for a fresh JWT (and rotated refresh token).
 * Uses a bare axios call so it never re-enters this instance's interceptors.
 * Concurrent 401s share a single in-flight refresh via `refreshPromise`.
 */
let refreshPromise: Promise<string> | null = null;

const performRefresh = async (): Promise<string> => {
  const refreshToken = getStoredRefreshToken();
  if (!refreshToken) {
    throw new Error('No refresh token available');
  }
  const { data } = await axios.post(
    `${api.defaults.baseURL ?? ''}${REFRESH_PATH}`,
    { refresh_token: refreshToken },
    { headers: { 'Content-Type': 'application/json' } }
  );
  const newToken = data?.token as string | undefined;
  if (!newToken) {
    throw new Error('Refresh response did not contain a token');
  }
  setStoredToken(newToken);
  if (typeof data?.refresh_token === 'string') {
    setStoredRefreshToken(data.refresh_token);
  }
  return newToken;
};

interface RetriableConfig extends InternalAxiosRequestConfig {
  _retry?: boolean;
}

// On 401: try to renew the JWT from the refresh token and replay the request.
// Only when renewal is impossible do we purge the session and log out.
api.interceptors.response.use(
  (response) => response,
  async (error: AxiosError) => {
    const original = error.config as RetriableConfig | undefined;
    const isUnauthorized = error.response?.status === 401;
    const isRefreshCall = original?.url?.includes(REFRESH_PATH) ?? false;

    if (isUnauthorized && original && !original._retry && !isRefreshCall && getStoredRefreshToken()) {
      original._retry = true;
      try {
        refreshPromise =
          refreshPromise ?? performRefresh().finally(() => { refreshPromise = null; });
        const newToken = await refreshPromise;
        original.headers = original.headers ?? {};
        original.headers.Authorization = `Bearer ${newToken}`;
        return api(original);
      } catch {
        clearStoredAuth();
        onUnauthorized?.();
        return Promise.reject(error);
      }
    }

    if (isUnauthorized) {
      clearStoredAuth();
      onUnauthorized?.();
    }
    return Promise.reject(error);
  }
);

export default api;
