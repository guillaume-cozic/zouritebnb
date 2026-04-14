import { RootState } from '../../store';

export const selectCurrentTeam = (state: RootState) => state.team?.current ?? null;
export const selectTeamStatus = (state: RootState) => state.team?.status ?? 'idle';
export const selectTeamError = (state: RootState) => state.team?.error ?? null;
