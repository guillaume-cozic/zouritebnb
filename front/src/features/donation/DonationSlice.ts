import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import api from '../../services/api';
import { extractErrorMessage } from '../../services/errors';
import { CreateDonationIntentPayload, DonationIntentResponse } from './DonationTypes';

type Status = 'idle' | 'loading' | 'succeeded' | 'failed';

interface DonationState {
  status: Status;
  error: string | null;
}

const initialState: DonationState = {
  status: 'idle',
  error: null,
};

export const createDonationIntent = createAsyncThunk<
  DonationIntentResponse,
  CreateDonationIntentPayload,
  { rejectValue: string }
>('donation/createIntent', async (payload, { rejectWithValue }) => {
  try {
    const response = await api.post('/api/donation-intents', payload, {
      headers: { 'Content-Type': 'application/ld+json' },
    });
    return {
      paymentIntentId: response.data.paymentIntentId,
      clientSecret: response.data.clientSecret,
    };
  } catch (err) {
    return rejectWithValue(
      extractErrorMessage(err, 'Erreur lors de la création du don')
    );
  }
});

const donationSlice = createSlice({
  name: 'donation',
  initialState,
  reducers: {
    resetDonationStatus(state) {
      state.status = 'idle';
      state.error = null;
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(createDonationIntent.pending, (state) => {
        state.status = 'loading';
        state.error = null;
      })
      .addCase(createDonationIntent.fulfilled, (state) => {
        state.status = 'succeeded';
      })
      .addCase(createDonationIntent.rejected, (state, action) => {
        state.status = 'failed';
        state.error = (action.payload as string) || action.error.message || null;
      });
  },
});

export const { resetDonationStatus } = donationSlice.actions;
export default donationSlice.reducer;
