import React, { useState, useEffect, useCallback } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useFeaturedSolidarityProject } from '../../solidarityProject/useFeaturedSolidarityProject';
import DatePicker, { registerLocale } from 'react-datepicker';
import { fr } from 'date-fns/locale/fr';
import { enGB } from 'date-fns/locale/en-GB';
import 'react-datepicker/dist/react-datepicker.css';
import '../../../styles/datepicker-overrides.css';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { setFilters } from '../HomepageSlice';
import { selectHomepageFilters } from '../HomepageSelectors';
import LocalitySuggestions from './LocalitySuggestions';

registerLocale('fr', fr);
registerLocale('en', enGB);

const toDate = (s: string): Date | null => (s ? new Date(s) : null);
const toStr = (d: Date | null): string => {
  if (!d) return '';
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
};

const SLIDES = [
  'https://images.unsplash.com/photo-1559128010-7c1ad6e1b6a5?auto=format&fit=crop&q=80&w=2000',
  'https://images.unsplash.com/photo-1506953823976-52e1fdc0149a?auto=format&fit=crop&q=80&w=2000',
  'https://images.unsplash.com/photo-1505881502353-a1986add3762?auto=format&fit=crop&q=80&w=2000',
  'https://images.unsplash.com/photo-1537956965359-7573183d1f57?auto=format&fit=crop&q=80&w=2000',
];

