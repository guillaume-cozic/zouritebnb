import { RootState } from '../../store';

export const selectLocalities = (state: RootState) =>
  state.geography?.localities ?? [];

export const selectLocalitiesStatus = (state: RootState) =>
  state.geography?.status ?? 'idle';

export const selectRegions = (state: RootState) =>
  state.geography?.regions ?? [];

export const selectRegionsStatus = (state: RootState) =>
  state.geography?.status ?? 'idle';
