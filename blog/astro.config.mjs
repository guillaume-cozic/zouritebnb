import { defineConfig } from 'astro/config';
import tailwind from '@astrojs/tailwind';

export default defineConfig({
  site: 'https://rodrigues-bnb.com',
  base: '/blog',
  trailingSlash: 'always',
  build: {
    format: 'directory',
  },
  integrations: [tailwind()],
});
