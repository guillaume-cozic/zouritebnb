import type { RootState } from '../../store/store';

export const selectActivityPoints = (state: RootState) => state.activityPoints.items;
export const selectActivityPointsStatus = (state: RootState) => state.activityPoints.status;
export const selectActivityPointsError = (state: RootState) => state.activityPoints.error;
export const selectActivityPointsCount = (state: RootState) => state.activityPoints.totalItems;
export const selectActivityPointsPage = (state: RootState) => state.activityPoints.page;
export const selectActivityPointsPerPage = (state: RootState) =>
  state.activityPoints.itemsPerPage;

export const selectActivityPointsMapItems = (state: RootState) =>
  state.activityPoints.mapItems;
export const selectActivityPointsMapStatus = (state: RootState) =>
  state.activityPoints.mapStatus;
export const selectActivityPointsMapError = (state: RootState) =>
  state.activityPoints.mapError;

export const selectActivityPointCurrent = (state: RootState) => state.activityPoints.current;
export const selectActivityPointCurrentStatus = (state: RootState) =>
  state.activityPoints.currentStatus;
export const selectActivityPointCurrentError = (state: RootState) =>
  state.activityPoints.currentError;

export const selectActivityPointSaveState = (state: RootState) =>
  state.activityPoints.saveState;
export const selectActivityPointSaveError = (state: RootState) =>
  state.activityPoints.saveError;
