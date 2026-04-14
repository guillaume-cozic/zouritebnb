export interface ManagedAccommodation {
  id: string;
  title: string;
  description: string | null;
  price: number | null;
  weeklyPromotionPercentage?: number | null;
  city: string | null;
  country: string | null;
  maxGuests: number | null;
  status: string;
  thumbnailUrl: string | null;
}

export type StatusFilter = 'all' | 'published' | 'draft';
