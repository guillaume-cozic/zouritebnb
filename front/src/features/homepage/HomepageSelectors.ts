import { createSelector } from '@reduxjs/toolkit';
import { RootState } from '../../store';

export const selectAccommodations = (state: RootState) =>
  state.homepage?.accommodations ?? [];

export const selectHomepageFilters = (state: RootState) =>
  state.homepage?.filters ?? { city: '', checkIn: '', checkOut: '', guests: null, amenities: [], priceMin: null, priceMax: null };

export const selectHomepageStatus = (state: RootState) =>
  state.homepage?.status ?? 'idle';

export const selectHomepageError = (state: RootState) =>
  state.homepage?.error ?? null;

export const selectFilteredAccommodations = createSelector(
  [selectAccommodations, selectHomepageFilters],
  (accommodations, filters) => {
    return accommodations.filter((item) => {
      if (filters.city && !item.city?.toLowerCase().includes(filters.city.toLowerCase())) {
        return false;
      }
      if (filters.guests !== null && item.maxGuests !== null && item.maxGuests < filters.guests) {
        return false;
      }
      if (filters.priceMin !== null && item.price !== null && item.price < filters.priceMin) {
        return false;
      }
      if (filters.priceMax !== null && item.price !== null && item.price > filters.priceMax) {
        return false;
      }
      if (filters.amenities.length > 0) {
        const itemAmenities = item.amenities ?? [];
        if (!filters.amenities.every((code) => itemAmenities.includes(code))) {
          return false;
        }
      }
      return true;
    });
  }
);
