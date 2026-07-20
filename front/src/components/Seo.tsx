import { useEffect } from 'react';
import { useLocation, matchPath } from 'react-router-dom';
import { useTranslation } from 'react-i18next';

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
  ['/accommodations/:id', { title: 'accommodation', description: 'accommodation', indexable: true }],
  ['/accommodations', { title: 'accommodations', description: 'accommodations', indexable: true }],
  ['/reservation-confirmed', { title: 'reservationConfirmed' }],
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

const Seo = () => {
  const { pathname } = useLocation();
  const { t, i18n } = useTranslation();

  useEffect(() => {
    document.documentElement.lang = i18n.language.startsWith('fr') ? 'fr' : 'en';

    const brand = t('meta.brand');
    const match = ROUTE_META.find(([pattern]) => matchPath(pattern, pathname));
    const meta = match ? match[1] : NOT_FOUND_META;
    const title =
      pathname === '/'
        ? `${brand} — ${t('meta.titles.home')}`
        : `${t(`meta.titles.${meta.title}`)} · ${brand}`;
    const description = t(`meta.descriptions.${meta.description ?? 'default'}`);
    const canonical = meta.indexable ? `${window.location.origin}${pathname}` : null;

    document.title = title;
    setMeta('name', 'description', description);
    setMeta('property', 'og:title', title);
    setMeta('property', 'og:description', description);
    setCanonical(canonical);
    if (canonical) {
      setMeta('property', 'og:url', canonical);
      removeMeta('name', 'robots');
    } else {
      removeMeta('property', 'og:url');
      setMeta('name', 'robots', 'noindex');
    }
  }, [pathname, t, i18n.language]);

  return null;
};

export default Seo;
