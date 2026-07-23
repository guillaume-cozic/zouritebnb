/**
 * Unit tests for the shared API client: it attaches the Bearer token from
 * localStorage on every request; on a 401 it tries to renew the JWT from the
 * stored refresh token and replays the request, and only when renewal is
 * impossible does it purge the stored auth and invoke the unauthorized handler.
 */

// Capture the request/response interceptors axios registers so we can drive them,
// and expose the mocked instance + axios.post so tests can assert the refresh flow.
// vi.hoisted keeps these initialised when the (hoisted) vi.mock factory runs.
const { requestInterceptors, responseInterceptors, apiInstance, axiosPost } = vi.hoisted(() => {
  const requestInterceptors = [] as Array<(config: any) => any>;
  const responseInterceptors = [] as Array<{ onFulfilled: (r: any) => any; onRejected: (e: any) => any }>;
  // The instance returned by axios.create() is callable (used to replay requests).
  const apiInstance: any = vi.fn((config: any) => Promise.resolve({ replayed: true, config }));
  apiInstance.interceptors = {
    request: { use: (fn: (config: any) => any) => requestInterceptors.push(fn) },
    response: {
      use: (onFulfilled: (r: any) => any, onRejected: (e: any) => any) =>
        responseInterceptors.push({ onFulfilled, onRejected }),
    },
  };
  apiInstance.defaults = { baseURL: 'http://api.test' };
  const axiosPost = vi.fn();
  return { requestInterceptors, responseInterceptors, apiInstance, axiosPost };
});

vi.mock('axios', () => ({
  __esModule: true,
  default: { create: () => apiInstance, post: axiosPost },
}));

import {
  AUTH_TOKEN_KEY,
  AUTH_REFRESH_TOKEN_KEY,
  AUTH_USER_KEY,
  setUnauthorizedHandler,
  clearStoredAuth,
  hasValidStoredToken,
  hasRenewableSession,
} from './api';

/** Builds an unsigned JWT-shaped token whose payload carries the given claims. */
const makeJwt = (payload: object): string =>
  `header.${btoa(JSON.stringify(payload)).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '')}.signature`;

const runRequestInterceptor = (config: any) =>
  requestInterceptors.reduce((acc, fn) => fn(acc), config);

const runResponseRejected = (error: any) =>
  responseInterceptors[0].onRejected(error).catch((e: any) => e);

