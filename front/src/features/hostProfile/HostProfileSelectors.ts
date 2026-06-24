import { RootState } from '../../store';

export const selectHostProfileByTeamId =
  (teamId: string | null | undefined) => (state: RootState) =>
    teamId ? state.hostProfile.byTeamId[teamId] : undefined;
