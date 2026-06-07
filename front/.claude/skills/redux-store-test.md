# Skill: Redux Store Test

## When to use

Whenever a slice is created or modified. We test the store's observable behaviour:
**dispatch an action (sync reducer, async thunk, or an event handled by a listener)
and assert that `store.getState()` reflects the expected state.** Never test
components here (that belongs to testing-library) — only the Redux logic.

## Location & naming

- One test file per slice, next to the slice: `features/<feature>/<Feature>Slice.test.ts`.
- Listener tests go in `features/<feature>/<Feature>Listeners.test.ts`.
- One `describe` per action/thunk; test names describe the effect on the store
  (e.g. "stores items in the store after fulfilled").

## Slice test skeleton

```ts
// 1. Mock the API FIRST, before any import (Jest hoisting)
jest.mock('../../services/api', () => ({
  __esModule: true,
  default: { get: jest.fn(), post: jest.fn(), put: jest.fn(), patch: jest.fn(), delete: jest.fn() },
}));

import { configureStore } from '@reduxjs/toolkit';
import featureReducer, { setFilters, fetchItems } from './FeatureSlice';
import api from '../../services/api';

const mockedApi = api as jest.Mocked<typeof api>;

// 2. Fresh store per test, with only the relevant reducer(s)
const buildStore = () => configureStore({ reducer: { feature: featureReducer } });

beforeEach(() => {
  jest.clearAllMocks();
});

// 3a. Sync reducer: dispatch → getState
describe('setFilters', () => {
  test('merges the provided filters into the store', () => {
    const store = buildStore();
    store.dispatch(setFilters({ city: 'Paris' }));
    expect(store.getState().feature.filters.city).toBe('Paris');
  });
});

// 3b. Async thunk: mock the response, await the dispatch, assert state + API call
describe('fetchItems', () => {
  test('stores the items in the store after fulfilled', async () => {
    mockedApi.get.mockResolvedValue({ data: { 'hydra:member': [{ id: 'a-1' }] } });
    const store = buildStore();

    await store.dispatch(fetchItems());

    expect(store.getState().feature.status).toBe('succeeded');
    expect(store.getState().feature.items).toHaveLength(1);
    expect(mockedApi.get).toHaveBeenCalledWith('/api/items');
  });

  test('moves to failed with the error message after rejected', async () => {
    mockedApi.get.mockRejectedValue({ response: { data: { detail: 'boom' } } });
    const store = buildStore();

    await store.dispatch(fetchItems());

    expect(store.getState().feature.status).toBe('failed');
    expect(store.getState().feature.error).toBe('boom');
  });
});
```

## Key conventions

1. **`jest.mock('../../services/api', …)` at the very top**, before imports — Jest
   hoists the mock. Every axios method used is a `jest.fn()`.
2. **`buildStore()` rebuilds a store per test** with only the needed reducer(s) →
   isolation, no shared state across tests.
3. **`await store.dispatch(thunk(...))` is enough** to wait for a thunk: `fulfilled`
   and `rejected` update the store before the promise resolves.
4. **Cover both `fulfilled` AND `rejected`** for every thunk (succeeded/items vs
   failed/error), and assert the URL/params passed to the API (`toHaveBeenCalledWith`).
5. **Hydra responses**: mock `{ data: { 'hydra:member': [...] } }` (thunks read
   `hydra:member ?? member`).
6. **Errors**: mock `{ response: { data: { detail: '…' } } }` (thunks surface
   `err.response?.data?.detail` via `rejectWithValue`).
7. **`jest.clearAllMocks()` in `beforeEach`**.

## Special cases

### Listeners (event-driven) → test the side effect

When an event triggers a listener (`createListenerMiddleware`), build the store with
the middleware and **all the reducers it touches**, then wait for the async loop:

```ts
import { listenerMiddleware } from '../../store/listenerMiddleware';
import '../../store/registerListeners';

const buildStore = () => configureStore({
  reducer: { feature: featureReducer, other: otherReducer },
  middleware: (gdm) => gdm().prepend(listenerMiddleware.middleware),
});

// Let the thunks triggered by the listener settle
const flush = async () => { for (let i = 0; i < 5; i++) await Promise.resolve(); };

test('the event triggers the fetch (one event, one effect)', async () => {
  mockedApi.get.mockResolvedValue({ data: { 'hydra:member': [{ id: 'a-1' }] } });
  const store = buildStore();

  store.dispatch(pageOpened({ teamId: 't-1' }));
  await flush();

  expect(mockedApi.get).toHaveBeenCalledWith('/api/...', expect.anything());
  expect(store.getState().other.items).toHaveLength(1);
});
```

### Deferred effects (setTimeout)

```ts
jest.useFakeTimers('modern');
try {
  // ... dispatch + await flush()
  jest.advanceTimersByTime(2001);
  await flush();
  expect(store.getState().feature.status).toBe('idle');
} finally {
  jest.useRealTimers();
}
```

### localStorage

The jsdom environment provides `localStorage`. Call `localStorage.clear()` in
`beforeEach` when the slice persists to it (e.g. `auth`), and assert its content via
`JSON.parse(localStorage.getItem(KEY)!)`.

## Running the tests

```bash
cd front && CI=true npx react-scripts test --watchAll=false src/features/<feature>/<Feature>Slice.test.ts
# or the whole suite:
cd front && CI=true npx react-scripts test --watchAll=false
```

## Strict rules

1. **Always mock `services/api`** — no test makes a real network call.
2. **Assert the store state** (`store.getState()`), not the thunk's return value.
3. **A fresh store per test**, never a module-level shared store.
4. **Test both the happy path and the error path** of every thunk.
5. **No component tests here** — this skill covers Redux logic only.