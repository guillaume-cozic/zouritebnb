import { configureStore } from '@reduxjs/toolkit';
import counterReducer from '../features/counter/counterSlice';
import accommodationReducer from '../features/accommodation/AccommodationSlice';
import homepageReducer from '../features/homepage/HomepageSlice';
import accommodationManagementReducer from '../features/accommodationManagement/AccommodationManagementSlice';
import solidarityProjectReducer from '../features/solidarityProject/SolidarityProjectSlice';

export const store = configureStore({
  reducer: {
    counter: counterReducer,
    accommodation: accommodationReducer,
    homepage: homepageReducer,
    accommodationManagement: accommodationManagementReducer,
    solidarityProject: solidarityProjectReducer,
  },
});

export type RootState = ReturnType<typeof store.getState>;
export type AppDispatch = typeof store.dispatch;
