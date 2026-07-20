# syntax=docker/dockerfile:1.7

# Image "web" auto-suffisante : le build du front (React/Vite) et du blog
# (Astro, base /blog) est embarqué dans l'image, servi par nginx.
# Contexte de build attendu : la racine du monorepo.

# ---------- Stage 1: front build ----------
FROM node:24-alpine AS front_build

WORKDIR /app

# L'URL de l'API est cuite dans le bundle au build (import.meta.env).
ARG VITE_API_URL
ENV VITE_API_URL=${VITE_API_URL}

COPY front/package.json front/package-lock.json ./
RUN npm ci

COPY front/ ./
RUN npm run build

# ---------- Stage 2: blog build ----------
FROM node:24-alpine AS blog_build

WORKDIR /app

ARG PUBLIC_FRONT_URL
ENV PUBLIC_FRONT_URL=${PUBLIC_FRONT_URL}

# Les pages "où dormir" interrogent l'API publique au build (repli silencieux
# si elle est injoignable : les pages sortent sans cartes d'hébergements).
ARG PUBLIC_API_URL
ENV PUBLIC_API_URL=${PUBLIC_API_URL}

COPY blog/package.json blog/package-lock.json ./
RUN npm ci

COPY blog/ ./
RUN npm run build

# ---------- Stage 3: runtime ----------
FROM nginx:1.27-alpine

COPY docker/web/nginx.conf /etc/nginx/conf.d/default.conf

COPY --from=front_build /app/dist /usr/share/nginx/html
# Astro est configuré avec base '/blog' : ses URLs internes pointent déjà
# vers /blog/..., il suffit de poser le build dans ce sous-dossier.
COPY --from=blog_build /app/dist /usr/share/nginx/html/blog

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
