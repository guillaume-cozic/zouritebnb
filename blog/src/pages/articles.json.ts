import type { APIRoute } from 'astro';
import { getCollection } from 'astro:content';

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

export const GET: APIRoute = async () => {
  const articles = await getCollection('articles');
  const payload = articles
    .map((article) => ({
      slug: article.slug,
      title: article.data.title,
      summary: article.data.summary,
      category: article.data.category,
      tags: article.data.tags ?? [],
      body: stripMarkdown(article.body),
    }))
    .sort((a, b) => a.title.localeCompare(b.title, 'fr'));

  return new Response(JSON.stringify(payload), {
    headers: { 'Content-Type': 'application/json; charset=utf-8' },
  });
};
