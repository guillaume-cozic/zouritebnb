import { RootState } from '../../store';

export const selectPaymentStatus = (state: RootState) => state.payment.status;
export const selectPaymentError = (state: RootState) => state.payment.error;
