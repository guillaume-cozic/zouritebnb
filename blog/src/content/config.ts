import { defineCollection, z } from 'astro:content';

const articles = defineCollection({
  type: 'content',
  schema: z.object({
    title: z.string(),
    summary: z.string(),
    category: z.enum([
      'reserves-et-parcs',
      'points-de-vue',
      'randonnees',
      'sorties-en-mer',
      'sites-naturels',
      'patrimoine',
      'itineraires',
    ]),
    duration: z.string().optional(),
    difficulty: z.enum(['facile', 'modere', 'difficile']).optional(),
    guideRequired: z.boolean().default(false),
    zone: z.string().optional(),
    order: z.number().default(100),
    cover: z.string().optional(),
    tags: z.array(z.string()).default([]),
  }),
});

export const collections = { articles };
