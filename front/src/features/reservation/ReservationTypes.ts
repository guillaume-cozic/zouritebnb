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
  /** Number of travellers, validated against the accommodation capacity at booking time. */
  guestCount?: number;
  status: ReservationStatus;
  totalPrice?: number;
  pricePerNight?: number;
  appliedDiscountPercentage?: number | null;
  /** Grand total paid by the guest (stay + service fee + donation), matching the invoice. */
  totalPaid?: number;
  /** Cancellation policy snapshotted at booking time. */
  cancellationPolicy?: 'flexible' | 'moderate';
  /** Whether the reservation can be cancelled right now (pending/confirmed and stay not started). */
  cancellable?: boolean;
  /** Amount refunded if cancelled now, per policy and current date. */
  refundAmount?: number | null;
  /** Refunded share if cancelled now (0, 50 or 100). */
  refundPercentage?: number | null;
  /** Guest-requested date change awaiting host approval, or null. */
  pendingModification?: PendingModification | null;
}

export interface PendingModification {
  checkIn: string;
  checkOut: string;
  totalPrice: number;
  totalPaid: number;
  /** Difference in total paid vs the current reservation (positive = extra to pay). */
  priceDifference: number;
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
  guestCount: number;
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
