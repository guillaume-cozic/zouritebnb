import type { RootState } from '../../store/store';

export const selectUsers = (state: RootState) => state.users.items;
export const selectUsersStatus = (state: RootState) => state.users.status;
export const selectUsersError = (state: RootState) => state.users.error;
export const selectUsersCount = (state: RootState) => state.users.totalItems;
export const selectUsersPage = (state: RootState) => state.users.page;
export const selectUsersPerPage = (state: RootState) => state.users.itemsPerPage;
