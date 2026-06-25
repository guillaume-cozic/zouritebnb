import { isAnyOf } from '@reduxjs/toolkit';
import { startAppListening } from '../../store/listenerMiddleware';
import { loginUser, registerUser } from '../auth/AuthSlice';
import { fetchWishlist, mergeWishlist } from './WishlistSlice';

// On sign-in / sign-up, merge the anonymous wishlist (cookie) into the account,
// then reload the now-unified wishlist so the hearts stay in sync.
startAppListening({
  matcher: isAnyOf(loginUser.fulfilled, registerUser.fulfilled),
  effect: async (_action, api) => {
    await api.dispatch(mergeWishlist());
    await api.dispatch(fetchWishlist());
  },
});
