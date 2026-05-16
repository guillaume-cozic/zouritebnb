import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import api from '../../services/api';
import { Conversation, ConversationMessage, SendMessagePayload } from './ConversationTypes';

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
}

const initialState: ConversationState = {
  items: [],
  listStatus: 'idle',
  listError: null,
  current: null,
  currentStatus: 'idle',
  currentError: null,
  sendStatus: 'idle',
  sendError: null,
};

export const fetchConversationsForUser = createAsyncThunk(
  'conversation/fetchForUser',
  async (userId: string, { rejectWithValue }) => {
    try {
      const response = await api.get('/api/conversations', { params: { userId } });
      const data = response.data;
      return (data['hydra:member'] ?? data['member'] ?? []) as Conversation[];
    } catch (err: any) {
      return rejectWithValue(
        err.response?.data?.detail || 'Erreur lors du chargement des conversations'
      );
    }
  }
);

export const fetchConversationsForTeam = createAsyncThunk(
  'conversation/fetchForTeam',
  async (teamId: string, { rejectWithValue }) => {
    try {
      const response = await api.get('/api/conversations', { params: { teamId } });
      const data = response.data;
      return (data['hydra:member'] ?? data['member'] ?? []) as Conversation[];
    } catch (err: any) {
      return rejectWithValue(
        err.response?.data?.detail || 'Erreur lors du chargement des conversations'
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
    } catch (err: any) {
      return rejectWithValue(
        err.response?.data?.detail || 'Conversation introuvable'
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
        { authorUserId: payload.authorUserId, body: payload.body },
        { headers: { 'Content-Type': 'application/ld+json' } }
      );
      return {
        conversationId: payload.conversationId,
        message: response.data as ConversationMessage,
      };
    } catch (err: any) {
      return rejectWithValue(
        err.response?.data?.detail || "Impossible d'envoyer le message"
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
      });
  },
});

export const { clearCurrent, clearSendError } = conversationSlice.actions;
export default conversationSlice.reducer;
