import type { APIRoute } from 'astro';
import { getCollection } from 'astro:content';
import { buildArticleIndex } from '../lib/searchIndex';

export const GET: APIRoute = async () => {
  const articles = await getCollection('articles');
  return new Response(JSON.stringify(buildArticleIndex(articles, 'en')), {
    headers: { 'Content-Type': 'application/json; charset=utf-8' },
  });
};
