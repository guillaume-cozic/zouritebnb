import type { RootState } from '../../store/store';

export const selectUsers = (state: RootState) => state.users.items;
export const selectUsersStatus = (state: RootState) => state.users.status;
export const selectUsersError = (state: RootState) => state.users.error;
export const selectUsersCount = (state: RootState) => state.users.items.length;
