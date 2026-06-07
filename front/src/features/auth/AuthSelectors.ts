import { RootState } from '../../store';

export const selectAuthUser = (state: RootState) => state.auth?.user ?? null;
export const selectAuthStatus = (state: RootState) => state.auth?.status ?? 'idle';
export const selectAuthError = (state: RootState) => state.auth?.error ?? null;
export const selectAuthTeamId = (state: RootState) => state.auth?.user?.teamId ?? null;
export const selectAuthToken = (state: RootState) => state.auth?.user?.token ?? null;
export const selectIsAuthenticated = (state: RootState) => !!state.auth?.user;
