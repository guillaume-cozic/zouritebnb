import { configureStore } from '@reduxjs/toolkit';
import counterReducer from '../features/counter/counterSlice';
import accommodationReducer from '../features/accommodation/AccommodationSlice';
import homepageReducer from '../features/homepage/HomepageSlice';
import accommodationManagementReducer from '../features/accommodationManagement/AccommodationManagementSlice';

export const store = configureStore({
  reducer: {
    counter: counterReducer,
    accommodation: accommodationReducer,
    homepage: homepageReducer,
    accommodationManagement: accommodationManagementReducer,
  },
});

export type RootState = ReturnType<typeof store.getState>;
export type AppDispatch = typeof store.dispatch;
