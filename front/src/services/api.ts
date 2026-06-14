import axios from 'axios';

/** localStorage key holding the raw JWT Bearer token returned at login. */
export const AUTH_TOKEN_KEY = 'auth.token';
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

export const clearStoredAuth = (): void => {
  try {
    localStorage.removeItem(AUTH_TOKEN_KEY);
    localStorage.removeItem(AUTH_USER_KEY);
  } catch {
    /* ignore storage errors */
  }
};

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8080',
  headers: { Accept: 'application/ld+json' },
});

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
 * Callback invoked when the API answers 401. The store wires this to dispatch a
 * logout and redirect, keeping `services/api` free of any Redux/router import.
 */
let onUnauthorized: (() => void) | null = null;

export const setUnauthorizedHandler = (handler: (() => void) | null): void => {
  onUnauthorized = handler;
};

// On 401: purge the stored auth and let the app log out + redirect.
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error?.response?.status === 401) {
      clearStoredAuth();
      if (onUnauthorized) {
        onUnauthorized();
      }
    }
    return Promise.reject(error);
  }
);

export default api;
