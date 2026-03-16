export interface Accommodation {
  '@id'?: string;
  id?: string;
  title?: string;
  description?: string;
  price?: number;
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
  thumbnailUrl?: string | null;
}

export interface FormDrafts {
  capacity?: { bedrooms: number; bathrooms: number; maxGuests: number; singleBeds: number; doubleBeds: number };
  amenities?: string[];
  address?: { street: string; city: string; zipCode: string; country: string; latitude?: number; longitude?: number };
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

export type WizardStep = 'description' | 'capacity' | 'amenities' | 'address' | 'photos' | 'success';
