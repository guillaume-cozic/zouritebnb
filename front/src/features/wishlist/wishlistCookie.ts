/**
 * Anonymous wishlist identity. Non-connected visitors are tracked by a correlation
 * uuid persisted in a first-party cookie; it is sent to the API in the X-Wishlist-Id
 * header so the backend can attach their saved accommodations server-side.
 */
const COOKIE_NAME = 'wishlist_id';
const ONE_YEAR_SECONDS = 60 * 60 * 24 * 365;

const uuid = (): string => {
  if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
    return crypto.randomUUID();
  }
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
    const r = (Math.random() * 16) | 0;
    const v = c === 'x' ? r : (r & 0x3) | 0x8;
    return v.toString(16);
  });
};

const readCookie = (name: string): string | null => {
  if (typeof document === 'undefined') return null;
  const match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
  return match ? decodeURIComponent(match[1]) : null;
};

/** Returns the existing correlation id, or null when the visitor has none yet. */
export const getWishlistCorrelationId = (): string | null => readCookie(COOKIE_NAME);

/** Returns the correlation id, creating and persisting one on first use. */
export const getOrCreateWishlistCorrelationId = (): string => {
  const existing = readCookie(COOKIE_NAME);
  if (existing) return existing;
  const id = uuid();
  if (typeof document !== 'undefined') {
    document.cookie = `${COOKIE_NAME}=${id}; path=/; max-age=${ONE_YEAR_SECONDS}; SameSite=Lax`;
  }
  return id;
};

/** Clears the anonymous correlation cookie, e.g. after merging into an account. */
export const clearWishlistCorrelationId = (): void => {
  if (typeof document === 'undefined') return;
  document.cookie = `${COOKIE_NAME}=; path=/; max-age=0; SameSite=Lax`;
};
