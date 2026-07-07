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
  instantBooking: boolean;
  type: string | null;
  thumbnailUrl: string | null;
  photoUrls: string[];
  amenities: string[] | null;
  averageRating: number | null;
  reviewCount: number;
}

/**
 * Sort order for the catalog. Empty string = the API's default ("Recommandé",
 * alphabetical). The other values map 1:1 to the `sort` query parameter.
 */
export type SortOption = '' | 'price_asc' | 'price_desc' | 'rating';

/** Geographic bounding box of the map viewport, for "search this area". */
export interface MapBounds {
  north: number;
  south: number;
  east: number;
  west: number;
}

export interface SearchFilters {
  /** Full-text keyword search on title + description. */
  q: string;
  city: string;
  checkIn: string;
  checkOut: string;
  guests: number | null;
  amenities: string[];
  priceMin: number | null;
  priceMax: number | null;
  sort: SortOption;
  instantBooking: boolean;
  type: string;
  bounds: MapBounds | null;
}
