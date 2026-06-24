import type { CollectionEntry } from 'astro:content';
import type { Lang } from './i18n';

const stripMarkdown = (raw: string): string =>
  raw
    .replace(/^---[\s\S]*?---/, '')
    .replace(/```[\s\S]*?```/g, ' ')
    .replace(/`[^`]*`/g, ' ')
    .replace(/!\[[^\]]*]\([^)]*\)/g, ' ')
    .replace(/\[([^\]]+)]\([^)]+\)/g, '$1')
    .replace(/^#{1,6}\s+/gm, '')
    .replace(/[*_~>]/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();

/**
 * Build the client-side search index for a given language.
 * `slug` matches the `data-slug` set on the article cards (the full collection
 * slug, e.g. `en/reserve-grande-montagne` for EN entries), so lookups line up.
 */
export function buildArticleIndex(
  articles: CollectionEntry<'articles'>[],
  lang: Lang,
) {
  return articles
    .filter((article) => article.data.lang === lang)
    .map((article) => ({
      slug: article.slug,
      lang: article.data.lang,
      title: article.data.title,
      summary: article.data.summary,
      category: article.data.category,
      tags: article.data.tags ?? [],
      body: stripMarkdown(article.body),
    }))
    .sort((a, b) => a.title.localeCompare(b.title, lang));
}
