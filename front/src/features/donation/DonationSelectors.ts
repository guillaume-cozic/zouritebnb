import { RootState } from '../../store';

export const selectDonationStatus = (state: RootState) => state.donation.status;
export const selectDonationError = (state: RootState) => state.donation.error;
