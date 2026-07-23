import type { Mocked } from 'vitest';
vi.mock('../../services/api', async (importOriginal) => ({
  ...((await importOriginal()) as Record<string, unknown>),
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), patch: vi.fn(), delete: vi.fn() },
}));

import { configureStore } from '@reduxjs/toolkit';
import contactReducer, { contactPageLeft, sendContactMessage } from './ContactSlice';
import api from '../../services/api';

const mockedApi = api as Mocked<typeof api>;

const buildStore = () => configureStore({ reducer: { contact: contactReducer } });

const payload = {
  name: 'Jean Dupont',
  email: 'jean@example.com',
  subject: 'Question sur une réservation',
  message: 'Bonjour, je souhaite en savoir plus.',
};

beforeEach(() => {
  vi.clearAllMocks();
});

describe('sendContactMessage', () => {
  test('le store passe à succeeded après fulfilled', async () => {
    mockedApi.post.mockResolvedValue({ data: {} });
    const store = buildStore();

    const result = await store.dispatch(sendContactMessage(payload));

    expect(store.getState().contact.status).toBe('succeeded');
    expect(sendContactMessage.fulfilled.match(result)).toBe(true);
    expect(mockedApi.post).toHaveBeenCalledWith(
      '/api/contact_messages',
      payload,
      expect.anything()
    );
  });

  test('le store passe à failed avec le message d\'erreur après rejected', async () => {
    mockedApi.post.mockRejectedValue({ response: { data: { detail: 'Email is invalid.' } } });
    const store = buildStore();

    await store.dispatch(sendContactMessage({ ...payload, email: 'invalide' }));

    const state = store.getState().contact;
    expect(state.status).toBe('failed');
    expect(state.error).toBe('Email is invalid.');
  });
});

describe('contactPageLeft', () => {
  test('le store revient à idle et efface l\'erreur', async () => {
    mockedApi.post.mockRejectedValue({ response: { data: { detail: 'Email is invalid.' } } });
    const store = buildStore();
    await store.dispatch(sendContactMessage(payload));

    store.dispatch(contactPageLeft());

    const state = store.getState().contact;
    expect(state.status).toBe('idle');
    expect(state.error).toBeNull();
  });
});
