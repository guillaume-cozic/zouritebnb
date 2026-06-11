import { createSelector } from '@reduxjs/toolkit';
import type { RootState } from '../../store/store';

export const selectReservations = (state: RootState) => state.reservations.items;
export const selectReservationsStatus = (state: RootState) => state.reservations.status;
export const selectReservationsError = (state: RootState) => state.reservations.error;
export const selectReservationsCount = (state: RootState) => state.reservations.items.length;

/** Distinct statuses present in the data, known ones first in a stable order. */
export const selectReservationStatuses = createSelector(selectReservations, (items) => {
  const known = ['pending', 'confirmed', 'cancelled', 'refused'];
  const present = new Set(items.map((r) => r.status));
  return [
    ...known.filter((s) => present.has(s)),
    ...[...present].filter((s) => !known.includes(s)).sort(),
  ];
});
