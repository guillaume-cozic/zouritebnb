import { startAppListening } from '../../store/listenerMiddleware';
import {
  teamSettingsPageOpened,
  bankAccountEdited,
  bankSaveStateCleared,
  favoriteSaveStateCleared,
  fetchTeam,
  fetchTeamInvitations,
  inviteCoHost,
  updateTeamBankAccount,
  updateTeamFavoriteProject,
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

const BANK_AUTOSAVE_DELAY = 800;
const SAVED_BADGE_DELAY = 1500;

startAppListening({
  actionCreator: bankAccountEdited,
  effect: async (action, api) => {
    api.cancelActiveListeners();
    await api.delay(BANK_AUTOSAVE_DELAY);
    const { teamId, iban, bic, holderName } = action.payload;
    const ibanTrimmed = iban.trim();
    const holderTrimmed = holderName.trim();
    // An IBAN without a holder name is incomplete: wait for the holder.
    if (ibanTrimmed !== '' && holderTrimmed === '') return;
    const result = await api.dispatch(updateTeamBankAccount({
      id: teamId,
      payload: {
        iban: ibanTrimmed === '' ? null : ibanTrimmed,
        bic: bic.trim() === '' ? null : bic.trim(),
        holderName: holderTrimmed === '' ? null : holderTrimmed,
      },
    }));
    if (updateTeamBankAccount.fulfilled.match(result)) {
      await api.delay(SAVED_BADGE_DELAY);
      api.dispatch(bankSaveStateCleared());
    }
  },
});

startAppListening({
  actionCreator: updateTeamFavoriteProject.fulfilled,
  effect: async (_action, api) => {
    api.cancelActiveListeners();
    await api.delay(SAVED_BADGE_DELAY);
    api.dispatch(favoriteSaveStateCleared());
  },
});
