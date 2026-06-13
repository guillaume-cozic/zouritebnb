import type { RootState } from '../../store/store';

export const selectAccommodations = (state: RootState) => state.accommodations.items;
export const selectAccommodationsStatus = (state: RootState) => state.accommodations.status;
export const selectAccommodationsError = (state: RootState) => state.accommodations.error;
export const selectAccommodationsCount = (state: RootState) => state.accommodations.totalItems;
export const selectAccommodationsPage = (state: RootState) => state.accommodations.page;
export const selectAccommodationsPerPage = (state: RootState) => state.accommodations.itemsPerPage;
