import { RootState } from '../../store';

export const selectCurrentTeam = (state: RootState) => state.team?.current ?? null;
export const selectTeamStatus = (state: RootState) => state.team?.status ?? 'idle';
export const selectTeamError = (state: RootState) => state.team?.error ?? null;
export const selectTeamInvitations = (state: RootState) => state.team?.invitations ?? [];
export const selectInviteStatus = (state: RootState) => state.team?.inviteStatus ?? 'idle';
export const selectInviteError = (state: RootState) => state.team?.inviteError ?? null;
