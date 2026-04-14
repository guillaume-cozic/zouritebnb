export interface AuthUser {
  id: string;
  email: string;
  teamId: string;
  firstName?: string | null;
  lastName?: string | null;
}
