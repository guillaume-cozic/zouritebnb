export interface AdminAccommodation {
  id: string;
  title: string | null;
  status: string;
  price: number | null;
  city: string | null;
  bedrooms: number | null;
  maxGuests: number | null;
  weeklyPromotionPercentage: number | null;
  teamId: string | null;
  hostEmail: string | null;
}

export interface AccommodationsState {
  items: AdminAccommodation[];
  status: 'idle' | 'loading' | 'succeeded' | 'failed';
  error: string | null;
}
