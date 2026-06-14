/**
 * Unit tests for the shared API client: it attaches the Bearer token from
 * localStorage on every request, and on a 401 response it purges the stored
 * auth and invokes the registered unauthorized handler.
 */

// Capture the request/response interceptors axios registers so we can drive them.
// vi.hoisted keeps these initialised when the (hoisted) vi.mock factory runs.
const { requestInterceptors, responseInterceptors } = vi.hoisted(() => ({
  requestInterceptors: [] as Array<(config: any) => any>,
  responseInterceptors: [] as Array<{ onFulfilled: (r: any) => any; onRejected: (e: any) => any }>,
}));

vi.mock('axios', () => ({
  __esModule: true,
  default: {
    create: () => ({
      interceptors: {
        request: { use: (fn: (config: any) => any) => requestInterceptors.push(fn) },
        response: {
          use: (onFulfilled: (r: any) => any, onRejected: (e: any) => any) =>
            responseInterceptors.push({ onFulfilled, onRejected }),
        },
      },
    }),
  },
}));

import {
  AUTH_TOKEN_KEY,
  AUTH_USER_KEY,
  setUnauthorizedHandler,
  clearStoredAuth,
} from './api';

const runRequestInterceptor = (config: any) =>
  requestInterceptors.reduce((acc, fn) => fn(acc), config);

const runResponseRejected = (error: any) =>
  responseInterceptors[0].onRejected(error).catch((e: any) => e);

beforeEach(() => {
  localStorage.clear();
  setUnauthorizedHandler(null);
});

describe('request interceptor', () => {
  test('attaches the Bearer token when one is stored', () => {
    localStorage.setItem(AUTH_TOKEN_KEY, 'jwt-xyz');

    const config = runRequestInterceptor({ headers: {} });

    expect(config.headers.Authorization).toBe('Bearer jwt-xyz');
  });

  test('does not set Authorization when no token is stored', () => {
    const config = runRequestInterceptor({ headers: {} });

    expect(config.headers.Authorization).toBeUndefined();
  });
});

describe('response interceptor (401)', () => {
  test('purges stored auth and calls the unauthorized handler on 401', async () => {
    localStorage.setItem(AUTH_TOKEN_KEY, 'jwt-xyz');
    localStorage.setItem(AUTH_USER_KEY, JSON.stringify({ id: 'u-1' }));
    const handler = vi.fn();
    setUnauthorizedHandler(handler);

    await runResponseRejected({ response: { status: 401 } });

    expect(localStorage.getItem(AUTH_TOKEN_KEY)).toBeNull();
    expect(localStorage.getItem(AUTH_USER_KEY)).toBeNull();
    expect(handler).toHaveBeenCalledTimes(1);
  });

  test('leaves auth untouched and does not call the handler on other errors', async () => {
    localStorage.setItem(AUTH_TOKEN_KEY, 'jwt-xyz');
    const handler = vi.fn();
    setUnauthorizedHandler(handler);

    await runResponseRejected({ response: { status: 500 } });

    expect(localStorage.getItem(AUTH_TOKEN_KEY)).toBe('jwt-xyz');
    expect(handler).not.toHaveBeenCalled();
  });

  test('rejects with the original error so callers still see it', async () => {
    const original = { response: { status: 401 }, message: 'boom' };

    const result = await runResponseRejected(original);

    expect(result).toBe(original);
  });
});

describe('clearStoredAuth', () => {
  test('removes both the token and the user from localStorage', () => {
    localStorage.setItem(AUTH_TOKEN_KEY, 'jwt-xyz');
    localStorage.setItem(AUTH_USER_KEY, '{}');

    clearStoredAuth();

    expect(localStorage.getItem(AUTH_TOKEN_KEY)).toBeNull();
    expect(localStorage.getItem(AUTH_USER_KEY)).toBeNull();
  });
});
