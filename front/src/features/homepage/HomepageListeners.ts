import { startAppListening } from '../../store/listenerMiddleware';
import { nextPageRequested, fetchPublishedAccommodations } from './HomepageSlice';

startAppListening({
  actionCreator: nextPageRequested,
  effect: (_action, api) => {
    const { homepage } = api.getState();
    const { status, loadingMore, accommodations, totalItems, page, filters } = homepage;

    // Don't stack requests, and don't fetch past the last page.
    if (status === 'loading' || loadingMore) return;
    if (accommodations.length >= totalItems) return;

    api.dispatch(
      fetchPublishedAccommodations({
        checkIn: filters.checkIn,
        checkOut: filters.checkOut,
        city: filters.city,
        guests: filters.guests,
        priceMin: filters.priceMin,
        priceMax: filters.priceMax,
        amenities: filters.amenities,
        sort: filters.sort,
        page: page + 1,
      })
    );
  },
});
