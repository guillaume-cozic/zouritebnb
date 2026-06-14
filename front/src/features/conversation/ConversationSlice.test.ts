import type { Mocked } from 'vitest';
vi.mock('../../services/api', async (importOriginal) => ({
  ...((await importOriginal()) as Record<string, unknown>),
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), patch: vi.fn(), delete: vi.fn() },
}));

import { configureStore } from '@reduxjs/toolkit';
import conversationReducer, {
  clearCurrent,
  clearSendError,
  fetchConversationsForUser,
  fetchConversationById,
  sendMessage,
  markConversationRead,
} from './ConversationSlice';
import { selectUnreadCount } from './ConversationSelectors';
import api from '../../services/api';

const mockedApi = api as Mocked<typeof api>;

const buildStore = () => configureStore({ reducer: { conversation: conversationReducer } });

beforeEach(() => {
  vi.clearAllMocks();
  localStorage.clear();
});

describe('fetchConversationsForUser', () => {
  test('le store stocke la liste des conversations après fulfilled', async () => {
    mockedApi.get.mockResolvedValue({ data: { 'hydra:member': [{ id: 'c-1' }] } });
    const store = buildStore();

    await store.dispatch(fetchConversationsForUser());

    const state = store.getState().conversation;
    expect(state.listStatus).toBe('succeeded');
    expect(state.items).toHaveLength(1);
    // The backend scopes the collection to the authenticated user via the JWT,
    // so no userId is sent in the query.
    expect(mockedApi.get).toHaveBeenCalledWith('/api/conversations');
  });
});

describe('fetchConversationById', () => {
  test('le store charge la conversation courante après fulfilled', async () => {
    mockedApi.get.mockResolvedValue({ data: { id: 'c-1', messages: [] } });
    const store = buildStore();

    await store.dispatch(fetchConversationById('c-1'));

    const state = store.getState().conversation;
    expect(state.currentStatus).toBe('succeeded');
    expect(state.current?.id).toBe('c-1');
  });
});

describe('sendMessage', () => {
  test('le store ajoute le message à la conversation courante après fulfilled', async () => {
    mockedApi.get.mockResolvedValue({ data: { id: 'c-1', messages: [] } });
    const store = buildStore();
    await store.dispatch(fetchConversationById('c-1'));

    mockedApi.post.mockResolvedValue({ data: { id: 'm-1', body: 'Bonjour' } });
    await store.dispatch(
      sendMessage({ conversationId: 'c-1', body: 'Bonjour' })
    );

    const state = store.getState().conversation;
    expect(state.sendStatus).toBe('succeeded');
    expect(state.current?.messages).toHaveLength(1);
    expect(state.current?.messages[0].id).toBe('m-1');
    // The author is derived from the JWT, so only the body is sent.
    expect(mockedApi.post).toHaveBeenCalledWith(
      '/api/conversations/c-1/messages',
      { body: 'Bonjour' },
      expect.anything()
    );
  });

  test('le store passe sendStatus à failed après rejected', async () => {
    mockedApi.post.mockRejectedValue({ response: { data: { detail: 'too long' } } });
    const store = buildStore();

    await store.dispatch(
      sendMessage({ conversationId: 'c-1', body: '' })
    );

    const state = store.getState().conversation;
    expect(state.sendStatus).toBe('failed');
    expect(state.sendError).toBe('too long');
  });
});

describe('clearCurrent / clearSendError', () => {
  test('clearCurrent réinitialise la conversation courante', async () => {
    mockedApi.get.mockResolvedValue({ data: { id: 'c-1', messages: [] } });
    const store = buildStore();
    await store.dispatch(fetchConversationById('c-1'));

    store.dispatch(clearCurrent());

    const state = store.getState().conversation;
    expect(state.current).toBeNull();
    expect(state.currentStatus).toBe('idle');
  });

  test('clearSendError réinitialise l\'état d\'envoi', async () => {
    mockedApi.post.mockRejectedValue({ response: { data: { detail: 'too long' } } });
    const store = buildStore();
    await store.dispatch(sendMessage({ conversationId: 'c-1', body: '' }));

    store.dispatch(clearSendError());

    const state = store.getState().conversation;
    expect(state.sendStatus).toBe('idle');
    expect(state.sendError).toBeNull();
  });
});

describe('unread count', () => {
  const conversation = {
    id: 'c-1',
    reservationId: 'r-1',
    accommodationId: 'a-1',
    teamId: 't-1',
    guestUserId: 'me',
    createdAt: '2026-01-01T09:00:00Z',
    messages: [
      { id: 'm1', body: 'Réservation demandée', authorUserId: null, sentAt: '2026-01-01T10:00:00Z', isSystem: true },
      { id: 'm2', body: 'Bonjour', authorUserId: 'host', sentAt: '2026-01-02T10:00:00Z', isSystem: false },
      { id: 'm3', body: 'Merci', authorUserId: 'me', sentAt: '2026-01-03T10:00:00Z', isSystem: false },
    ],
  };

  const stateWith = (reads: Record<string, string>) =>
    ({
      auth: { user: { id: 'me' } },
      conversation: { items: [conversation], reads },
    }) as unknown as Parameters<typeof selectUnreadCount>[0];

  test('compte les messages entrants non lus, hors messages système et messages de l\'utilisateur', () => {
    // Only m2 counts: m1 is a system message, m3 was sent by the user.
    expect(selectUnreadCount(stateWith({}))).toBe(1);
  });

  test('ne compte plus un message une fois la conversation lue', () => {
    expect(selectUnreadCount(stateWith({ 'c-1': '2026-01-02T10:00:00Z' }))).toBe(0);
  });

  test('fetchConversationById marque la conversation lue jusqu\'au dernier message', async () => {
    mockedApi.get.mockResolvedValue({ data: conversation });
    const store = buildStore();

    await store.dispatch(fetchConversationById('c-1'));

    expect(store.getState().conversation.reads['c-1']).toBe('2026-01-03T10:00:00Z');
  });

  test('markConversationRead enregistre l\'horodatage de lecture', () => {
    const store = buildStore();

    store.dispatch(markConversationRead({ conversationId: 'c-1', at: '2026-02-01T00:00:00Z' }));

    expect(store.getState().conversation.reads['c-1']).toBe('2026-02-01T00:00:00Z');
  });
});