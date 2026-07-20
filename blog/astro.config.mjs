import { defineConfig } from 'astro/config';
import tailwind from '@astrojs/tailwind';
import sitemap from '@astrojs/sitemap';

export default defineConfig({
  site: 'https://www.zouritebnb.com',
  base: '/blog',
  trailingSlash: 'always',
  server: {
    host: true,
    port: 4321,
  },
  build: {
    format: 'directory',
  },
  integrations: [tailwind(), sitemap()],
});
