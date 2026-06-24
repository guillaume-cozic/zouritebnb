import type { CollectionEntry } from 'astro:content';

export type Lang = 'fr' | 'en';

export const DEFAULT_LANG: Lang = 'fr';

/**
 * Convention:
 * - FR articles live as flat files: `articles/<slug>.md` (lang defaults to 'fr').
 *   They are served at the existing, unprefixed URL `/blog/<slug>/`.
 * - EN articles live in the `articles/en/` subfolder: `articles/en/<slug>.md`
 *   with `lang: en` in their frontmatter. Their collection slug is `en/<slug>`,
 *   which Astro's catch-all route turns into `/blog/en/<slug>/`.
 *
 * Two language versions of the same article are linked by a shared
 * `translationKey` (defaults to the locale-stripped slug when omitted).
 */

/** The slug without any locale prefix (e.g. `en/foo` -> `foo`). */
export function bareSlug(entry: CollectionEntry<'articles'>): string {
  return entry.slug.replace(/^en\//, '');
}

/** Stable key shared by the FR and EN versions of the same article. */
export function translationKeyOf(entry: CollectionEntry<'articles'>): string {
  return entry.data.translationKey ?? bareSlug(entry);
}

/** Public URL of an article, accounting for its language prefix. */
export function articleHref(baseUrl: string, entry: CollectionEntry<'articles'>): string {
  const base = baseUrl.replace(/\/$/, '');
  return `${base}/${entry.slug}/`;
}

/** Localized home URL: `/blog/` for fr, `/blog/en/` for en. */
export function homeHref(baseUrl: string, lang: Lang): string {
  const base = baseUrl.replace(/\/$/, '');
  return lang === DEFAULT_LANG ? `${base}/` : `${base}/${lang}/`;
}

/**
 * Find the counterpart article in the other language by matching translationKey.
 * Returns `undefined` when no translation exists.
 */
export function counterpart(
  entry: CollectionEntry<'articles'>,
  all: CollectionEntry<'articles'>[],
): CollectionEntry<'articles'> | undefined {
  const key = translationKeyOf(entry);
  const otherLang: Lang = entry.data.lang === 'en' ? 'fr' : 'en';
  return all.find(
    (e) => e.data.lang === otherLang && translationKeyOf(e) === key,
  );
}
