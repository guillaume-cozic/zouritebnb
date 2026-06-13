import type { RootState } from '../../store/store';

export const selectDashboard = (state: RootState) => state.dashboard.data;
export const selectDashboardStatus = (state: RootState) => state.dashboard.status;
export const selectDashboardError = (state: RootState) => state.dashboard.error;
