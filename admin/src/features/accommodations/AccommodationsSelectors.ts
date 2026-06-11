import { createSelector } from '@reduxjs/toolkit';
import type { RootState } from '../../store/store';

export const selectAccommodations = (state: RootState) => state.accommodations.items;
export const selectAccommodationsStatus = (state: RootState) => state.accommodations.status;
export const selectAccommodationsError = (state: RootState) => state.accommodations.error;
export const selectAccommodationsCount = (state: RootState) => state.accommodations.items.length;

/** Distinct statuses present in the data, known ones first in a stable order. */
export const selectAccommodationStatuses = createSelector(selectAccommodations, (items) => {
  const known = ['published', 'draft'];
  const present = new Set(items.map((a) => a.status));
  return [
    ...known.filter((s) => present.has(s)),
    ...[...present].filter((s) => !known.includes(s)).sort(),
  ];
});