const HeroSection: React.FC = () => {
  const { t, i18n } = useTranslation();
  const dispatch = useAppDispatch();
  const navigate = useNavigate();
  const filters = useAppSelector(selectHomepageFilters);

  const [current, setCurrent] = useState(0);
  const [advancedOpen, setAdvancedOpen] = useState(false);
  const [cityOpen, setCityOpen] = useState(false);
  const {
    project: solidarityProject,
    count: solidarityCount,
    next: nextSolidarity,
    prev: prevSolidarity,
  } = useFeaturedSolidarityProject();

  const advancedCount =
    filters.amenities.length +
    (filters.priceMin !== null ? 1 : 0) +
    (filters.priceMax !== null ? 1 : 0);

  const next = useCallback(() => {
    setCurrent((prev) => (prev + 1) % SLIDES.length);
  }, []);

  useEffect(() => {
    const timer = setInterval(next, 5000);
    return () => clearInterval(timer);
  }, [next]);

  const startDate = toDate(filters.checkIn);
  const endDate = toDate(filters.checkOut);

  const handleDateChange = (dates: [Date | null, Date | null]) => {
    const [start, end] = dates;
    // Cas: une date de début existe, pas de fin, et l'utilisateur clique avant la date de début.
    // react-datepicker réinitialise avec [clique, null] — on interprète plutôt comme "fin avant début" et on swap.
    if (startDate && !endDate && start && !end && start < startDate) {
      dispatch(setFilters({ checkIn: toStr(start), checkOut: toStr(startDate) }));
      return;
    }
    dispatch(setFilters({ checkIn: toStr(start), checkOut: toStr(end) }));
  };

  const dateLabel = startDate
    ? endDate
      ? `${startDate.toLocaleDateString(i18n.language)} – ${endDate.toLocaleDateString(i18n.language)}`
      : startDate.toLocaleDateString(i18n.language)
    : '';

  // Dès qu'un projet solidaire est mis en avant, le hero passe en « mode projet » :
  // image du projet (ou dégradé de repli), navigation entre projets, pas de diaporama.
  const projectMode = solidarityProject !== null;
  const heroImage = solidarityProject?.imageUrl ?? null;

  return (
    <div className="relative">
      {/* Slider */}
      <div className="relative h-[500px] w-full overflow-hidden">
        {heroImage ? (
          <img
            src={heroImage}
            alt={solidarityProject?.title ?? ''}
            // Image LCP découverte après le JS + l'appel API : priorité réseau
            // maximale dès qu'elle apparaît dans le DOM.
            fetchPriority="high"
            className="absolute inset-0 h-full w-full object-cover"
          />
        ) : projectMode ? (
          <div className="absolute inset-0 bg-gradient-to-br from-primary-700 to-primary-900" />
        ) : (
          SLIDES.map((src, i) => (
            <img
              key={src}
              src={src}
              alt=""
              className={`absolute inset-0 h-full w-full object-cover transition-opacity duration-1000 ease-in-out ${
                i === current ? 'opacity-100' : 'opacity-0'
              }`}
            />
          ))
        )}
        <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/40 to-black/20" />

        {/* Navigation entre projets solidaires */}
        {projectMode && solidarityCount > 1 && (
          <>
            <button
              type="button"
              onClick={prevSolidarity}
              aria-label={t('hero.solidarity.prev')}
              className="absolute left-4 top-1/2 -translate-y-1/2 z-10 inline-flex items-center justify-center h-11 w-11 rounded-full bg-white/15 backdrop-blur-md ring-1 ring-white/25 text-white hover:bg-white/30 transition-colors"
            >
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                <path d="m15 18-6-6 6-6" />
              </svg>
            </button>
            <button
              type="button"
              onClick={nextSolidarity}
              aria-label={t('hero.solidarity.next')}
              className="absolute right-4 top-1/2 -translate-y-1/2 z-10 inline-flex items-center justify-center h-11 w-11 rounded-full bg-white/15 backdrop-blur-md ring-1 ring-white/25 text-white hover:bg-white/30 transition-colors"
            >
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                <path d="m9 18 6-6-6-6" />
              </svg>
            </button>
          </>
        )}

        {/* Hero text */}
        <div className="relative h-full flex flex-col items-center justify-center px-4 pb-16">
          <h1 className="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-4 text-center tracking-tight">
            {t('hero.title')}
          </h1>
          <p className="text-lg md:text-xl text-white/80 max-w-2xl mx-auto text-center font-light">
            {t('hero.subtitle')}
          </p>

          {solidarityProject && (
            <Link
              to={`/solidarity-projects/${solidarityProject.id}`}
              className="group mt-7 inline-flex items-center gap-3 rounded-full bg-white/15 backdrop-blur-md ring-1 ring-white/25 py-2 pl-4 pr-2 hover:bg-white/25 transition-colors"
            >
              <span className="inline-flex items-center gap-1.5 text-xs font-semibold text-white/90 whitespace-nowrap">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                  <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                </svg>
                {t('hero.solidarity.label')}
              </span>
              <span className="text-sm font-semibold text-white truncate max-w-[40vw] sm:max-w-xs">
                {solidarityProject.title}
              </span>
              <span className="inline-flex items-center justify-center h-7 w-7 rounded-full bg-white text-primary-700 transition-transform group-hover:translate-x-0.5">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                  <path d="M5 12h14" />
                  <path d="m12 5 7 7-7 7" />
                </svg>
              </span>
            </Link>
          )}
        </div>

        {/* Dots */}
        {!projectMode && (
          <div className="absolute bottom-24 left-1/2 -translate-x-1/2 flex gap-2">
            {SLIDES.map((_, i) => (
              <button
                key={i}
                onClick={() => setCurrent(i)}
                className={`h-2 rounded-full transition-all duration-300 ${
                  i === current ? 'w-6 bg-white' : 'w-2 bg-white/50 hover:bg-white/70'
                }`}
              />
            ))}
          </div>
        )}
      </div>

      {/* Search form */}
      <div className="absolute bottom-0 left-0 right-0 transform translate-y-1/2 z-10">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="bg-white p-5 sm:p-6 rounded-2xl shadow-xl shadow-black/10 border border-gray-100">
            {/* Mots-clés */}
            <div className="space-y-1.5 mb-3">
              <label className="block text-xs font-semibold text-gray-500 uppercase tracking-wider">
                {t('listing.keyword.label')}
              </label>
              <div className="relative">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                  <circle cx="11" cy="11" r="8" />
                  <path d="m21 21-4.3-4.3" />
                </svg>
                <input
                  type="text"
                  placeholder={t('listing.keyword.placeholder')}
                  value={filters.q}
                  onChange={(e) => dispatch(setFilters({ q: e.target.value }))}
                  autoComplete="off"
                  className="w-full h-11 rounded-xl border border-gray-200 bg-gray-50 pl-10 pr-3 text-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 focus:bg-white transition-all"
                />
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
              {/* Lieu */}
              <div className="space-y-1.5">
                <label className="block text-xs font-semibold text-gray-500 uppercase tracking-wider">
                  {t('hero.city')}
                </label>
                <div className="relative">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                    <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0" />
                    <circle cx="12" cy="10" r="3" />
                  </svg>
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
                    className="w-full h-11 rounded-xl border border-gray-200 bg-gray-50 pl-10 pr-3 text-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 focus:bg-white transition-all"
                  />
                  <LocalitySuggestions
                    value={filters.city}
                    open={cityOpen}
                    onSelect={(city) => {
                      dispatch(setFilters({ city }));
                      setCityOpen(false);
                    }}
                  />
                </div>
              </div>

              {/* Dates */}
              <div className="space-y-1.5">
                <label className="block text-xs font-semibold text-gray-500 uppercase tracking-wider">
                  {t('hero.dates')}
                </label>
                <div className="relative">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 z-10 pointer-events-none">
                    <path d="M8 2v4" />
                    <path d="M16 2v4" />
                    <rect width="18" height="18" x="3" y="4" rx="2" />
                    <path d="M3 10h18" />
                  </svg>
                  <DatePicker
                    selectsRange
                    startDate={startDate}
                    endDate={endDate}
                    onChange={handleDateChange}
                    locale={i18n.language}
                    minDate={new Date()}
                    monthsShown={2}
                    placeholderText={t('hero.selectDates')}
                    value={dateLabel}
                    dateFormat="dd/MM/yyyy"
                    className="w-full h-11 rounded-xl border border-gray-200 bg-gray-50 pl-10 pr-3 text-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 focus:bg-white transition-all"
                    isClearable
                  />
                </div>
              </div>

              {/* Voyageurs */}
              <div className="space-y-1.5">
                <label className="block text-xs font-semibold text-gray-500 uppercase tracking-wider">
                  {t('hero.guests')}
                </label>
                <div className="h-11 flex items-center justify-between gap-2 rounded-xl border border-gray-200 bg-gray-50 pl-3 pr-2">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-gray-400 flex-shrink-0">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                  </svg>
                  <div className="flex items-center gap-2">
                    <button
                      type="button"
                      onClick={() => {
                        const current = filters.guests ?? 1;
                        dispatch(setFilters({ guests: Math.max(1, current - 1) }));
                      }}
                      disabled={!filters.guests || filters.guests <= 1}
                      aria-label="decrement guests"
                      className="inline-flex items-center justify-center w-8 h-8 rounded-full border border-gray-300 bg-white text-gray-700 hover:border-primary-400 hover:bg-primary-50 hover:text-primary-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                    >
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M5 12h14" />
                      </svg>
                    </button>
                    <span className="min-w-[24px] text-center text-sm font-semibold text-gray-900 tabular-nums">
                      {filters.guests ?? 1}
                    </span>
                    <button
                      type="button"
                      onClick={() => {
                        const current = filters.guests ?? 0;
                        dispatch(setFilters({ guests: current + 1 }));
                      }}
                      aria-label="increment guests"
                      className="inline-flex items-center justify-center w-8 h-8 rounded-full border border-gray-300 bg-white text-gray-700 hover:border-primary-400 hover:bg-primary-50 hover:text-primary-700 transition-colors"
                    >
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M12 5v14" />
                        <path d="M5 12h14" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            {/* Advanced filters */}
            <div className="mb-3">
              <button
                type="button"
                onClick={() => setAdvancedOpen((v) => !v)}
                aria-expanded={advancedOpen}
                className="inline-flex items-center gap-2 text-sm font-semibold text-primary-700 hover:text-primary-800 transition-colors"
              >
                <svg
                  width="14"
                  height="14"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  strokeWidth="2.5"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  className={`transition-transform ${advancedOpen ? 'rotate-180' : ''}`}
                >
                  <polyline points="6 9 12 15 18 9" />
                </svg>
                {t('listing.advanced.toggle')}
                {advancedCount > 0 && (
                  <span className="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-[11px] font-bold bg-primary-100 text-primary-700">
                    {advancedCount}
                  </span>
                )}
              </button>

              {advancedOpen && (
                <div className="mt-4 space-y-4">
                  <div>
                    <label className="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                      {t('listing.advanced.priceRange')}
                    </label>
                    <div className="flex items-center gap-3">
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
                          className="w-full h-11 rounded-xl border border-gray-200 bg-gray-50 pl-7 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 focus:bg-white transition-all"
                        />
                      </div>
                      <span className="text-gray-300">—</span>
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
                          className="w-full h-11 rounded-xl border border-gray-200 bg-gray-50 pl-7 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 focus:bg-white transition-all"
                        />
                      </div>
                    </div>
                  </div>

                  <div>
                    <label className="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                      {t('hero.amenities')}
                    </label>
                    <div className="flex flex-wrap gap-2">
                      {(['wifi', 'air_conditioning', 'private_pool', 'private_parking', 'sea_view', 'pets_allowed'] as const).map((code) => {
                        const active = filters.amenities.includes(code);
                        return (
                          <button
                            key={code}
                            type="button"
                            onClick={() => {
                              const next = active
                                ? filters.amenities.filter((c) => c !== code)
                                : [...filters.amenities, code];
                              dispatch(setFilters({ amenities: next }));
                            }}
                            className={`px-3 py-1.5 rounded-full text-xs font-medium border transition-colors ${
                              active
                                ? 'bg-primary-600 text-white border-primary-600'
                                : 'bg-white text-gray-700 border-gray-200 hover:border-primary-400'
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

            <button
              type="button"
              onClick={() => navigate('/accommodations')}
              className="w-full inline-flex items-center justify-center h-11 rounded-xl px-8 text-sm font-semibold text-white bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 hover:shadow-primary-500/40"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" className="mr-2">
                <circle cx="11" cy="11" r="8" />
                <path d="m21 21-4.34-4.34" />
              </svg>
              {t('hero.search')}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default HeroSection;
