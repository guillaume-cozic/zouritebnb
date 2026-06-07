export interface AuthUser {
  id: string;
  email: string;
  teamId: string;
  firstName?: string | null;
  lastName?: string | null;
  /** JWT Bearer returned by /api/login, attached to authenticated requests. */
  token?: string | null;
}
