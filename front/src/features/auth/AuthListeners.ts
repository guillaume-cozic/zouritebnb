import { startAppListening } from '../../store/listenerMiddleware';
import {
  profileEdited,
  profileSaveStateCleared,
  updateUserProfile,
} from './AuthSlice';

const PROFILE_AUTOSAVE_DELAY = 800;
const SAVED_BADGE_DELAY = 1500;

startAppListening({
  actionCreator: profileEdited,
  effect: async (action, api) => {
    api.cancelActiveListeners();
    await api.delay(PROFILE_AUTOSAVE_DELAY);
    const { userId, firstName, lastName, email } = action.payload;
    if (!email) return;
    const result = await api.dispatch(updateUserProfile({
      id: userId,
      firstName: firstName || null,
      lastName: lastName || null,
      email,
    }));
    if (updateUserProfile.fulfilled.match(result)) {
      await api.delay(SAVED_BADGE_DELAY);
      api.dispatch(profileSaveStateCleared());
    }
  },
});
