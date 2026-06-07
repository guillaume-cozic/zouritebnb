jest.mock('../../services/api', () => ({
  __esModule: true,
  default: { get: jest.fn(), post: jest.fn(), put: jest.fn(), patch: jest.fn(), delete: jest.fn() },
}));

import { configureStore } from '@reduxjs/toolkit';
import conversationReducer, {
  clearCurrent,
  clearSendError,
  fetchConversationsForUser,
  fetchConversationById,
  sendMessage,
} from './ConversationSlice';
import api from '../../services/api';

const mockedApi = api as jest.Mocked<typeof api>;

const buildStore = () => configureStore({ reducer: { conversation: conversationReducer } });

beforeEach(() => {
  jest.clearAllMocks();
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