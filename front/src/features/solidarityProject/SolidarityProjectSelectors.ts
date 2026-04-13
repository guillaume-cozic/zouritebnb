import { RootState } from '../../store';

export const selectSolidarityProjects = (state: RootState) =>
  state.solidarityProject?.items ?? [];

export const selectSolidarityProjectsStatus = (state: RootState) =>
  state.solidarityProject?.status ?? 'idle';

export const selectSolidarityProjectsError = (state: RootState) =>
  state.solidarityProject?.error ?? null;

export const selectCurrentSolidarityProject = (state: RootState) =>
  state.solidarityProject?.current ?? null;

export const selectCurrentSolidarityProjectStatus = (state: RootState) =>
  state.solidarityProject?.currentStatus ?? 'idle';

export const selectCurrentSolidarityProjectError = (state: RootState) =>
  state.solidarityProject?.currentError ?? null;
