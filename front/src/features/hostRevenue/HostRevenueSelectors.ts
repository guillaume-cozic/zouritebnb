import { RootState } from '../../store';

export const selectHostRevenue = (state: RootState) => state.hostRevenue.data;
export const selectHostRevenueStatus = (state: RootState) => state.hostRevenue.status;
export const selectHostRevenueError = (state: RootState) => state.hostRevenue.error;
