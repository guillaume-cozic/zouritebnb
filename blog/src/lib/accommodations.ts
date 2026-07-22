import { ZONES } from './zones';

/**
 * Published accommodations, fetched from the API once per build.
 *
 * The blog is fully static: this runs at build time (CI reaches the public
 * production API, the dev container reaches the API over the Docker network
 * via API_INTERNAL_URL). If the API is unreachable the build must NOT fail —
 * pages simply render without accommodation cards.
 */
export interface Accommodation {
  id: string;
  title: string;
  city: string;
  price: number;
  type: string;
  maxGuests: number;
  reviewCount: number;
  averageRating: number | null;
  /** Absolute URL of the first photo, or null. */
  photoUrl: string | null;
  /** Slug of the nearest zone (see zones.ts). */
  zoneSlug: string;
}

/** Browser-facing API URL (photos); also the fetch fallback. */
export const PUBLIC_API_URL: string = import.meta.env.PUBLIC_API_URL ?? '';

/**
 * Build-time fetch URL (Docker-internal in dev), falls back to the public one.
 * Read from process.env: only PUBLIC_* vars reach import.meta.env, and this
 * code only ever runs in Node (static build), never in the browser.
 */
const INTERNAL_API_URL: string = process.env.API_INTERNAL_URL ?? PUBLIC_API_URL;

function distanceSquared(
  a: { latitude: number; longitude: number },
  b: { latitude: number; longitude: number },
): number {
  // Rodrigues spans ~0.2°; plain equirectangular distance is plenty here.
  const dLat = a.latitude - b.latitude;
  const dLng = (a.longitude - b.longitude) * Math.cos((a.latitude * Math.PI) / 180);
  return dLat * dLat + dLng * dLng;
}

function nearestZoneSlug(latitude: number, longitude: number): string {
  let best = ZONES[0];
  let bestD = Infinity;
  for (const zone of ZONES) {
    const d = distanceSquared({ latitude, longitude }, zone.center);
    if (d < bestD) {
      bestD = d;
      best = zone;
    }
  }
  return best.slug;
}

function toAbsolutePhotoUrl(path: string | undefined): string | null {
  if (!path) return null;
  if (/^https?:\/\//.test(path)) return path;
  if (!PUBLIC_API_URL) return null;
  return `${PUBLIC_API_URL.replace(/\/$/, '')}${path}`;
}

async function fetchAccommodations(): Promise<Accommodation[]> {
  if (!INTERNAL_API_URL) {
    console.warn('[blog] PUBLIC_API_URL not set — accommodation blocks disabled.');
    return [];
  }

  const url = `${INTERNAL_API_URL.replace(/\/$/, '')}/api/accommodations`;
  try {
    const response = await fetch(url, {
      headers: { Accept: 'application/ld+json' },
      signal: AbortSignal.timeout(15_000),
    });
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }
    const body = await response.json();
    const items: any[] = body.member ?? body['hydra:member'] ?? [];

    const accommodations = items
      .filter((i) => i.status === 'published' && i.latitude != null && i.longitude != null)
      .map(
        (i): Accommodation => ({
          id: i.id,
          title: i.title,
          city: i.city,
          price: i.price,
          type: i.type,
          maxGuests: i.maxGuests,
          reviewCount: i.reviewCount ?? 0,
          averageRating: i.averageRating ?? null,
          photoUrl: toAbsolutePhotoUrl(i.photoUrls?.[0]),
          zoneSlug: nearestZoneSlug(i.latitude, i.longitude),
        }),
      );

    console.info(`[blog] ${accommodations.length} accommodations loaded from ${url}`);
    return accommodations;
  } catch (error) {
    console.warn(`[blog] Could not load accommodations from ${url} — building without them.`, error);
    return [];
  }
}

// One fetch per build, shared by every page.
let cache: Promise<Accommodation[]> | undefined;

export function getAccommodations(): Promise<Accommodation[]> {
  cache ??= fetchAccommodations();
  return cache;
}

/** Accommodations of a zone, best first (most reviewed, then cheapest). */
export async function accommodationsInZone(zoneSlug: string): Promise<Accommodation[]> {
  const all = await getAccommodations();
  return all
    .filter((a) => a.zoneSlug === zoneSlug)
    .sort((a, b) => b.reviewCount - a.reviewCount || a.price - b.price);
}

/**
 * Chemin public d'une annonce sur le front : /hebergements/<slug>--<uuid>.
 * Même convention de slug que le front (accommodationUrl.ts) et l'API
 * (SitemapController) — l'UUID final reste la clé de résolution.
 */
export function accommodationPath(a: { id: string; title?: string; city?: string }): string {
  const slug = [a.title, a.city]
    .filter(Boolean)
    .join(' ')
    .toLowerCase()
    .replace(/œ/g, 'oe')
    .replace(/æ/g, 'ae')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');
  return slug ? `/hebergements/${slug}--${a.id}` : `/accommodations/${a.id}`;
}
