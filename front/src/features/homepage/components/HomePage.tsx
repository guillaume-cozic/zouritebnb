import React, { useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { fetchPublishedAccommodations } from '../HomepageSlice';
import { selectFilteredAccommodations, selectHomepageStatus, selectHomepageError } from '../HomepageSelectors';
import HeroSection from './HeroSection';
import AccommodationCard from './AccommodationCard';

const HomePage: React.FC = () => {
  const { t } = useTranslation();
  const dispatch = useAppDispatch();
  const accommodations = useAppSelector(selectFilteredAccommodations);
  const status = useAppSelector(selectHomepageStatus);
  const error = useAppSelector(selectHomepageError);

  useEffect(() => {
    dispatch(fetchPublishedAccommodations());
  }, [dispatch]);

  return (
    <div>
      <HeroSection />
      <div className="max-w-7xl mx-auto px-4 py-8">
        {status === 'loading' && (
          <p className="text-center text-gray-500">{t('homepage.loading')}</p>
        )}
        {status === 'failed' && (
          <p className="text-center text-red-500">{error}</p>
        )}
        {status === 'succeeded' && accommodations.length === 0 && (
          <p className="text-center text-gray-500">{t('homepage.noResults')}</p>
        )}
        {accommodations.length > 0 && (
          <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            {accommodations.map((item) => (
              <AccommodationCard key={item.id} accommodation={item} />
            ))}
          </div>
        )}
      </div>
    </div>
  );
};

export default HomePage;
