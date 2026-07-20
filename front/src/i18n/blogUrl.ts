const BLOG_URL = import.meta.env.VITE_BLOG_URL ?? '/blog';

/** Home page of the blog in the given language (the English version lives under /en/). */
export function blogHomeUrl(lang: string): string {
  return lang === 'en' ? `${BLOG_URL}/en/` : `${BLOG_URL}/`;
}

/** Landing page « où dormir » d'une zone de l'île sur le blog (slugs partagés fr/en). */
export function blogWhereToStayUrl(lang: string, slug: string): string {
  return lang === 'en'
    ? `${BLOG_URL}/en/where-to-stay/${slug}/`
    : `${BLOG_URL}/ou-dormir/${slug}/`;
}

/** Deep links to the blog's activity sections; tags are localized on the blog side. */
export function blogActivityLinks(lang: string): {
  diving: string;
  hiking: string;
  excursions: string;
  gastronomy: string;
} {
  const base = blogHomeUrl(lang);
  const tags =
    lang === 'en'
      ? { diving: 'Snorkeling', excursions: 'Boat', gastronomy: 'Food' }
      : { diving: 'Snorkeling', excursions: 'Bateau', gastronomy: 'Gastronomie' };

  return {
    diving: `${base}#tag=${encodeURIComponent(tags.diving)}`,
    hiking: `${base}#cat-randonnees`,
    excursions: `${base}#tag=${encodeURIComponent(tags.excursions)}`,
    gastronomy: `${base}#tag=${encodeURIComponent(tags.gastronomy)}`,
  };
}
