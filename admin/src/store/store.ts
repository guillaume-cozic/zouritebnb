import { configureStore } from '@reduxjs/toolkit';
import { setUnauthorizedHandler } from '../services/api';
import authReducer, { loggedOut } from '../features/auth/AuthSlice';
import reservationsReducer from '../features/reservations/ReservationsSlice';
import accommodationsReducer from '../features/accommodations/AccommodationsSlice';
import reviewsReducer from '../features/reviews/ReviewsSlice';
import usersReducer from '../features/users/UsersSlice';
import solidarityProjectsReducer from '../features/solidarityProjects/SolidarityProjectsSlice';
import dashboardReducer from '../features/dashboard/DashboardSlice';

export const store = configureStore({
  reducer: {
    auth: authReducer,
    reservations: reservationsReducer,
    accommodations: accommodationsReducer,
    reviews: reviewsReducer,
    users: usersReducer,
    solidarityProjects: solidarityProjectsReducer,
    dashboard: dashboardReducer,
  },
});

// On 401 the API client purges the storage; the store logs the session out,
// which makes ProtectedRoute redirect to /login.
setUnauthorizedHandler(() => {
  store.dispatch(loggedOut());
});

export type RootState = ReturnType<typeof store.getState>;
export type AppDispatch = typeof store.dispatch;
