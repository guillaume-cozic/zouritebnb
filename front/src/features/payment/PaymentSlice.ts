import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import api from '../../services/api';
import { extractErrorMessage } from '../../services/errors';
import { CreatePaymentIntentPayload, PaymentIntentResponse } from './PaymentTypes';

type Status = 'idle' | 'loading' | 'succeeded' | 'failed';

interface PaymentState {
  status: Status;
  error: string | null;
}

const initialState: PaymentState = {
  status: 'idle',
  error: null,
};

export const createPaymentIntent = createAsyncThunk<
  PaymentIntentResponse,
  CreatePaymentIntentPayload,
  { rejectValue: string }
>('payment/createIntent', async (payload, { rejectWithValue }) => {
  try {
    const response = await api.post('/api/payment-intents', payload, {
      headers: { 'Content-Type': 'application/ld+json' },
    });
    return {
      paymentIntentId: response.data.paymentIntentId,
      clientSecret: response.data.clientSecret,
    };
  } catch (err) {
    return rejectWithValue(
      extractErrorMessage(err, 'Erreur lors de la création du paiement')
    );
  }
});

const paymentSlice = createSlice({
  name: 'payment',
  initialState,
  reducers: {
    resetPaymentStatus(state) {
      state.status = 'idle';
      state.error = null;
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(createPaymentIntent.pending, (state) => {
        state.status = 'loading';
        state.error = null;
      })
      .addCase(createPaymentIntent.fulfilled, (state) => {
        state.status = 'succeeded';
      })
      .addCase(createPaymentIntent.rejected, (state, action) => {
        state.status = 'failed';
        state.error = (action.payload as string) || action.error.message || null;
      });
  },
});

export const { resetPaymentStatus } = paymentSlice.actions;
export default paymentSlice.reducer;
