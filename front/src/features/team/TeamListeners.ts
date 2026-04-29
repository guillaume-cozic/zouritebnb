import { startAppListening } from '../../store/listenerMiddleware';
import {
  teamSettingsPageOpened,
  fetchTeam,
  fetchTeamInvitations,
  inviteCoHost,
  clearInviteStatus,
} from './TeamSlice';
import { fetchSolidarityProjects } from '../solidarityProject/SolidarityProjectSlice';

startAppListening({
  actionCreator: teamSettingsPageOpened,
  effect: (action, api) => {
    const { teamId } = action.payload;
    if (teamId) {
      api.dispatch(fetchTeam(teamId));
      api.dispatch(fetchTeamInvitations(teamId));
    }
    api.dispatch(fetchSolidarityProjects());
  },
});

startAppListening({
  actionCreator: inviteCoHost.fulfilled,
  effect: async (_action, api) => {
    api.cancelActiveListeners();
    await api.delay(2000);
    api.dispatch(clearInviteStatus());
  },
});
