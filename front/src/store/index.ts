import { configureStore } from '@reduxjs/toolkit';
import counterReducer from '../features/counter/counterSlice';
import accommodationReducer from '../features/accommodation/AccommodationSlice';
import homepageReducer from '../features/homepage/HomepageSlice';

export const store = configureStore({
  reducer: {
    counter: counterReducer,
    accommodation: accommodationReducer,
    homepage: homepageReducer,
  },
});

export type RootState = ReturnType<typeof store.getState>;
export type AppDispatch = typeof store.dispatch;