beforeEach(() => {
  localStorage.clear();
  setUnauthorizedHandler(null);
  vi.clearAllMocks();
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

describe('response interceptor (401) without a refresh token', () => {
  test('purges stored auth and calls the unauthorized handler on 401', async () => {
    localStorage.setItem(AUTH_TOKEN_KEY, 'jwt-xyz');
    localStorage.setItem(AUTH_USER_KEY, JSON.stringify({ id: 'u-1' }));
    const handler = vi.fn();
    setUnauthorizedHandler(handler);

    await runResponseRejected({ response: { status: 401 }, config: { headers: {} } });

    expect(localStorage.getItem(AUTH_TOKEN_KEY)).toBeNull();
    expect(localStorage.getItem(AUTH_USER_KEY)).toBeNull();
    expect(handler).toHaveBeenCalledTimes(1);
    expect(axiosPost).not.toHaveBeenCalled();
  });

  test('leaves auth untouched and does not call the handler on other errors', async () => {
    localStorage.setItem(AUTH_TOKEN_KEY, 'jwt-xyz');
    const handler = vi.fn();
    setUnauthorizedHandler(handler);

    await runResponseRejected({ response: { status: 500 }, config: { headers: {} } });

    expect(localStorage.getItem(AUTH_TOKEN_KEY)).toBe('jwt-xyz');
    expect(handler).not.toHaveBeenCalled();
  });

  test('rejects with the original error so callers still see it', async () => {
    const original = { response: { status: 401 }, config: { headers: {} }, message: 'boom' };

    const result = await runResponseRejected(original);

    expect(result).toBe(original);
  });
});

describe('response interceptor (401) with a refresh token', () => {
  test('renews the JWT and replays the request on 401', async () => {
    localStorage.setItem(AUTH_TOKEN_KEY, 'stale-jwt');
    localStorage.setItem(AUTH_REFRESH_TOKEN_KEY, 'refresh-1');
    localStorage.setItem(AUTH_USER_KEY, JSON.stringify({ id: 'u-1' }));
    const handler = vi.fn();
    setUnauthorizedHandler(handler);
    axiosPost.mockResolvedValue({ data: { token: 'fresh-jwt', refresh_token: 'refresh-2' } });

    const original = { response: { status: 401 }, config: { headers: {}, url: '/api/conversations' } };
    const result = await responseInterceptors[0].onRejected(original);

    // The refresh endpoint was called with the stored refresh token.
    expect(axiosPost).toHaveBeenCalledWith(
      'http://api.test/api/token/refresh',
      { refresh_token: 'refresh-1' },
      { headers: { 'Content-Type': 'application/json' } }
    );
    // The new token + rotated refresh token are persisted.
    expect(localStorage.getItem(AUTH_TOKEN_KEY)).toBe('fresh-jwt');
    expect(localStorage.getItem(AUTH_REFRESH_TOKEN_KEY)).toBe('refresh-2');
    // The original request was replayed with the fresh Bearer, and the session survives.
    expect(apiInstance).toHaveBeenCalledTimes(1);
    expect(result.config.headers.Authorization).toBe('Bearer fresh-jwt');
    expect(handler).not.toHaveBeenCalled();
  });

  test('logs out when the refresh call itself fails', async () => {
    localStorage.setItem(AUTH_TOKEN_KEY, 'stale-jwt');
    localStorage.setItem(AUTH_REFRESH_TOKEN_KEY, 'refresh-1');
    localStorage.setItem(AUTH_USER_KEY, JSON.stringify({ id: 'u-1' }));
    const handler = vi.fn();
    setUnauthorizedHandler(handler);
    axiosPost.mockRejectedValue({ response: { status: 401 } });

    const original = { response: { status: 401 }, config: { headers: {}, url: '/api/conversations' } };
    await runResponseRejected(original);

    expect(localStorage.getItem(AUTH_TOKEN_KEY)).toBeNull();
    expect(localStorage.getItem(AUTH_REFRESH_TOKEN_KEY)).toBeNull();
    expect(localStorage.getItem(AUTH_USER_KEY)).toBeNull();
    expect(handler).toHaveBeenCalledTimes(1);
  });

  test('does not attempt to refresh a failing refresh call (no loop)', async () => {
    localStorage.setItem(AUTH_REFRESH_TOKEN_KEY, 'refresh-1');
    const handler = vi.fn();
    setUnauthorizedHandler(handler);

    // A 401 on the refresh endpoint itself must not trigger another refresh.
    await runResponseRejected({ response: { status: 401 }, config: { headers: {}, url: '/api/token/refresh' } });

    expect(axiosPost).not.toHaveBeenCalled();
    expect(handler).toHaveBeenCalledTimes(1);
  });
});

describe('hasValidStoredToken', () => {
  test('true when the stored JWT expires in the future', () => {
    localStorage.setItem(AUTH_TOKEN_KEY, makeJwt({ exp: Math.floor(Date.now() / 1000) + 3600 }));

    expect(hasValidStoredToken()).toBe(true);
  });

  test('false when the stored JWT is expired', () => {
    localStorage.setItem(AUTH_TOKEN_KEY, makeJwt({ exp: Math.floor(Date.now() / 1000) - 60 }));

    expect(hasValidStoredToken()).toBe(false);
  });

  test('false when no token is stored', () => {
    expect(hasValidStoredToken()).toBe(false);
  });

  test('false when the token is not a decodable JWT', () => {
    localStorage.setItem(AUTH_TOKEN_KEY, 'not-a-jwt');

    expect(hasValidStoredToken()).toBe(false);
  });

  test('false when the payload has no numeric exp claim', () => {
    localStorage.setItem(AUTH_TOKEN_KEY, makeJwt({ sub: 'u-1' }));

    expect(hasValidStoredToken()).toBe(false);
  });
});

describe('hasRenewableSession', () => {
  test('true when the JWT is still valid', () => {
    localStorage.setItem(AUTH_TOKEN_KEY, makeJwt({ exp: Math.floor(Date.now() / 1000) + 3600 }));

    expect(hasRenewableSession()).toBe(true);
  });

  test('true when the JWT is expired but a refresh token remains', () => {
    localStorage.setItem(AUTH_TOKEN_KEY, makeJwt({ exp: Math.floor(Date.now() / 1000) - 60 }));
    localStorage.setItem(AUTH_REFRESH_TOKEN_KEY, 'refresh-1');

    expect(hasRenewableSession()).toBe(true);
  });

  test('false when neither a valid JWT nor a refresh token exists', () => {
    localStorage.setItem(AUTH_TOKEN_KEY, makeJwt({ exp: Math.floor(Date.now() / 1000) - 60 }));

    expect(hasRenewableSession()).toBe(false);
  });
});

describe('clearStoredAuth', () => {
  test('removes the token, refresh token and user from localStorage', () => {
    localStorage.setItem(AUTH_TOKEN_KEY, 'jwt-xyz');
    localStorage.setItem(AUTH_REFRESH_TOKEN_KEY, 'refresh-1');
    localStorage.setItem(AUTH_USER_KEY, '{}');

    clearStoredAuth();

    expect(localStorage.getItem(AUTH_TOKEN_KEY)).toBeNull();
    expect(localStorage.getItem(AUTH_REFRESH_TOKEN_KEY)).toBeNull();
    expect(localStorage.getItem(AUTH_USER_KEY)).toBeNull();
  });
});
