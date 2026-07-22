import type { RootState } from '../../store';

export const selectActivityPoints = (state: RootState) => state.activityPoint.items;
export const selectActivityPointsStatus = (state: RootState) => state.activityPoint.status;
export const selectActivityPointsError = (state: RootState) => state.activityPoint.error;
