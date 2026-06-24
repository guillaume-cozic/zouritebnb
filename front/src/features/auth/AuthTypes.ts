export interface AuthUser {
  id: string;
  email: string;
  teamId: string;
  firstName?: string | null;
  lastName?: string | null;
  /** Public host presentation shown on accommodation pages and in messaging. */
  bio?: string | null;
  /** Relative URL of the host avatar (prefix with the API base), or null. */
  avatarUrl?: string | null;
  /** Identity verification status: not_started | pending | verified | rejected. */
  verificationStatus?: string | null;
  /** JWT Bearer returned by /api/login, attached to authenticated requests. */
  token?: string | null;
}
