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

export type WizardStep = 'description' | 'capacity' | 'address' | 'photos' | 'success';
