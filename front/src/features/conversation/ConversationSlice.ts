import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import api from '../../services/api';
import { extractErrorMessage } from '../../services/errors';
import {
  Conversation,
  ConversationMessage,
  SendAttachmentPayload,
  SendMessagePayload,
} from './ConversationTypes';
import {
  ConversationReads,
  loadConversationReads,
  saveConversationReads,
} from './conversationReads';

type Status = 'idle' | 'loading' | 'succeeded' | 'failed';

interface ConversationState {
  items: Conversation[];
  listStatus: Status;
  listError: string | null;
  current: Conversation | null;
  currentStatus: Status;
  currentError: string | null;
  sendStatus: Status;
  sendError: string | null;
  reads: ConversationReads;
}

const latestMessageAt = (conversation: Conversation): string | null => {
  let latest: string | null = null;
  for (const message of conversation.messages) {
    if (!latest || new Date(message.sentAt).getTime() > new Date(latest).getTime()) {
      latest = message.sentAt;
    }
  }
  return latest;
};

const initialState: ConversationState = {
  items: [],
  listStatus: 'idle',
  listError: null,
  current: null,
  currentStatus: 'idle',
  currentError: null,
  sendStatus: 'idle',
  sendError: null,
  reads: loadConversationReads(),
};

export const fetchConversationsForUser = createAsyncThunk(
  'conversation/fetchForUser',
  async (_: void, { rejectWithValue }) => {
    try {
      // The backend scopes the collection to the authenticated user (JWT).
      const response = await api.get('/api/conversations');
      const data = response.data;
      return (data['hydra:member'] ?? data['member'] ?? []) as Conversation[];
    } catch (err) {
      return rejectWithValue(
        extractErrorMessage(err, 'Erreur lors du chargement des conversations')
      );
    }
  }
);

export const fetchConversationsForTeam = createAsyncThunk(
  'conversation/fetchForTeam',
  async (_: void, { rejectWithValue }) => {
    try {
      // The backend scopes the collection to the authenticated user's team (JWT).
      const response = await api.get('/api/conversations');
      const data = response.data;
      return (data['hydra:member'] ?? data['member'] ?? []) as Conversation[];
    } catch (err) {
      return rejectWithValue(
        extractErrorMessage(err, 'Erreur lors du chargement des conversations')
      );
    }
  }
);

export const fetchConversationById = createAsyncThunk(
  'conversation/fetchById',
  async (id: string, { rejectWithValue }) => {
    try {
      const response = await api.get(`/api/conversations/${id}`);
      return response.data as Conversation;
    } catch (err) {
      return rejectWithValue(
        extractErrorMessage(err, 'Conversation introuvable')
      );
    }
  }
);

export const sendMessage = createAsyncThunk(
  'conversation/sendMessage',
  async (payload: SendMessagePayload, { rejectWithValue }) => {
    try {
      const response = await api.post(
        `/api/conversations/${payload.conversationId}/messages`,
        { body: payload.body },
        { headers: { 'Content-Type': 'application/ld+json' } }
      );
      return {
        conversationId: payload.conversationId,
        message: response.data as ConversationMessage,
      };
    } catch (err) {
      return rejectWithValue(
        extractErrorMessage(err, "Impossible d'envoyer le message")
      );
    }
  }
);

export const sendAttachment = createAsyncThunk(
  'conversation/sendAttachment',
  async (payload: SendAttachmentPayload, { rejectWithValue }) => {
    try {
      const formData = new FormData();
      formData.append('file', payload.file);
      if (payload.body) {
        formData.append('body', payload.body);
      }
      const response = await api.post(
        `/api/conversations/${payload.conversationId}/attachments`,
        formData
      );
      return {
        conversationId: payload.conversationId,
        message: response.data as ConversationMessage,
      };
    } catch (err) {
      return rejectWithValue(
        extractErrorMessage(err, "Impossible d'envoyer la photo")
      );
    }
  }
);

const conversationSlice = createSlice({
  name: 'conversation',
  initialState,
  reducers: {
    clearCurrent(state) {
      state.current = null;
      state.currentStatus = 'idle';
      state.currentError = null;
    },
    clearSendError(state) {
      state.sendError = null;
      state.sendStatus = 'idle';
    },
    // Marks a conversation read up to the given timestamp (defaults handled by callers).
    markConversationRead(state, action: { payload: { conversationId: string; at: string } }) {
      state.reads[action.payload.conversationId] = action.payload.at;
      saveConversationReads(state.reads);
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(fetchConversationsForUser.pending, (state) => {
        state.listStatus = 'loading';
        state.listError = null;
      })
      .addCase(fetchConversationsForUser.fulfilled, (state, action) => {
        state.listStatus = 'succeeded';
        state.items = action.payload;
      })
      .addCase(fetchConversationsForUser.rejected, (state, action) => {
        state.listStatus = 'failed';
        state.listError = (action.payload as string) || null;
      })
      .addCase(fetchConversationsForTeam.pending, (state) => {
        state.listStatus = 'loading';
        state.listError = null;
      })
      .addCase(fetchConversationsForTeam.fulfilled, (state, action) => {
        state.listStatus = 'succeeded';
        state.items = action.payload;
      })
      .addCase(fetchConversationsForTeam.rejected, (state, action) => {
        state.listStatus = 'failed';
        state.listError = (action.payload as string) || null;
      })
      .addCase(fetchConversationById.pending, (state) => {
        state.currentStatus = 'loading';
        state.currentError = null;
        state.current = null;
      })
      .addCase(fetchConversationById.fulfilled, (state, action) => {
        state.currentStatus = 'succeeded';
        state.current = action.payload;
        // Opening a conversation marks every message it currently holds as read.
        const at = latestMessageAt(action.payload);
        if (at) {
          state.reads[action.payload.id] = at;
          saveConversationReads(state.reads);
        }
      })
      .addCase(fetchConversationById.rejected, (state, action) => {
        state.currentStatus = 'failed';
        state.currentError = (action.payload as string) || null;
      })
      .addCase(sendMessage.pending, (state) => {
        state.sendStatus = 'loading';
        state.sendError = null;
      })
      .addCase(sendMessage.fulfilled, (state, action) => {
        state.sendStatus = 'succeeded';
        const { conversationId, message } = action.payload;
        if (state.current && state.current.id === conversationId) {
          state.current.messages.push(message);
        }
      })
      .addCase(sendMessage.rejected, (state, action) => {
        state.sendStatus = 'failed';
        state.sendError = (action.payload as string) || null;
      })
      .addCase(sendAttachment.pending, (state) => {
        state.sendStatus = 'loading';
        state.sendError = null;
      })
      .addCase(sendAttachment.fulfilled, (state, action) => {
        state.sendStatus = 'succeeded';
        const { conversationId, message } = action.payload;
        if (state.current && state.current.id === conversationId) {
          state.current.messages.push(message);
        }
      })
      .addCase(sendAttachment.rejected, (state, action) => {
        state.sendStatus = 'failed';
        state.sendError = (action.payload as string) || null;
      });
  },
});

export const { clearCurrent, clearSendError, markConversationRead } = conversationSlice.actions;
export default conversationSlice.reducer;
