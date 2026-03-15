import { configureStore } from '@reduxjs/toolkit';
import counterReducer from '../features/counter/counterSlice';
import accommodationReducer from '../features/accommodation/AccommodationSlice';

export const store = configureStore({
  reducer: {
    counter: counterReducer,
    accommodation: accommodationReducer,
  },
});

export type RootState = ReturnType<typeof store.getState>;
export type AppDispatch = typeof store.dispatch;
