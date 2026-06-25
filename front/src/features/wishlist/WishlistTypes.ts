/** A saved accommodation as returned by GET /api/wishlist (card-ready summary). */
export interface WishlistItem {
  accommodationId: string;
  title: string | null;
  city: string | null;
  country: string | null;
  price: number | null;
  photoUrl: string | null;
}
