import { createSelector } from '@reduxjs/toolkit';
import { RootState } from '../../store';

export const selectReservations = (state: RootState) => state.reservation.items;
export const selectReservationsStatus = (state: RootState) => state.reservation.status;
export const selectReservationsError = (state: RootState) => state.reservation.error;
export const selectReservationMutationStatus = (state: RootState) =>
  state.reservation.mutationStatus;
export const selectReservationMutationError = (state: RootState) =>
  state.reservation.mutationError;

export const selectReservationsForAccommodation = (accommodationId: string) =>
  createSelector(selectReservations, (items) =>
    items.filter((r) => r.accommodationId === accommodationId)
  );
