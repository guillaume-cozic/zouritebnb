import { configureStore } from '@reduxjs/toolkit';
import counterReducer from '../features/counter/counterSlice';
import accommodationReducer from '../features/accommodation/AccommodationSlice';
import homepageReducer from '../features/homepage/HomepageSlice';
import accommodationManagementReducer from '../features/accommodationManagement/AccommodationManagementSlice';
import solidarityProjectReducer from '../features/solidarityProject/SolidarityProjectSlice';
import teamReducer from '../features/team/TeamSlice';
import authReducer from '../features/auth/AuthSlice';
import reservationReducer from '../features/reservation/ReservationSlice';
import conversationReducer from '../features/conversation/ConversationSlice';
import { listenerMiddleware } from './listenerMiddleware';
import './registerListeners';

export const store = configureStore({
  reducer: {
    counter: counterReducer,
    accommodation: accommodationReducer,
    homepage: homepageReducer,
    accommodationManagement: accommodationManagementReducer,
    solidarityProject: solidarityProjectReducer,
    team: teamReducer,
    auth: authReducer,
    reservation: reservationReducer,
    conversation: conversationReducer,
  },
  middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware().prepend(listenerMiddleware.middleware),
});

export type RootState = ReturnType<typeof store.getState>;
export type AppDispatch = typeof store.dispatch;
