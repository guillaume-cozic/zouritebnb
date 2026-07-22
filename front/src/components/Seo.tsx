import { useEffect } from 'react';
import { useLocation, matchPath } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAppSelector } from '../store/hooks';
import { selectCurrentAccommodation } from '../features/accommodation/AccommodationSelectors';
import { accommodationIdFromSlug, accommodationPath } from '../features/accommodation/accommodationUrl';
import { Accommodation } from '../features/accommodation/AccommodationTypes';

const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:8080';
const DEFAULT_OG_IMAGE = 'https://www.zouritebnb.com/logo512.png';

/**
 * Per-route <head> management: title, meta description, robots, canonical and
 * Open Graph tags (SEO, browser history, screen readers, link previews).
 * Public pages are indexable and carry their own description; everything else
 * (auth, backoffice, account) is noindex. Patterns are matched in order, most
 * specific first; the first match wins. No match at all means the URL fell
 * through to the catch-all 404 route. Renders nothing.
 */
interface RouteMeta {
  /** i18n key under meta.titles. */
  title: string;
  /** i18n key under meta.descriptions — only public, indexable pages have one. */
  description?: string;
  /** Public page: indexable by search engines, gets a canonical URL. */
  indexable?: boolean;
}

const ROUTE_META: ReadonlyArray<readonly [string, RouteMeta]> = [
  ['/', { title: 'home', description: 'home', indexable: true }],
  ['/accommodations/:id/book', { title: 'book' }],
  ['/accommodations/:id/edit', { title: 'editAccommodation' }],
  ['/accommodations/:id/photos', { title: 'photos' }],
  ['/hebergements/:slug', { title: 'accommodation', description: 'accommodation', indexable: true }],
  ['/accommodations', { title: 'accommodations', description: 'accommodations', indexable: true }],
  ['/reservation-confirmed', { title: 'reservationConfirmed' }],
  ['/activites', { title: 'activities', description: 'activities', indexable: true }],
  ['/solidarity-projects/:id', { title: 'solidarityProject', description: 'solidarityProject', indexable: true }],
  ['/solidarity-projects', { title: 'solidarityProjects', description: 'solidarityProjects', indexable: true }],
  ['/cgu', { title: 'termsOfUse', description: 'termsOfUse', indexable: true }],
  ['/cgv', { title: 'termsOfSale', description: 'termsOfSale', indexable: true }],
  ['/mentions-legales', { title: 'legalNotice', description: 'legalNotice', indexable: true }],
  ['/confidentialite', { title: 'privacyPolicy', description: 'privacyPolicy', indexable: true }],
  ['/wishlist', { title: 'wishlist' }],
  ['/login', { title: 'login' }],
  ['/register', { title: 'register' }],
  ['/forgot-password', { title: 'forgotPassword' }],
  ['/reset-password', { title: 'resetPassword' }],
  ['/verify-email', { title: 'verifyEmail' }],
  ['/create', { title: 'create' }],
  ['/admin/accommodations/:id/calendar', { title: 'calendar' }],
  ['/admin/accommodations', { title: 'manageAccommodations' }],
  ['/admin/calendar', { title: 'calendar' }],
  ['/admin/reservations', { title: 'reservations' }],
  ['/admin/revenue', { title: 'revenue' }],
  ['/admin/team', { title: 'team' }],
  ['/admin/conversations/:id', { title: 'conversations' }],
  ['/admin/conversations', { title: 'conversations' }],
  ['/admin', { title: 'adminHome' }],
  ['/account/conversations/:id', { title: 'conversations' }],
  ['/account/conversations', { title: 'conversations' }],
  ['/account/settings', { title: 'settings' }],
  ['/account/verification', { title: 'verification' }],
  ['/account', { title: 'account' }],
  ['/conversations/:id', { title: 'conversations' }],
  ['/conversations', { title: 'conversations' }],
];

const NOT_FOUND_META: RouteMeta = { title: 'notFound' };

const setMeta = (attr: 'name' | 'property', key: string, content: string): void => {
  let el = document.head.querySelector<HTMLMetaElement>(`meta[${attr}="${key}"]`);
  if (!el) {
    el = document.createElement('meta');
    el.setAttribute(attr, key);
    document.head.appendChild(el);
  }
  el.content = content;
};

const removeMeta = (attr: 'name' | 'property', key: string): void => {
  document.head.querySelector(`meta[${attr}="${key}"]`)?.remove();
};

const setCanonical = (href: string | null): void => {
  let el = document.head.querySelector<HTMLLinkElement>('link[rel="canonical"]');
  if (!href) {
    el?.remove();
    return;
  }
  if (!el) {
    el = document.createElement('link');
    el.rel = 'canonical';
    document.head.appendChild(el);
  }
  el.href = href;
};

