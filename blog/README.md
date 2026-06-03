# Blog — Carnet de Rodrigues

Site statique [Astro](https://astro.build/) qui sert les articles d'activités et de balades à Rodrigues sous le sous-chemin `/blog/` du domaine principal (meilleur SEO qu'un sous-domaine).

## Stack

- **Astro 5** (output statique)
- **Tailwind CSS 3**
- **Content Collections** typées via Zod (`src/content/config.ts`)

## Commandes

```bash
cd blog
npm install
npm run dev       # serveur de dev → http://localhost:4321/blog/
npm run build     # build statique → dist/
npm run preview   # preview du build
```

## Ajouter un article

Créer un fichier `src/content/articles/<slug>.md` avec ce frontmatter :

```yaml
---
title: "Titre de l'article"
summary: "Phrase d'accroche affichée sur la liste et en méta description."
category: "randonnees"   # reserves-et-parcs | points-de-vue | randonnees | sorties-en-mer | sites-naturels | patrimoine | itineraires
duration: "2 heures"     # facultatif
difficulty: "facile"     # facile | modere | difficile — facultatif
guideRequired: true      # défaut false
zone: "Côte est"         # facultatif
order: 50                # ordre dans sa catégorie — défaut 100
---

Contenu Markdown ici…
```

Le slug du fichier est utilisé comme URL : `src/content/articles/balade-mourouk-saint-francois.md` → `/blog/balade-mourouk-saint-francois/`.

## Déploiement & reverse proxy

Astro génère un site **statique** dans `dist/`. Deux options pour servir le tout sous `/blog/` :

### Option A — nginx en frontal (recommandé)

Le nginx qui sert le front React route déjà `/api/*` vers le backend Symfony. Il faut ajouter une location pour `/blog/` :

```nginx
server {
    listen 80;
    server_name rodrigues-bnb.com;

    # Front React (existant)
    root /var/www/front/build;
    index index.html;

    location / {
        try_files $uri /index.html;
    }

    # Blog Astro
    location /blog/ {
        alias /var/www/blog/dist/;
        try_files $uri $uri/index.html =404;
    }

    # API Symfony (existant)
    location /api/ {
        proxy_pass http://api-php:8080;
        # …
    }
}
```

Pipeline de déploiement :

```bash
cd blog && npm ci && npm run build
rsync -avz --delete dist/ user@host:/var/www/blog/dist/
```

### Option B — service Astro avec adapter Node

Si vous voulez du SSR (pas nécessaire pour un blog statique), ajoutez `@astrojs/node` et adaptez `astro.config.mjs` (`output: 'server'`, `adapter: node`). Côté nginx :

```nginx
location /blog/ {
    proxy_pass http://blog:4321/blog/;
    proxy_set_header Host $host;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
}
```

## SEO

- `base: '/blog'` dans `astro.config.mjs` garantit que tous les liens internes sont préfixés.
- `trailingSlash: 'always'` + `build.format: 'directory'` produisent des URL `/blog/<slug>/index.html` — idéal pour nginx en mode statique.
- Le `site` dans `astro.config.mjs` doit pointer vers le domaine de prod pour le sitemap et les URL canoniques.
