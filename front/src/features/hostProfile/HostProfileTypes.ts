export interface HostProfile {
  teamId: string;
  firstName: string | null;
  lastName: string | null;
  bio: string | null;
  /** Relative URL of the host avatar (prefix with the API base), or null. */
  avatarUrl: string | null;
}
