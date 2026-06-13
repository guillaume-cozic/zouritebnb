import type { RootState } from '../../store/store';

export const selectReservations = (state: RootState) => state.reservations.items;
export const selectReservationsStatus = (state: RootState) => state.reservations.status;
export const selectReservationsError = (state: RootState) => state.reservations.error;
export const selectReservationsCount = (state: RootState) => state.reservations.totalItems;
export const selectReservationsPage = (state: RootState) => state.reservations.page;
export const selectReservationsPerPage = (state: RootState) => state.reservations.itemsPerPage;
