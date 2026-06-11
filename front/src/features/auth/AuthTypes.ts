export interface AuthUser {
  id: string;
  email: string;
  teamId: string;
  firstName?: string | null;
  lastName?: string | null;
  /** Identity verification status: not_started | pending | verified | rejected. */
  verificationStatus?: string | null;
  /** JWT Bearer returned by /api/login, attached to authenticated requests. */
  token?: string | null;
}
