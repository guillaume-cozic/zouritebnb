import { RootState } from '../../store';

export const selectManagedAccommodations = (state: RootState) => state.accommodationManagement.items;
export const selectManagementStatus = (state: RootState) => state.accommodationManagement.status;
export const selectManagementError = (state: RootState) => state.accommodationManagement.error;
export const selectManagementStatusFilter = (state: RootState) => state.accommodationManagement.statusFilter;
