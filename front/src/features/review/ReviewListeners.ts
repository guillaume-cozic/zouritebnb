import { startAppListening } from '../../store/listenerMiddleware';
import { reviewSubmitted, submitAccommodationReview, submitGuestReview } from './ReviewSlice';

/**
 * One business intent (`reviewSubmitted`) → one effect: dispatch the matching thunk.
 * The form component stays declarative and never knows which endpoint is hit.
 */
startAppListening({
  actionCreator: reviewSubmitted,
  effect: (action, api) => {
    const event = action.payload;
    if (event.target === 'accommodation') {
      api.dispatch(
        submitAccommodationReview({
          reservationId: event.reservationId,
          payload: event.payload,
        })
      );
    } else {
      api.dispatch(
        submitGuestReview({
          reservationId: event.reservationId,
          payload: event.payload,
        })
      );
    }
  },
});
