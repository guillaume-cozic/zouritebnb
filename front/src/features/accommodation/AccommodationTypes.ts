/** Host-selectable cancellation policy for an accommodation. */
export type CancellationPolicy = 'flexible' | 'moderate';

/** Host-selectable accommodation category. */
export type AccommodationType = 'apartment' | 'house' | 'villa' | 'studio' | 'room' | 'bungalow';

/** Seasonal / per-date nightly override. Dates are Y-m-d strings, inclusive range. */
export interface PricePeriod {
  startDate: string;
  endDate: string;
  pricePerNight: number;
}

/** All accommodation types, in display order. */
export const ACCOMMODATION_TYPES: AccommodationType[] = ['apartment', 'house', 'villa', 'studio', 'room', 'bungalow'];

export interface Accommodation {
  '@id'?: string;
  id?: string;
  title?: string;
  description?: string;
  price?: number;
  weeklyPromotionPercentage?: number | null;
  /** Surcharge (%) applied to Friday/Saturday nights. */
  weekendSurchargePercentage?: number | null;
  /** Discount (%) when booking within lastMinuteDays of check-in. */
  lastMinuteDiscountPercentage?: number | null;
  lastMinuteDays?: number | null;
  /** Seasonal / per-date nightly overrides. */
  pricePeriods?: PricePeriod[];
  cancellationPolicy?: CancellationPolicy;
  /** When true, guest requests are auto-confirmed without host approval. */
  instantBooking?: boolean;
  type?: AccommodationType | null;
  minNights?: number | null;
  maxNights?: number | null;
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
  /** House rules shown on the accommodation page. */
  smokingAllowed?: boolean;
  petsAllowed?: boolean;
  partiesAllowed?: boolean;
  /** Free-text additional house rules set by the host. */
  houseRulesNotes?: string | null;
  teamId?: string | null;
  /** Host's featured solidarity project (UUID), exposed publicly. */
  favoriteSolidarityProjectId?: string | null;
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

export interface UpdateDynamicPricingPayload {
  id: string;
  weekendSurchargePercentage: number | null;
  lastMinuteDiscountPercentage: number | null;
  lastMinuteDays: number | null;
}

export interface UpdatePricePeriodsPayload {
  id: string;
  pricePeriods: PricePeriod[];
}

export interface UpdateCancellationPolicyPayload {
  id: string;
  cancellationPolicy: CancellationPolicy;
}

export interface UpdateInstantBookingPayload {
  id: string;
  instantBooking: boolean;
}

export interface UpdateTypePayload {
  id: string;
  type: AccommodationType | null;
}

export interface UpdateStayConstraintsPayload {
  id: string;
  minNights: number | null;
  maxNights: number | null;
}

export interface UpdateHouseRulesPayload {
  id: string;
  smokingAllowed: boolean;
  petsAllowed: boolean;
  partiesAllowed: boolean;
  houseRulesNotes: string | null;
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
