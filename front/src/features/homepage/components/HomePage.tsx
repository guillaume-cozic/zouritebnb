import React, { useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { fetchHomepageFeatured } from '../HomepageSlice';
import { selectAccommodations, selectHomepageStatus, selectHomepageError } from '../HomepageSelectors';
import HeroSection from './HeroSection';
import AccommodationCard from './AccommodationCard';
import AboutRodriguesSection from './AboutRodriguesSection';

// La carte (Leaflet, ~150 Ko) est sous la ligne de flottaison : chunk séparé,
// chargé après le rendu initial.
const RodriguesMap = React.lazy(() => import('./RodriguesMap'));
import SolidarityProjectsSection from '../../solidarityProject/components/SolidarityProjectsSection';
import Footer from '../../../components/Footer';
import LiteYouTube from '../../../components/LiteYouTube';

import { blogActivityLinks } from '../../../i18n/blogUrl';

const LoadingSkeleton: React.FC = () => (
  <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
    {[1, 2, 3].map((i) => (
      <div key={i} className="rounded-2xl border border-gray-100 bg-white overflow-hidden animate-pulse">
        <div className="aspect-video bg-gray-200" />
        <div className="p-5 space-y-3">
          <div className="h-5 bg-gray-200 rounded-lg w-3/4" />
          <div className="h-4 bg-gray-100 rounded-lg w-1/2" />
          <div className="h-4 bg-gray-100 rounded-lg w-full" />
          <div className="h-4 bg-gray-100 rounded-lg w-2/3" />
          <div className="pt-3 border-t border-gray-100 flex justify-between">
            <div className="h-6 bg-gray-200 rounded-lg w-20" />
            <div className="h-10 bg-gray-200 rounded-xl w-32" />
          </div>
        </div>
      </div>
    ))}
  </div>
);

const EmptyState: React.FC<{ message: string }> = ({ message }) => (
  <div className="text-center py-20">
    <div className="w-20 h-20 mx-auto mb-6 rounded-full bg-gray-100 flex items-center justify-center">
      <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" className="text-gray-400">
        <circle cx="11" cy="11" r="8" />
        <path d="m21 21-4.34-4.34" />
      </svg>
    </div>
    <p className="text-gray-500 text-lg">{message}</p>
  </div>
);

const HomePage: React.FC = () => {
  const { t, i18n } = useTranslation();
  const activityLinks = blogActivityLinks(i18n.language);
  const dispatch = useAppDispatch();
  const accommodations = useAppSelector(selectAccommodations);
  const status = useAppSelector(selectHomepageStatus);
  const error = useAppSelector(selectHomepageError);

  useEffect(() => {
    dispatch(fetchHomepageFeatured());
  }, [dispatch]);

  return (
    <div className="flex flex-col min-h-screen bg-white">
      <HeroSection />

      {/* Featured accommodations */}
      {/* md:pt-44: clears the search card that overhangs the hero by half its
          height on desktop — on mobile the card sits in the normal flow */}
      <section className="flex-1 pt-12 md:pt-44 pb-20 bg-gray-50/50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-end mb-10">
            <div>
              <h2 className="text-3xl font-bold text-gray-900">{t('homepage.featuredTitle')}</h2>
              <p className="text-gray-500 mt-2">{t('homepage.featuredSubtitle')}</p>
            </div>
            <Link to="/accommodations" className="hidden sm:block">
              <button className="inline-flex items-center gap-2 rounded-xl text-sm font-medium border border-gray-200 bg-white hover:bg-gray-50 h-10 px-5 transition-all hover:shadow-sm">
                {t('homepage.viewAll')}
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                  <path d="M5 12h14" />
                  <path d="m12 5 7 7-7 7" />
                </svg>
              </button>
            </Link>
          </div>

          {status === 'loading' && <LoadingSkeleton />}
          {status === 'failed' && (
            <div className="text-center py-12">
              <div className="w-16 h-16 mx-auto mb-4 rounded-full bg-red-50 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-red-400">
                  <circle cx="12" cy="12" r="10" />
                  <path d="m15 9-6 6" />
                  <path d="m9 9 6 6" />
                </svg>
              </div>
              <p className="text-red-500">{error}</p>
            </div>
          )}
          {status === 'succeeded' && accommodations.length === 0 && (
            <EmptyState message={t('homepage.noResults')} />
          )}
          {accommodations.length > 0 && (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
              {accommodations.map((item) => (
                <AccommodationCard key={item.id} accommodation={item} />
              ))}
            </div>
          )}
        </div>
      </section>

      {/* Activities */}
      <section className="py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h2 className="text-3xl font-bold mb-12 text-center">{t('activities.title')}</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            {/* Diving */}
            <a href={activityLinks.diving} className="block rounded-2xl border border-gray-100 bg-white shadow-sm text-center group hover:shadow-lg transition-all hover:-translate-y-1">
              <div className="flex flex-col space-y-1.5 p-6">
                <div className="mx-auto w-12 h-12 bg-primary-50 rounded-full flex items-center justify-center mb-4 group-hover:bg-primary-100 transition-colors">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-primary-600">
                    <path d="M2 6c.6.5 1.2 1 2.5 1C7 7 7 5 9.5 5c2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1" />
                    <path d="M2 12c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1" />
                    <path d="M2 18c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1" />
                  </svg>
                </div>
                <h3 className="text-xl font-semibold leading-none tracking-tight group-hover:text-primary-600 transition-colors">
                  {t('activities.diving')}
                </h3>
              </div>
              <div className="p-6 pt-0">
                <p className="text-gray-500 text-sm">{t('activities.divingDesc')}</p>
              </div>
            </a>

            {/* Hiking */}
            <a href={activityLinks.hiking} className="block rounded-2xl border border-gray-100 bg-white shadow-sm text-center group hover:shadow-lg transition-all hover:-translate-y-1">
              <div className="flex flex-col space-y-1.5 p-6">
                <div className="mx-auto w-12 h-12 bg-primary-50 rounded-full flex items-center justify-center mb-4 group-hover:bg-primary-100 transition-colors">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-primary-600">
                    <path d="m8 3 4 8 5-5 5 15H2L8 3z" />
                  </svg>
                </div>
                <h3 className="text-xl font-semibold leading-none tracking-tight group-hover:text-primary-600 transition-colors">
                  {t('activities.hiking')}
                </h3>
              </div>
              <div className="p-6 pt-0">
                <p className="text-gray-500 text-sm">{t('activities.hikingDesc')}</p>
              </div>
            </a>

            {/* Excursions */}
            <a href={activityLinks.excursions} className="block rounded-2xl border border-gray-100 bg-white shadow-sm text-center group hover:shadow-lg transition-all hover:-translate-y-1">
              <div className="flex flex-col space-y-1.5 p-6">
                <div className="mx-auto w-12 h-12 bg-primary-50 rounded-full flex items-center justify-center mb-4 group-hover:bg-primary-100 transition-colors">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-primary-600">
                    <circle cx="12" cy="12" r="10" />
                    <path d="m16.24 7.76-1.804 5.411a2 2 0 0 1-1.265 1.265L7.76 16.24l1.804-5.411a2 2 0 0 1 1.265-1.265z" />
                  </svg>
                </div>
                <h3 className="text-xl font-semibold leading-none tracking-tight group-hover:text-primary-600 transition-colors">
                  {t('activities.excursions')}
                </h3>
              </div>
              <div className="p-6 pt-0">
                <p className="text-gray-500 text-sm">{t('activities.excursionsDesc')}</p>
              </div>
            </a>

            {/* Gastronomy */}
            <a href={activityLinks.gastronomy} className="block rounded-2xl border border-gray-100 bg-white shadow-sm text-center group hover:shadow-lg transition-all hover:-translate-y-1">
              <div className="flex flex-col space-y-1.5 p-6">
                <div className="mx-auto w-12 h-12 bg-primary-50 rounded-full flex items-center justify-center mb-4 group-hover:bg-primary-100 transition-colors">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-primary-600">
                    <path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2" />
                    <path d="M7 2v20" />
                    <path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7" />
                  </svg>
                </div>
                <h3 className="text-xl font-semibold leading-none tracking-tight group-hover:text-primary-600 transition-colors">
                  {t('activities.gastronomy')}
                </h3>
              </div>
              <div className="p-6 pt-0">
                <p className="text-gray-500 text-sm">{t('activities.gastronomyDesc')}</p>
              </div>
            </a>
          </div>
        </div>
      </section>

      {/* Discover Rodrigues */}
      <section className="py-16 bg-gray-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div>
              <h2 className="text-3xl font-bold mb-6">{t('discover.title')}</h2>
              <p className="text-lg text-gray-600 mb-6">{t('discover.description')}</p>
              <div className="grid grid-cols-2 gap-4 mb-8">
                <div className="flex items-center gap-2">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-primary-600">
                    <circle cx="12" cy="12" r="4" />
                    <path d="M12 2v2" /><path d="M12 20v2" />
                    <path d="m4.93 4.93 1.41 1.41" /><path d="m17.66 17.66 1.41 1.41" />
                    <path d="M2 12h2" /><path d="M20 12h2" />
                    <path d="m6.34 17.66-1.41 1.41" /><path d="m19.07 4.93-1.41 1.41" />
                  </svg>
                  <span className="text-sm">{t('discover.climate')}</span>
                </div>
                <div className="flex items-center gap-2">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-primary-600">
                    <path d="M2 6c.6.5 1.2 1 2.5 1C7 7 7 5 9.5 5c2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1" />
                    <path d="M2 12c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1" />
                    <path d="M2 18c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1" />
                  </svg>
                  <span className="text-sm">{t('discover.beaches')}</span>
                </div>
                <div className="flex items-center gap-2">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-primary-600">
                    <path d="M6.5 12c.94-3.46 4.94-6 8.5-6 3.56 0 6.06 2.54 7 6-.94 3.47-3.44 6-7 6s-7.56-2.53-8.5-6Z" />
                    <path d="M18 12v.5" />
                    <path d="M16 17.93a9.77 9.77 0 0 1 0-11.86" />
                    <path d="M7 10.67C7 8 5.58 5.97 2.73 5.5c-1 1.5-1 5 .23 6.5-1.24 1.5-1.24 5-.23 6.5C5.58 18.03 7 16 7 13.33" />
                    <path d="M10.46 7.26C10.2 5.88 9.17 4.24 8 3h5.8a2 2 0 0 1 1.98 1.67l.23 1.4" />
                    <path d="m16.01 17.93-.23 1.4A2 2 0 0 1 13.8 21H9.5a5.96 5.96 0 0 0 1.49-3.98" />
                  </svg>
                  <span className="text-sm">{t('discover.fishing')}</span>
                </div>
                <div className="flex items-center gap-2">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-primary-600">
                    <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0" />
                    <circle cx="12" cy="10" r="3" />
                  </svg>
                  <span className="text-sm">{t('discover.nature')}</span>
                </div>
              </div>
              <button className="inline-flex items-center justify-center text-sm font-semibold text-white bg-primary-600 hover:bg-primary-700 h-11 rounded-xl px-8 transition-all hover:shadow-md hover:shadow-primary-200">
                {t('discover.learnMore')}
              </button>
            </div>
            <div className="relative aspect-video rounded-2xl overflow-hidden shadow-xl">
              <LiteYouTube
                videoId="67nE_apLm9Y"
                title={t('discover.videoTitle')}
                className="absolute inset-0 w-full h-full"
              />
            </div>
          </div>
        </div>
      </section>

      <React.Suspense fallback={<div className="h-96 rounded-2xl bg-gray-100 animate-pulse" />}>
        <RodriguesMap />
      </React.Suspense>

      <SolidarityProjectsSection />

      <AboutRodriguesSection />

      <Footer />
    </div>
  );
};

export default HomePage;
