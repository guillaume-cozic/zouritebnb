import React, { useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { fetchPublishedAccommodations, setFilters } from '../HomepageSlice';
import {
  selectFilteredAccommodations,
  selectHomepageStatus,
  selectHomepageError,
  selectHomepageFilters,
} from '../HomepageSelectors';
import AccommodationCard from './AccommodationCard';

const QUICK_AMENITIES = ['wifi', 'air_conditioning', 'private_pool', 'private_parking', 'sea_view', 'pets_allowed'] as const;

const AccommodationsListingPage: React.FC = () => {
  const { t } = useTranslation();
  const dispatch = useAppDispatch();
  const accommodations = useAppSelector(selectFilteredAccommodations);
  const status = useAppSelector(selectHomepageStatus);
  const error = useAppSelector(selectHomepageError);
  const filters = useAppSelector(selectHomepageFilters);

  useEffect(() => {
    dispatch(fetchPublishedAccommodations({ checkIn: filters.checkIn, checkOut: filters.checkOut }));
  }, [dispatch, filters.checkIn, filters.checkOut]);

  const toggleAmenity = (code: string) => {
    const next = filters.amenities.includes(code)
      ? filters.amenities.filter((c) => c !== code)
      : [...filters.amenities, code];
    dispatch(setFilters({ amenities: next }));
  };

  return (
    <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      <div className="mb-6">
        <h1 className="text-3xl font-bold text-gray-900">{t('listing.title')}</h1>
        <p className="text-gray-500 mt-1">{t('listing.subtitle')}</p>
      </div>

      {/* Filters bar */}
      <div className="bg-white rounded-2xl border border-gray-100 p-5 mb-8 space-y-4">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
          <div>
            <label className="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
              {t('hero.city')}
            </label>
            <input
              type="text"
              placeholder={t('hero.searchPlaceholder')}
              value={filters.city}
              onChange={(e) => dispatch(setFilters({ city: e.target.value }))}
              className="w-full h-11 rounded-xl border border-gray-200 bg-gray-50 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 focus:bg-white transition-all"
            />
          </div>
          <div>
            <label className="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
              {t('hero.guests')}
            </label>
            <input
              type="number"
              min={1}
              value={filters.guests ?? ''}
              onChange={(e) =>
                dispatch(setFilters({ guests: e.target.value ? Number(e.target.value) : null }))
              }
              placeholder="1"
              className="w-full h-11 rounded-xl border border-gray-200 bg-gray-50 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 focus:bg-white transition-all"
            />
          </div>
          <div className="flex items-end">
            <button
              type="button"
              onClick={() => dispatch(setFilters({ city: '', guests: null, amenities: [] }))}
              className="w-full h-11 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-sm font-medium"
            >
              {t('listing.reset')}
            </button>
          </div>
        </div>
        <div>
          <label className="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
            {t('hero.amenities')}
          </label>
          <div className="flex flex-wrap gap-2">
            {QUICK_AMENITIES.map((code) => {
              const active = filters.amenities.includes(code);
              return (
                <button
                  key={code}
                  type="button"
                  onClick={() => toggleAmenity(code)}
                  className={`px-3 py-1.5 rounded-full text-xs font-medium border transition-colors ${
                    active
                      ? 'bg-blue-600 text-white border-blue-600'
                      : 'bg-white text-gray-700 border-gray-200 hover:border-blue-400'
                  }`}
                >
                  {t(`amenities.${code}`, code)}
                </button>
              );
            })}
          </div>
        </div>
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
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          {accommodations.map((item) => (
            <AccommodationCard key={item.id} accommodation={item} />
          ))}
        </div>
      )}
    </section>
  );
};

export default AccommodationsListingPage;
