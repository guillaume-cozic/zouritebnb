import React, { useState, useEffect, useCallback } from 'react';
import { useTranslation } from 'react-i18next';
import DatePicker, { registerLocale } from 'react-datepicker';
import { fr } from 'date-fns/locale/fr';
import { enGB } from 'date-fns/locale/en-GB';
import 'react-datepicker/dist/react-datepicker.css';
import '../../../styles/datepicker-overrides.css';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { setFilters } from '../HomepageSlice';
import { selectHomepageFilters } from '../HomepageSelectors';

registerLocale('fr', fr);
registerLocale('en', enGB);

const toDate = (s: string): Date | null => (s ? new Date(s) : null);
const toStr = (d: Date | null): string =>
  d ? d.toISOString().slice(0, 10) : '';

const SLIDES = [
  'https://images.unsplash.com/photo-1559128010-7c1ad6e1b6a5?auto=format&fit=crop&q=80&w=2000',
  'https://images.unsplash.com/photo-1506953823976-52e1fdc0149a?auto=format&fit=crop&q=80&w=2000',
  'https://images.unsplash.com/photo-1505881502353-a1986add3762?auto=format&fit=crop&q=80&w=2000',
  'https://images.unsplash.com/photo-1537956965359-7573183d1f57?auto=format&fit=crop&q=80&w=2000',
];

const HeroSection: React.FC = () => {
  const { t, i18n } = useTranslation();
  const dispatch = useAppDispatch();
  const filters = useAppSelector(selectHomepageFilters);

  const [current, setCurrent] = useState(0);

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
    dispatch(setFilters({ checkIn: toStr(start), checkOut: toStr(end) }));
  };

  const dateLabel = startDate
    ? endDate
      ? `${startDate.toLocaleDateString(i18n.language)} – ${endDate.toLocaleDateString(i18n.language)}`
      : startDate.toLocaleDateString(i18n.language)
    : '';

  return (
    <div className="relative">
      {/* Slider */}
      <div className="relative h-[500px] w-full overflow-hidden">
        {SLIDES.map((src, i) => (
          <img
            key={src}
            src={src}
            alt=""
            className={`absolute inset-0 h-full w-full object-cover transition-opacity duration-1000 ease-in-out ${
              i === current ? 'opacity-100' : 'opacity-0'
            }`}
          />
        ))}
        <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/40 to-black/20" />

        {/* Hero text */}
        <div className="relative h-full flex flex-col items-center justify-center px-4 pb-16">
          <h1 className="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-4 text-center tracking-tight">
            {t('hero.title')}
          </h1>
          <p className="text-lg md:text-xl text-white/80 max-w-2xl mx-auto text-center font-light">
            {t('hero.subtitle')}
          </p>
        </div>

        {/* Dots */}
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
      </div>

      {/* Search form */}
      <div className="absolute bottom-0 left-0 right-0 transform translate-y-1/2 z-10">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="bg-white p-5 sm:p-6 rounded-2xl shadow-xl shadow-black/10 border border-gray-100">
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
                    onChange={(e) => dispatch(setFilters({ city: e.target.value }))}
                    className="w-full h-11 rounded-xl border border-gray-200 bg-gray-50 pl-10 pr-3 text-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 focus:bg-white transition-all"
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
                    className="w-full h-11 rounded-xl border border-gray-200 bg-gray-50 pl-10 pr-3 text-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 focus:bg-white transition-all"
                    isClearable
                  />
                </div>
              </div>

              {/* Voyageurs */}
              <div className="space-y-1.5">
                <label className="block text-xs font-semibold text-gray-500 uppercase tracking-wider">
                  {t('hero.guests')}
                </label>
                <div className="relative">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                  </svg>
                  <input
                    type="number"
                    min={1}
                    value={filters.guests ?? ''}
                    onChange={(e) =>
                      dispatch(setFilters({ guests: e.target.value ? Number(e.target.value) : null }))
                    }
                    placeholder="1"
                    className="w-full h-11 rounded-xl border border-gray-200 bg-gray-50 pl-10 pr-3 text-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 focus:bg-white transition-all"
                  />
                </div>
              </div>
            </div>

            <button
              type="button"
              className="w-full inline-flex items-center justify-center h-11 rounded-xl px-8 text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg shadow-blue-500/25 hover:shadow-blue-500/40"
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
