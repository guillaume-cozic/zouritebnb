export interface AccommodationListItem {
  id: string;
  title: string;
  description: string | null;
  price: number | null;
  city: string | null;
  country: string | null;
  latitude: number | null;
  longitude: number | null;
  maxGuests: number | null;
  status: string;
  thumbnailUrl: string | null;
  photoUrls: string[];
  amenities: string[] | null;
  averageRating: number | null;
  reviewCount: number;
}

export interface SearchFilters {
  city: string;
  checkIn: string;
  checkOut: string;
  guests: number | null;
  amenities: string[];
  priceMin: number | null;
  priceMax: number | null;
}
