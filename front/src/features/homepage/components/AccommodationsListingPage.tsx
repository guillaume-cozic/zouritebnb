import React, { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { fetchPublishedAccommodations, nextPageRequested, setFilters } from '../HomepageSlice';
import {
  selectFilteredAccommodations,
  selectHomepageStatus,
  selectHomepageError,
  selectHomepageFilters,
  selectHomepageHasMore,
  selectHomepageLoadingMore,
} from '../HomepageSelectors';
import AccommodationCard from './AccommodationCard';
import AccommodationsMap from './AccommodationsMap';
import Footer from '../../../components/Footer';
import LocalitySuggestions from './LocalitySuggestions';

const QUICK_AMENITIES = ['wifi', 'air_conditioning', 'private_pool', 'private_parking', 'sea_view', 'pets_allowed'] as const;

interface ChipProps {
  label: string;
  onRemove: () => void;
}

const Chip: React.FC<ChipProps> = ({ label, onRemove }) => (
  <span className="inline-flex items-center gap-1.5 h-8 pl-3 pr-1 rounded-full bg-primary-50 border border-primary-200 text-sm font-medium text-primary-800">
    {label}
    <button
      type="button"
      onClick={onRemove}
      aria-label="remove"
      className="inline-flex items-center justify-center w-6 h-6 rounded-full text-primary-700 hover:bg-primary-100 transition-colors"
    >
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
        <path d="M18 6 6 18" />
        <path d="m6 6 12 12" />
      </svg>
    </button>
  </span>
);

const AccommodationsListingPage: React.FC = () => {
  const { t } = useTranslation();
  const dispatch = useAppDispatch();
  const accommodations = useAppSelector(selectFilteredAccommodations);
  const status = useAppSelector(selectHomepageStatus);
  const error = useAppSelector(selectHomepageError);
  const filters = useAppSelector(selectHomepageFilters);
  const hasMore = useAppSelector(selectHomepageHasMore);
  const loadingMore = useAppSelector(selectHomepageLoadingMore);

  const [advancedOpen, setAdvancedOpen] = useState(false);
  const [cityOpen, setCityOpen] = useState(false);
  // The map is shown by default on the search page.
  const [mapOpen, setMapOpen] = useState(true);
  const [hoveredId, setHoveredId] = useState<string | null>(null);
  const sentinelRef = useRef<HTMLDivElement | null>(null);

  const amenitiesKey = filters.amenities.join(',');
  useEffect(() => {
    dispatch(fetchPublishedAccommodations({
      checkIn: filters.checkIn,
      checkOut: filters.checkOut,
      city: filters.city,
      guests: filters.guests,
      priceMin: filters.priceMin,
      priceMax: filters.priceMax,
      amenities: filters.amenities,
      page: 1,
    }));
  }, [dispatch, filters.checkIn, filters.checkOut, filters.city, filters.guests, filters.priceMin, filters.priceMax, amenitiesKey]);

  // Infinite scroll: ask for the next page when the sentinel enters the viewport.
  // The component only declares the intent; the listener decides what to fetch.
  useEffect(() => {
    const node = sentinelRef.current;
    if (!node || !hasMore) return;
    const observer = new IntersectionObserver(
      (entries) => {
        if (entries[0]?.isIntersecting) {
          dispatch(nextPageRequested());
        }
      },
      { rootMargin: '400px' }
    );
    observer.observe(node);
    return () => observer.disconnect();
  }, [dispatch, hasMore, accommodations.length]);

  const toggleAmenity = (code: string) => {
    const next = filters.amenities.includes(code)
      ? filters.amenities.filter((c) => c !== code)
      : [...filters.amenities, code];
    dispatch(setFilters({ amenities: next }));
  };

  const activeCount =
    (filters.city ? 1 : 0) +
    (filters.guests !== null && filters.guests > 1 ? 1 : 0) +
    filters.amenities.length +
    (filters.priceMin !== null ? 1 : 0) +
    (filters.priceMax !== null ? 1 : 0);

  const advancedCount =
    filters.amenities.length +
    (filters.priceMin !== null ? 1 : 0) +
    (filters.priceMax !== null ? 1 : 0);

  const resetAll = () => {
    dispatch(setFilters({ city: '', guests: 1, amenities: [], priceMin: null, priceMax: null }));
  };

  const priceChipLabel = (() => {
    if (filters.priceMin !== null && filters.priceMax !== null) {
      return `${filters.priceMin}€ – ${filters.priceMax}€`;
    }
    if (filters.priceMin !== null) return `≥ ${filters.priceMin}€`;
    if (filters.priceMax !== null) return `≤ ${filters.priceMax}€`;
    return null;
  })();

  return (
    <div className="min-h-[calc(100vh-4rem)] flex flex-col">
    <section className={`flex-1 w-full mx-auto px-4 sm:px-6 lg:px-8 py-10 ${mapOpen ? 'max-w-none' : 'max-w-7xl'}`}>
      <div className="mb-6">
        <h1 className="text-3xl font-bold text-gray-900 tracking-tight">{t('listing.title')}</h1>
        <p className="text-gray-500 mt-1">{t('listing.subtitle')}</p>
      </div>

      <div className="mb-6">
        <div className="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow overflow-hidden">
          <div className="grid grid-cols-1 md:grid-cols-[1.4fr_1fr_auto] divide-y md:divide-y-0 md:divide-x divide-gray-100">
            <label className="group relative flex items-center gap-3 px-5 py-3 cursor-text">
              <svg className="text-gray-400 group-focus-within:text-primary-500 transition-colors flex-shrink-0" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0" />
                <circle cx="12" cy="10" r="3" />
              </svg>
              <div className="flex-1 min-w-0">
                <p className="text-[11px] font-semibold uppercase tracking-wider text-gray-500">
                  {t('hero.city')}
                </p>
                <input
                  type="text"
                  placeholder={t('hero.searchPlaceholder')}
                  value={filters.city}
                  onChange={(e) => {
                    dispatch(setFilters({ city: e.target.value }));
                    setCityOpen(true);
                  }}
                  onFocus={() => setCityOpen(true)}
                  onBlur={() => setCityOpen(false)}
                  autoComplete="off"
                  className="w-full text-sm text-gray-900 placeholder:text-gray-400 bg-transparent border-0 p-0 focus:outline-none focus:ring-0"
                />
              </div>
              <LocalitySuggestions
                value={filters.city}
                open={cityOpen}
                onSelect={(city) => {
                  dispatch(setFilters({ city }));
                  setCityOpen(false);
                }}
              />
            </label>

            <div className="flex items-center gap-3 px-5 py-3">
              <svg className="text-gray-400 flex-shrink-0" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                <circle cx="9" cy="7" r="4" />
                <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
              </svg>
              <p className="text-[11px] font-semibold uppercase tracking-wider text-gray-500">
                {t('hero.guests')}
              </p>
              <div className="ml-auto flex items-center gap-2">
                <button
                  type="button"
                  onClick={() => {
                    const current = filters.guests ?? 1;
                    dispatch(setFilters({ guests: Math.max(1, current - 1) }));
                  }}
                  disabled={!filters.guests || filters.guests <= 1}
                  aria-label="decrement guests"
                  className="inline-flex items-center justify-center w-8 h-8 rounded-full border border-gray-300 text-gray-700 hover:border-primary-400 hover:bg-primary-50 hover:text-primary-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                >
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M5 12h14" />
                  </svg>
                </button>
                <span className="min-w-[28px] text-center text-sm font-semibold text-gray-900 tabular-nums">
                  {filters.guests ?? 1}
                </span>
                <button
                  type="button"
                  onClick={() => {
                    const current = filters.guests ?? 0;
                    dispatch(setFilters({ guests: current + 1 }));
                  }}
                  aria-label="increment guests"
                  className="inline-flex items-center justify-center w-8 h-8 rounded-full border border-gray-300 text-gray-700 hover:border-primary-400 hover:bg-primary-50 hover:text-primary-700 transition-colors"
                >
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M12 5v14" />
                    <path d="M5 12h14" />
                  </svg>
                </button>
              </div>
            </div>

            <div className="flex items-stretch px-3 py-2 md:py-2.5">
              <button
                type="button"
                onClick={() => setAdvancedOpen((v) => !v)}
                aria-expanded={advancedOpen}
                className={`inline-flex items-center gap-2 h-11 px-4 rounded-xl text-sm font-semibold transition-colors ${
                  advancedOpen || advancedCount > 0
                    ? 'bg-primary-50 text-primary-700 hover:bg-primary-100'
                    : 'text-gray-700 hover:bg-gray-50'
                }`}
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                  <line x1="21" x2="14" y1="4" y2="4" />
                  <line x1="10" x2="3" y1="4" y2="4" />
                  <line x1="21" x2="12" y1="12" y2="12" />
                  <line x1="8" x2="3" y1="12" y2="12" />
                  <line x1="21" x2="16" y1="20" y2="20" />
                  <line x1="12" x2="3" y1="20" y2="20" />
                  <line x1="14" x2="14" y1="2" y2="6" />
                  <line x1="8" x2="8" y1="10" y2="14" />
                  <line x1="16" x2="16" y1="18" y2="22" />
                </svg>
                <span className="hidden sm:inline">{t('listing.advanced.toggle')}</span>
                {advancedCount > 0 && (
                  <span className="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-[11px] font-bold bg-primary-600 text-white">
                    {advancedCount}
                  </span>
                )}
              </button>
            </div>
          </div>

          {advancedOpen && (
            <div className="border-t border-gray-100 bg-gradient-to-b from-primary-50/40 to-white px-5 sm:px-6 py-5 space-y-5">
              <div>
                <p className="text-xs font-semibold uppercase tracking-wider text-gray-600 mb-3">
                  {t('listing.advanced.priceRange')}
                </p>
                <div className="flex items-center gap-3 max-w-md">
                  <div className="flex-1 relative">
                    <span className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">€</span>
                    <input
                      type="number"
                      min={0}
                      step={10}
                      value={filters.priceMin ?? ''}
                      onChange={(e) =>
                        dispatch(setFilters({ priceMin: e.target.value ? Number(e.target.value) : null }))
                      }
                      placeholder={t('listing.advanced.priceMin') as string}
                      className="w-full h-11 rounded-xl border border-gray-200 bg-white pl-7 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 transition-all"
                    />
                  </div>
                  <span className="text-gray-300 font-medium">—</span>
                  <div className="flex-1 relative">
                    <span className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">€</span>
                    <input
                      type="number"
                      min={0}
                      step={10}
                      value={filters.priceMax ?? ''}
                      onChange={(e) =>
                        dispatch(setFilters({ priceMax: e.target.value ? Number(e.target.value) : null }))
                      }
                      placeholder={t('listing.advanced.priceMax') as string}
                      className="w-full h-11 rounded-xl border border-gray-200 bg-white pl-7 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 transition-all"
                    />
                  </div>
                </div>
              </div>

              <div>
                <p className="text-xs font-semibold uppercase tracking-wider text-gray-600 mb-3">
                  {t('hero.amenities')}
                </p>
                <div className="flex flex-wrap gap-2">
                  {QUICK_AMENITIES.map((code) => {
                    const active = filters.amenities.includes(code);
                    return (
                      <button
                        key={code}
                        type="button"
                        onClick={() => toggleAmenity(code)}
                        className={`px-3.5 py-2 rounded-full text-xs font-medium border transition-all ${
                          active
                            ? 'bg-primary-600 text-white border-primary-600 shadow-sm shadow-primary-200'
                            : 'bg-white text-gray-700 border-gray-200 hover:border-primary-400 hover:bg-primary-50/40'
                        }`}
                      >
                        {t(`amenities.${code}`, code)}
                      </button>
                    );
                  })}
                </div>
              </div>
            </div>
          )}
        </div>

        {activeCount > 0 && (
          <div className="mt-3 flex flex-wrap items-center gap-2">
            <span className="text-xs font-semibold text-gray-500 uppercase tracking-wider mr-1">
              {t('listing.activeFilters')}
            </span>
            {filters.city && (
              <Chip label={filters.city} onRemove={() => dispatch(setFilters({ city: '' }))} />
            )}
            {filters.guests !== null && filters.guests > 1 && (
              <Chip
                label={`${filters.guests} ${t('hero.guests').toLowerCase()}`}
                onRemove={() => dispatch(setFilters({ guests: 1 }))}
              />
            )}
            {priceChipLabel && (
              <Chip
                label={priceChipLabel}
                onRemove={() => dispatch(setFilters({ priceMin: null, priceMax: null }))}
              />
            )}
            {filters.amenities.map((code) => (
              <Chip
                key={code}
                label={t(`amenities.${code}`, code)}
                onRemove={() => toggleAmenity(code)}
              />
            ))}
            <button
              type="button"
              onClick={resetAll}
              className="ml-1 text-xs font-semibold text-gray-500 hover:text-primary-700 underline underline-offset-2 transition-colors"
            >
              {t('listing.reset')}
            </button>
          </div>
        )}
      </div>

      {status === 'loading' && (
        <div className="text-center py-16 text-gray-500">{t('homepage.loading')}</div>
      )}
      {status === 'failed' && (
        <div className="text-center py-16 text-red-500">{error}</div>
      )}
      {status === 'succeeded' && accommodations.length === 0 && (
        <div className="text-center py-16 text-gray-500">{t('homepage.noResults')}</div>
      )}
      {accommodations.length > 0 && (
        <>
          <div className="flex items-center justify-between mb-4 gap-3">
            <p className="text-sm text-gray-500">
              {t('listing.resultCount', { count: accommodations.length })}
            </p>
            <button
              type="button"
              onClick={() => setMapOpen((v) => !v)}
              aria-pressed={mapOpen}
              className={`inline-flex items-center gap-2 h-10 px-4 rounded-xl text-sm font-semibold border transition-colors ${
                mapOpen
                  ? 'bg-primary-50 text-primary-700 border-primary-200 hover:bg-primary-100'
                  : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'
              }`}
            >
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M9 18l-6 3V6l6-3m0 15l6 3m-6-3V3m6 18l6-3V3l-6 3m0 15V6" />
              </svg>
              {mapOpen ? t('listing.hideMap') : t('listing.showMap')}
            </button>
          </div>

          {mapOpen ? (
            <div className="flex flex-col lg:flex-row gap-6 items-start">
              <div className="flex-1 min-w-0 w-full lg:w-1/2">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                  {accommodations.map((item) => (
                    <AccommodationCard
                      key={item.id}
                      accommodation={item}
                      onHoverChange={(hovered) => setHoveredId(hovered ? item.id : null)}
                    />
                  ))}
                </div>
              </div>
              <div className="w-full lg:w-1/2 lg:sticky lg:top-24 flex-shrink-0">
                <AccommodationsMap
                  accommodations={accommodations}
                  height="calc(100vh - 8rem)"
                  highlightedId={hoveredId}
                />
              </div>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
              {accommodations.map((item) => (
                <AccommodationCard key={item.id} accommodation={item} />
              ))}
            </div>
          )}

          {/* Sentinel observed by the IntersectionObserver to trigger the next page. */}
          {hasMore && <div ref={sentinelRef} aria-hidden="true" className="h-px w-full" />}

          {loadingMore && (
            <div className="flex items-center justify-center py-10 text-gray-500">
              <svg
                className="animate-spin h-5 w-5 text-primary-600"
                viewBox="0 0 24 24"
                fill="none"
                aria-hidden="true"
              >
                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z" />
              </svg>
              <span className="ml-3 text-sm">{t('listing.loadingMore')}</span>
            </div>
          )}

          {!hasMore && !loadingMore && accommodations.length > 0 && (
            <p className="text-center py-10 text-sm text-gray-400">{t('listing.endOfResults')}</p>
          )}
        </>
      )}
    </section>
    <Footer />
    </div>
  );
};

export default AccommodationsListingPage;
