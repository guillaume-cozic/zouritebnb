export interface AccommodationListItem {
  id: string;
  title: string;
  price: number | null;
  city: string | null;
  country: string | null;
  maxGuests: number | null;
  status: string;
  thumbnailUrl: string | null;
}

export interface SearchFilters {
  city: string;
  guests: number | null;
}
