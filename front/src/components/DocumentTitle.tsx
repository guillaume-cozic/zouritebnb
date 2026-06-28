import { useEffect } from 'react';
import { useLocation, matchPath } from 'react-router-dom';
import { useTranslation } from 'react-i18next';

/**
 * Sets `document.title` per route so every page has a distinct, descriptive
 * title (SEO, browser history, screen readers). Patterns are matched in order,
 * most specific first; the first match wins. Renders nothing.
 */
const ROUTE_TITLES: ReadonlyArray<readonly [string, string]> = [
  ['/accommodations/:id/book', 'book'],
  ['/accommodations/:id/edit', 'editAccommodation'],
  ['/accommodations/:id/photos', 'photos'],
  ['/accommodations/:id/calendar', 'calendar'],
  ['/accommodations/:id', 'accommodation'],
  ['/accommodations', 'accommodations'],
  ['/reservation-confirmed', 'reservationConfirmed'],
  ['/solidarity-projects/:id', 'solidarityProject'],
  ['/solidarity-projects', 'solidarityProjects'],
  ['/cgu', 'termsOfUse'],
  ['/cgv', 'termsOfSale'],
  ['/login', 'login'],
  ['/register', 'register'],
  ['/create', 'create'],
  ['/admin/accommodations', 'manageAccommodations'],
  ['/admin/calendar', 'calendar'],
  ['/admin/reservations', 'reservations'],
  ['/admin/team', 'team'],
  ['/admin/conversations/:id', 'conversations'],
  ['/admin/conversations', 'conversations'],
  ['/admin', 'adminHome'],
  ['/account/conversations/:id', 'conversations'],
  ['/account/conversations', 'conversations'],
  ['/account/settings', 'settings'],
  ['/account/verification', 'verification'],
  ['/account', 'account'],
];

const DocumentTitle = () => {
  const { pathname } = useLocation();
  const { t } = useTranslation();

  useEffect(() => {
    const brand = t('meta.brand');
    if (pathname === '/') {
      document.title = `${brand} — ${t('meta.titles.home')}`;
      return;
    }
    const match = ROUTE_TITLES.find(([pattern]) => matchPath(pattern, pathname));
    document.title = match ? `${t(`meta.titles.${match[1]}`)} · ${brand}` : brand;
  }, [pathname, t]);

  return null;
};

export default DocumentTitle;
