export type ReservationStatus = 'pending' | 'confirmed' | 'cancelled';

export interface Reservation {
  '@id'?: string;
  id: string;
  accommodationId: string;
  teamId: string;
  checkIn: string;
  checkOut: string;
  guestName: string;
  status: ReservationStatus;
}

export interface CreateReservationPayload {
  accommodationId: string;
  checkIn: string;
  checkOut: string;
  guestName: string;
}

export interface FetchReservationsParams {
  accommodationId?: string;
  from?: string;
  to?: string;
}
