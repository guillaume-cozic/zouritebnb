import type { Lang } from './i18n';

/**
 * Canonical geographic zones of Rodrigues, used to connect blog articles
 * (frontmatter `zone`, free text) with accommodations (lat/lng from the API)
 * and to generate the "where to stay" landing pages.
 */
export interface Zone {
  /** URL slug, shared by the FR and EN landing pages. */
  slug: string;
  /** Zone center, used to attach each accommodation to its nearest zone. */
  center: { latitude: number; longitude: number };
  name: Record<Lang, string>;
  /** Short display name used in chips/links ("à Port Mathurin"). */
  shortName: Record<Lang, string>;
  intro: Record<Lang, string>;
}

export const ZONES: Zone[] = [
  {
    slug: 'port-mathurin',
    center: { latitude: -19.6797, longitude: 63.4181 },
    name: {
      fr: 'Port Mathurin et la côte nord',
      en: 'Port Mathurin and the north coast',
    },
    shortName: { fr: 'à Port Mathurin', en: 'in Port Mathurin' },
    intro: {
      fr: "Port Mathurin est la capitale et le cœur battant de Rodrigues : son marché du samedi matin, ses commerces et son front de mer en font le point de chute le plus pratique de l'île. Loger sur la côte nord, c'est être à proximité de tout, tout en restant à quelques minutes des plages et des sentiers.",
      en: "Port Mathurin is the capital and beating heart of Rodrigues: its Saturday morning market, shops and seafront make it the most convenient base on the island. Staying on the north coast puts you close to everything, just minutes from the beaches and trails.",
    },
  },
  {
    slug: 'anse-aux-anglais',
    center: { latitude: -19.6739, longitude: 63.4409 },
    name: {
      fr: "Anse aux Anglais et le nord-est",
      en: 'Anse aux Anglais and the north-east',
    },
    shortName: { fr: "à Anse aux Anglais", en: 'in Anse aux Anglais' },
    intro: {
      fr: "À dix minutes à pied de Port Mathurin, Anse aux Anglais est le petit village balnéaire préféré des voyageurs : guesthouses face au lagon, tables d'hôtes et départ des belles balades côtières vers Grand Baie et Baladirou.",
      en: "A ten-minute walk from Port Mathurin, Anse aux Anglais is travellers' favourite seaside village: guesthouses facing the lagoon, table d'hôte dinners and the starting point of beautiful coastal walks towards Grand Baie and Baladirou.",
    },
  },
  {
    slug: 'pointe-coton',
    center: { latitude: -19.689, longitude: 63.4995 },
    name: {
      fr: 'Pointe Coton et la côte est',
      en: 'Pointe Coton and the east coast',
    },
    shortName: { fr: 'sur la côte est', en: 'on the east coast' },
    intro: {
      fr: "La côte est concentre les plus belles plages de Rodrigues : Pointe Coton, Saint François, Trou d'Argent… Loger ici, c'est ouvrir ses volets sur le lagon et partir à pied sur le plus beau sentier littoral de l'île.",
      en: "The east coast has the finest beaches in Rodrigues: Pointe Coton, Saint François, Trou d'Argent… Stay here and you open your shutters onto the lagoon, with the island's most beautiful coastal path right on your doorstep.",
    },
  },
  {
    slug: 'mourouk',
    center: { latitude: -19.7328, longitude: 63.45 },
    name: {
      fr: 'Mourouk et le sud-est',
      en: 'Mourouk and the south-east',
    },
    shortName: { fr: 'à Mourouk', en: 'in Mourouk' },
    intro: {
      fr: "Face à la passe et aux îles du sud, Mourouk et Port Sud-Est sont le paradis des kitesurfeurs et des amoureux du grand lagon. C'est aussi le point de départ des excursions vers l'île aux Chats et l'île Hermitage.",
      en: "Facing the reef pass and the southern islets, Mourouk and Port Sud-Est are a paradise for kitesurfers and lagoon lovers. It is also the departure point for boat trips to île aux Chats and île Hermitage.",
    },
  },
  {
    slug: 'cote-ouest',
    center: { latitude: -19.7069, longitude: 63.3608 },
    name: {
      fr: "L'ouest et la plaine Corail",
      en: 'The west and Plaine Corail',
    },
    shortName: { fr: "dans l'ouest", en: 'in the west' },
    intro: {
      fr: "L'ouest de Rodrigues, de La Ferme à la plaine Corail, est le plus sauvage : la Caverne Patate, la réserve François Leguat et l'embarquement pour l'île aux Cocos. Un bon choix pour qui cherche le calme et les grands espaces.",
      en: "The west of Rodrigues, from La Ferme to Plaine Corail, is the wildest part of the island: Caverne Patate, the François Leguat reserve and the boats to île aux Cocos. A great choice if you are after peace and open landscapes.",
    },
  },
  {
    slug: 'centre',
    center: { latitude: -19.7075, longitude: 63.437 },
    name: {
      fr: "Le centre de l'île",
      en: 'The centre of the island',
    },
    shortName: { fr: "au centre de l'île", en: 'in the centre of the island' },
    intro: {
      fr: "Mont Lubin, Grande Montagne, Citronnelle : le centre de Rodrigues, frais et verdoyant, est le domaine des crêtes et des panoramas. On y loge chez l'habitant, au plus près de la vie rodriguaise, à vingt minutes de toutes les côtes.",
      en: "Mont Lubin, Grande Montagne, Citronnelle: the cool, green centre of Rodrigues is a land of ridges and panoramas. Staying here means living close to Rodriguan daily life, twenty minutes from every coast.",
    },
  },
];

