export type ReservationStatus = 'pending' | 'confirmed' | 'cancelled' | 'refused';

export interface Reservation {
  '@id'?: string;
  id: string;
  accommodationId: string;
  teamId: string;
  guestUserId?: string | null;
  checkIn: string;
  checkOut: string;
  guestName: string;
  status: ReservationStatus;
  totalPrice?: number;
  pricePerNight?: number;
  appliedDiscountPercentage?: number | null;
  /** Grand total paid by the guest (stay + service fee + donation), matching the invoice. */
  totalPaid?: number;
}

export interface CreateReservationPayload {
  accommodationId: string;
  checkIn: string;
  checkOut: string;
  guestName: string;
}

export interface RequestReservationPayload {
  accommodationId: string;
  checkIn: string;
  checkOut: string;
  guestName: string;
  note?: string;
  paymentIntentId?: string;
}

export interface FetchReservationsParams {
  accommodationId?: string;
  from?: string;
  to?: string;
}

/** An unavailable date span for an accommodation (dates in ISO YYYY-MM-DD). */
export interface BusyRange {
  /** First occupied night. */
  checkIn: string;
  /** Departure day, excluded from the booked nights. */
  checkOut: string;
}
