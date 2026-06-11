export interface AdminPlatformUser {
  id: string;
  email: string;
  firstName: string | null;
  lastName: string | null;
  roles: string[];
  verificationStatus: string;
  teamId: string | null;
  accommodationCount: number;
  reservationCount: number;
}

export interface UsersState {
  items: AdminPlatformUser[];
  status: 'idle' | 'loading' | 'succeeded' | 'failed';
  error: string | null;
}
