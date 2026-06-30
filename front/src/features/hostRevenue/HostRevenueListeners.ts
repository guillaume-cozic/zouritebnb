import { startAppListening } from '../../store/listenerMiddleware';
import { revenuePageOpened, fetchHostRevenue } from './HostRevenueSlice';

startAppListening({
  actionCreator: revenuePageOpened,
  effect: (_action, api) => {
    api.dispatch(fetchHostRevenue());
  },
});
