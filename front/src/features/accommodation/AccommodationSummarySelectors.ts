import { RootState } from '../../store';

export const selectAccommodationSummaries = (state: RootState) =>
  state.accommodationSummary.byId;

export const selectAccommodationSummaryById =
  (id: string) => (state: RootState) =>
    state.accommodationSummary.byId[id];
