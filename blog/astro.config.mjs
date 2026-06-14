import { defineConfig } from 'astro/config';
import tailwind from '@astrojs/tailwind';

export default defineConfig({
  site: 'https://rodrigues-bnb.com',
  base: '/blog',
  trailingSlash: 'always',
  server: {
    host: true,
    port: 4321,
  },
  build: {
    format: 'directory',
  },
  integrations: [tailwind()],
});
