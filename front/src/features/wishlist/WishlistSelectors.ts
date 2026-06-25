import { createSelector } from '@reduxjs/toolkit';
import { RootState } from '../../store';

export const selectWishlistItems = (state: RootState) => state.wishlist.items;
export const selectWishlistStatus = (state: RootState) => state.wishlist.status;
export const selectWishlistSavedIds = (state: RootState) => state.wishlist.savedIds;
export const selectWishlistCount = (state: RootState) => state.wishlist.savedIds.length;

export const selectIsInWishlist = (accommodationId: string | undefined) =>
  createSelector(selectWishlistSavedIds, (ids) =>
    accommodationId ? ids.includes(accommodationId) : false
  );
