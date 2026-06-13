import type { RootState } from '../../store/store';

export const selectSolidarityProjects = (state: RootState) => state.solidarityProjects.items;
export const selectSolidarityProjectsStatus = (state: RootState) => state.solidarityProjects.status;
export const selectSolidarityProjectsError = (state: RootState) => state.solidarityProjects.error;
export const selectSolidarityProjectsCount = (state: RootState) => state.solidarityProjects.totalItems;
export const selectSolidarityProjectsPage = (state: RootState) => state.solidarityProjects.page;
export const selectSolidarityProjectsPerPage = (state: RootState) =>
  state.solidarityProjects.itemsPerPage;

export const selectSolidarityProjectCurrent = (state: RootState) => state.solidarityProjects.current;
export const selectSolidarityProjectCurrentStatus = (state: RootState) =>
  state.solidarityProjects.currentStatus;
export const selectSolidarityProjectCurrentError = (state: RootState) =>
  state.solidarityProjects.currentError;

export const selectSolidarityProjectSaveState = (state: RootState) =>
  state.solidarityProjects.saveState;
export const selectSolidarityProjectSaveError = (state: RootState) =>
  state.solidarityProjects.saveError;
