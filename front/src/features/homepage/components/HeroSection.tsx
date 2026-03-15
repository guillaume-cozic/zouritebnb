import React from 'react';
import { useTranslation } from 'react-i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { setFilters } from '../HomepageSlice';
import { selectHomepageFilters } from '../HomepageSelectors';

const HeroSection: React.FC = () => {
  const { t } = useTranslation();
  const dispatch = useAppDispatch();
  const filters = useAppSelector(selectHomepageFilters);

  return (
    <div className="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-20 px-4">
      <div className="max-w-4xl mx-auto text-center">
        <h1 className="text-4xl md:text-5xl font-bold mb-4">
          {t('hero.title')}
        </h1>
        <p className="text-lg text-blue-100 mb-8">
          {t('hero.subtitle')}
        </p>
        <div className="bg-white rounded-xl shadow-lg p-4 flex flex-col md:flex-row gap-4 max-w-2xl mx-auto">
          <input
            type="text"
            placeholder={t('hero.city')}
            value={filters.city}
            onChange={(e) => dispatch(setFilters({ city: e.target.value }))}
            className="flex-1 px-4 py-3 rounded-lg border border-gray-200 text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
          <input
            type="number"
            placeholder={t('hero.guests')}
            min={1}
            value={filters.guests ?? ''}
            onChange={(e) =>
              dispatch(setFilters({ guests: e.target.value ? Number(e.target.value) : null }))
            }
            className="w-full md:w-32 px-4 py-3 rounded-lg border border-gray-200 text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
      </div>
    </div>
  );
};

export default HeroSection;
