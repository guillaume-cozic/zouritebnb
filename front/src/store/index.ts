import { configureStore } from '@reduxjs/toolkit';
import counterReducer from '../features/counter/counterSlice';
import accommodationReducer from '../features/accommodation/AccommodationSlice';
import accommodationSummaryReducer from '../features/accommodation/AccommodationSummarySlice';
import homepageReducer from '../features/homepage/HomepageSlice';
import accommodationManagementReducer from '../features/accommodationManagement/AccommodationManagementSlice';
import solidarityProjectReducer from '../features/solidarityProject/SolidarityProjectSlice';
import teamReducer from '../features/team/TeamSlice';
import authReducer from '../features/auth/AuthSlice';
import reservationReducer from '../features/reservation/ReservationSlice';
import conversationReducer from '../features/conversation/ConversationSlice';
import geographyReducer from '../features/geography/GeographySlice';
import paymentReducer from '../features/payment/PaymentSlice';
import donationReducer from '../features/donation/DonationSlice';
import reviewReducer from '../features/review/ReviewSlice';
import userProfileReducer from '../features/userProfile/UserProfileSlice';
import hostProfileReducer from '../features/hostProfile/HostProfileSlice';
import wishlistReducer from '../features/wishlist/WishlistSlice';
import hostRevenueReducer from '../features/hostRevenue/HostRevenueSlice';
import activityPointReducer from '../features/activityPoint/ActivityPointSlice';
import { listenerMiddleware } from './listenerMiddleware';
import './registerListeners';

export const store = configureStore({
  reducer: {
    counter: counterReducer,
    accommodation: accommodationReducer,
    accommodationSummary: accommodationSummaryReducer,
    homepage: homepageReducer,
    accommodationManagement: accommodationManagementReducer,
    solidarityProject: solidarityProjectReducer,
    team: teamReducer,
    auth: authReducer,
    reservation: reservationReducer,
    conversation: conversationReducer,
    geography: geographyReducer,
    payment: paymentReducer,
    donation: donationReducer,
    review: reviewReducer,
    userProfile: userProfileReducer,
    hostProfile: hostProfileReducer,
    wishlist: wishlistReducer,
    hostRevenue: hostRevenueReducer,
    activityPoint: activityPointReducer,
  },
  middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware().prepend(listenerMiddleware.middleware),
});

export type RootState = ReturnType<typeof store.getState>;
export type AppDispatch = typeof store.dispatch;
