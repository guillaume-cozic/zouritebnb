import { startAppListening } from '../../store/listenerMiddleware';
import { reservationModalOpened } from './ReservationSlice';
import { fetchAllAccommodations } from '../accommodationManagement/AccommodationManagementSlice';
import { selectManagedAccommodations } from '../accommodationManagement/AccommodationManagementSelectors';

startAppListening({
  actionCreator: reservationModalOpened,
  effect: (action, api) => {
    const { accommodationId } = action.payload;
    if (accommodationId) return;
    const accommodations = selectManagedAccommodations(api.getState());
    if (accommodations.length === 0) {
      api.dispatch(fetchAllAccommodations('all'));
    }
  },
});
