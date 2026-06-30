export type PayoutStatus = 'pending' | 'available';

export interface PayoutLine {
  reservationId: string;
  accommodationTitle: string | null;
  guestName: string;
  checkIn: string;
  checkOut: string;
  amount: number;
  status: PayoutStatus;
}

export interface RevenueByAccommodation {
  accommodationId: string | null;
  title: string | null;
  amount: number;
  reservations: number;
}

export interface RevenueByMonth {
  month: string; // YYYY-MM
  amount: number;
}

export interface HostRevenue {
  id: string;
  totalEarned: number;
  pendingAmount: number;
  availableAmount: number;
  confirmedReservations: number;
  upcomingStays: number;
  byAccommodation: RevenueByAccommodation[];
  byMonth: RevenueByMonth[];
  payouts: PayoutLine[];
}
