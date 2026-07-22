/**
 * URLs publiques des annonces : `/hebergements/<slug>--<uuid>`. Le slug (titre
 * + ville) porte les mots-clés pour le SEO ; l'UUID en fin d'URL reste la seule
 * clé de résolution, donc un slug modifié ou tronqué fonctionne toujours.
 * L'ancienne forme `/accommodations/<uuid>` est conservée en redirection.
 *
 * La même logique de slug existe côté API (SitemapController) et blog
 * (lib/accommodations.ts) — garder les trois synchronisées.
 */

const UUID_AT_END = /([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})$/i;

export const slugify = (value: string): string =>
  value
    .toLowerCase()
    .replace(/œ/g, 'oe')
    .replace(/æ/g, 'ae')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');

export const accommodationPath = (accommodation: {
  id?: string;
  title?: string | null;
  city?: string | null;
}): string => {
  if (!accommodation.id) return '/accommodations';
  const slug = slugify([accommodation.title, accommodation.city].filter(Boolean).join(' '));
  return slug ? `/hebergements/${slug}--${accommodation.id}` : `/accommodations/${accommodation.id}`;
};

/** Extrait l'UUID d'un slug d'URL `<slug>--<uuid>` (ou d'un UUID nu). */
export const accommodationIdFromSlug = (slug: string | undefined): string | null => {
  if (!slug) return null;
  const match = slug.match(UUID_AT_END);
  return match ? match[1].toLowerCase() : null;
};
