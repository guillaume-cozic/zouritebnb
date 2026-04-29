import { startAppListening } from '../../store/listenerMiddleware';
import { addressSubmitted, setLocation } from './AccommodationSlice';

startAppListening({
  actionCreator: addressSubmitted,
  effect: (action, api) => {
    const { id, address } = action.payload;
    api.dispatch(setLocation({ id, ...address }));
  },
});
