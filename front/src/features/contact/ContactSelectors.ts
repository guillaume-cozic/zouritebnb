import { RootState } from '../../store';

export const selectContactStatus = (state: RootState) => state.contact.status;
export const selectContactError = (state: RootState) => state.contact.error;
