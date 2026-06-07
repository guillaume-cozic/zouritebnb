import { RootState } from '../../store';

export const selectAccommodations = (state: RootState) =>
  state.homepage?.accommodations ?? [];

export const selectHomepageFilters = (state: RootState) =>
  state.homepage?.filters ?? { city: '', checkIn: '', checkOut: '', guests: 1, amenities: [], priceMin: null, priceMax: null };

export const selectHomepageStatus = (state: RootState) =>
  state.homepage?.status ?? 'idle';

export const selectHomepageError = (state: RootState) =>
  state.homepage?.error ?? null;

export const selectHomepageLoadingMore = (state: RootState) =>
  state.homepage?.loadingMore ?? false;

export const selectHomepageTotalItems = (state: RootState) =>
  state.homepage?.totalItems ?? 0;

export const selectHomepageHasMore = (state: RootState) =>
  (state.homepage?.accommodations.length ?? 0) < (state.homepage?.totalItems ?? 0);

// Filters are now applied server-side by the API; the selector is a pass-through
// kept as a named export so callers don't need to switch imports.
export const selectFilteredAccommodations = selectAccommodations;
