import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import api from '../../services/api';
import { extractErrorMessage } from '../../services/errors';
import { SendContactMessagePayload } from './ContactTypes';

type Status = 'idle' | 'loading' | 'succeeded' | 'failed';

interface ContactState {
  status: Status;
  error: string | null;
}

const initialState: ContactState = {
  status: 'idle',
  error: null,
};

export const sendContactMessage = createAsyncThunk<
  void,
  SendContactMessagePayload,
  { rejectValue: string }
>('contact/sendMessage', async (payload, { rejectWithValue }) => {
  try {
    await api.post('/api/contact_messages', payload, {
      headers: { 'Content-Type': 'application/ld+json' },
    });
  } catch (err) {
    return rejectWithValue(
      extractErrorMessage(err, "Erreur lors de l'envoi du message")
    );
  }
});

const contactSlice = createSlice({
  name: 'contact',
  initialState,
  reducers: {
    contactPageLeft(state) {
      state.status = 'idle';
      state.error = null;
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(sendContactMessage.pending, (state) => {
        state.status = 'loading';
        state.error = null;
      })
      .addCase(sendContactMessage.fulfilled, (state) => {
        state.status = 'succeeded';
      })
      .addCase(sendContactMessage.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload || action.error.message || null;
      });
  },
});

export const { contactPageLeft } = contactSlice.actions;
export default contactSlice.reducer;
