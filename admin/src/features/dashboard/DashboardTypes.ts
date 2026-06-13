export interface DonationByProject {
  projectId: string;
  title: string;
  amount: number;
}

export interface AdminDashboard {
  totalRevenue: number;
  totalMargin: number;
  totalDonated: number;
  confirmedReservations: number;
  upcomingStays: number;
  commissionRate: number;
  donationRate: number;
  donationsByProject: DonationByProject[];
}

export interface DashboardState {
  data: AdminDashboard | null;
  status: 'idle' | 'loading' | 'succeeded' | 'failed';
  error: string | null;
}
