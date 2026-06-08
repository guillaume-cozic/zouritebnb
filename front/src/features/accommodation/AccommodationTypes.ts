export interface Accommodation {
  '@id'?: string;
  id?: string;
  title?: string;
  description?: string;
  price?: number;
  weeklyPromotionPercentage?: number | null;
  status?: 'draft' | 'published';
  street?: string;
  city?: string;
  zipCode?: string;
  country?: string;
  latitude?: number;
  longitude?: number;
  bedrooms?: number;
  bathrooms?: number;
  maxGuests?: number;
  singleBeds?: number;
  doubleBeds?: number;
  amenities?: string[];
  checkIn?: string | null;
  checkOut?: string | null;
  teamId?: string | null;
  thumbnailUrl?: string | null;
  photos?: { id: string; url: string }[];
  averageRating?: number | null;
  reviewCount?: number;
}

export interface AddressDraft {
  street: string;
  city: string;
  zipCode: string;
  country: string;
  latitude?: number;
  longitude?: number;
}

export interface FormDrafts {
  capacity?: { bedrooms: number; bathrooms: number; maxGuests: number; singleBeds: number; doubleBeds: number };
  amenities?: string[];
  address?: AddressDraft;
}

export interface CreateAccommodationPayload {
  title: string;
  description: string;
  price: number;
}

export interface SetLocationPayload {
  id: string;
  street: string;
  city: string;
  zipCode: string;
  country: string;
  latitude?: number;
  longitude?: number;
}

export interface AddPhotoPayload {
  id: string;
  file: File;
}

export interface SetCapacityPayload {
  id: string;
  bedrooms: number;
  bathrooms: number;
  maxGuests: number;
  singleBeds: number;
  doubleBeds: number;
}

export interface SetAmenitiesPayload {
  id: string;
  codes: string[];
}

export interface UpdatePricePayload {
  id: string;
  price: number;
}

export interface UpdateWeeklyPromotionPayload {
  id: string;
  weeklyPromotionPercentage: number | null;
}

export interface SetCheckInOutPayload {
  id: string;
  checkIn: string;
  checkOut: string;
}

export interface DeletePhotoPayload {
  id: string;
  photoId: string;
}

export interface ReorderPhotosPayload {
  id: string;
  photoIds: string[];
}

export interface UpdateDescriptionPayload {
  id: string;
  title: string;
  description: string;
}

export type WizardStep = 'description' | 'capacity' | 'amenities' | 'address' | 'photos' | 'success';