export function zoneBySlug(slug: string): Zone | undefined {
  return ZONES.find((z) => z.slug === slug);
}

/**
 * Maps the free-text `zone` frontmatter of articles (FR and EN variants)
 * to canonical zone slugs. Crossings map to both endpoints, first = primary.
 * Unknown strings simply yield no zone (the article renders without the
 * "where to stay" block).
 */
const ARTICLE_ZONE_MAP: Record<string, string[]> = {
  'centre': ['centre'],
  "centre de l'île": ['centre'],
  'centre et intérieur': ['centre'],
  'centre and interior': ['centre'],
  'centre of the island': ['centre'],
  'côte est': ['pointe-coton'],
  'east coast': ['pointe-coton'],
  'côte est / nord-est': ['pointe-coton'],
  'east / north-east coast': ['pointe-coton'],
  'côte nord-est': ['anse-aux-anglais'],
  'north-east coast': ['anse-aux-anglais'],
  'côte nord-ouest': ['port-mathurin'],
  'north-west coast': ['port-mathurin'],
  'lagon nord-ouest': ['cote-ouest'],
  'north-west lagoon': ['cote-ouest'],
  'lagon sud': ['mourouk'],
  'southern lagoon': ['mourouk'],
  'sud-est': ['mourouk'],
  'south-east': ['mourouk'],
  'sud-ouest': ['cote-ouest'],
  'south-west': ['cote-ouest'],
  'traversée centre → est': ['centre', 'pointe-coton'],
  'centre → east crossing': ['centre', 'pointe-coton'],
  'traversée nord → sud': ['port-mathurin', 'mourouk'],
  'north → south crossing': ['port-mathurin', 'mourouk'],
};

export function zoneSlugsForArticleZone(articleZone: string | undefined): string[] {
  if (!articleZone) return [];
  return ARTICLE_ZONE_MAP[articleZone.trim().toLowerCase()] ?? [];
}

/** URL of a zone landing page, localized. */
export function zoneHref(baseUrl: string, zone: Zone, lang: Lang): string {
  const base = baseUrl.replace(/\/$/, '');
  return lang === 'en'
    ? `${base}/en/where-to-stay/${zone.slug}/`
    : `${base}/ou-dormir/${zone.slug}/`;
}