const setJsonLd = (data: object | null): void => {
  let el = document.head.querySelector<HTMLScriptElement>('script[data-seo-jsonld]');
  if (!data) {
    el?.remove();
    return;
  }
  if (!el) {
    el = document.createElement('script');
    el.type = 'application/ld+json';
    el.setAttribute('data-seo-jsonld', '');
    document.head.appendChild(el);
  }
  el.textContent = JSON.stringify(data);
};

const absoluteUrl = (url: string): string => (url.startsWith('http') ? url : `${API_BASE}${url}`);

const excerpt = (text: string | undefined, max = 155): string =>
  (text ?? '').replace(/\s+/g, ' ').trim().slice(0, max);

/** Données structurées schema.org de l'annonce (résultats enrichis Google). */
const accommodationJsonLd = (a: Accommodation, canonical: string): object => ({
  '@context': 'https://schema.org',
  '@type': 'VacationRental',
  name: a.title,
  description: excerpt(a.description, 300),
  url: canonical,
  image: (a.photos ?? []).slice(0, 8).map((p) => absoluteUrl(p.url)),
  address: {
    '@type': 'PostalAddress',
    ...(a.city ? { addressLocality: a.city } : {}),
    addressCountry: 'MU',
  },
  ...(a.latitude != null && a.longitude != null
    ? { geo: { '@type': 'GeoCoordinates', latitude: a.latitude, longitude: a.longitude } }
    : {}),
  ...(a.maxGuests != null
    ? { occupancy: { '@type': 'QuantitativeValue', value: a.maxGuests } }
    : {}),
  ...(a.price != null
    ? { offers: { '@type': 'Offer', price: a.price, priceCurrency: 'EUR' } }
    : {}),
  ...(a.averageRating != null && (a.reviewCount ?? 0) > 0
    ? {
        aggregateRating: {
          '@type': 'AggregateRating',
          ratingValue: a.averageRating,
          reviewCount: a.reviewCount,
        },
      }
    : {}),
});

const Seo = () => {
  const { pathname } = useLocation();
  const { t, i18n } = useTranslation();
  const currentAccommodation = useAppSelector(selectCurrentAccommodation);

  useEffect(() => {
    document.documentElement.lang = i18n.language.startsWith('fr') ? 'fr' : 'en';

    const brand = t('meta.brand');
    const match = ROUTE_META.find(([pattern]) => matchPath(pattern, pathname));
    const meta = match ? match[1] : NOT_FOUND_META;
    let title =
      pathname === '/'
        ? `${brand} — ${t('meta.titles.home')}`
        : `${t(`meta.titles.${meta.title}`)} · ${brand}`;
    let description = t(`meta.descriptions.${meta.description ?? 'default'}`);
    let canonical = meta.indexable ? `${window.location.origin}${pathname}` : null;
    let ogImage = DEFAULT_OG_IMAGE;

    // Page de détail d'une annonce : metas spécifiques (titre, extrait de la
    // description, photo, canonical à slug) + données structurées, dès que
    // l'annonce de la route est chargée en store.
    const detailMatch = matchPath('/hebergements/:slug', pathname);
    const routeAccommodationId = detailMatch
      ? accommodationIdFromSlug(detailMatch.params.slug)
      : null;
    const accommodation =
      routeAccommodationId && currentAccommodation?.id?.toLowerCase() === routeAccommodationId
        ? currentAccommodation
        : null;

    if (accommodation?.title) {
      title = `${accommodation.title} — ${accommodation.city ?? 'Rodrigues'} · ${brand}`;
      description = excerpt(accommodation.description) || description;
      canonical = `${window.location.origin}${accommodationPath(accommodation)}`;
      const photo = accommodation.photos?.[0]?.url;
      if (photo) ogImage = absoluteUrl(photo);
      setJsonLd(accommodationJsonLd(accommodation, canonical));
    } else {
      setJsonLd(null);
    }

    document.title = title;
    setMeta('name', 'description', description);
    setMeta('property', 'og:title', title);
    setMeta('property', 'og:description', description);
    setMeta('property', 'og:image', ogImage);
    setCanonical(canonical);
    if (canonical) {
      setMeta('property', 'og:url', canonical);
      removeMeta('name', 'robots');
    } else {
      removeMeta('property', 'og:url');
      setMeta('name', 'robots', 'noindex');
    }
  }, [pathname, t, i18n.language, currentAccommodation]);

  return null;
};

export default Seo;
