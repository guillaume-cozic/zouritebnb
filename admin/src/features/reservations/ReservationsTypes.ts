export interface AdminReservation {
  id: string;
  guestName: string;
  guestUserId: string | null;
  accommodationId: string;
  accommodationTitle: string | null;
  teamId: string;
  checkIn: string;
  checkOut: string;
  status: string;
  totalPrice: number;
  pricePerNight: number;
  appliedDiscountPercentage: number | null;
}

export interface ReservationsState {
  items: AdminReservation[];
  status: 'idle' | 'loading' | 'succeeded' | 'failed';
  error: string | null;
}
