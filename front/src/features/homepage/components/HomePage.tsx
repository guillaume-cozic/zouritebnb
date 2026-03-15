import React, { useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { fetchPublishedAccommodations } from '../HomepageSlice';
import { selectFilteredAccommodations, selectHomepageStatus, selectHomepageError } from '../HomepageSelectors';
import HeroSection from './HeroSection';
import AccommodationCard from './AccommodationCard';

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
  const { t } = useTranslation();
  const dispatch = useAppDispatch();
  const accommodations = useAppSelector(selectFilteredAccommodations);
  const status = useAppSelector(selectHomepageStatus);
  const error = useAppSelector(selectHomepageError);

  useEffect(() => {
    dispatch(fetchPublishedAccommodations());
  }, [dispatch]);

  return (
    <div className="flex flex-col min-h-screen bg-white">
      <HeroSection />

      {/* Featured accommodations */}
      <section className="flex-1 pt-36 pb-20 bg-gray-50/50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-end mb-10">
            <div>
              <h2 className="text-3xl font-bold text-gray-900">{t('homepage.featuredTitle')}</h2>
              <p className="text-gray-500 mt-2">{t('homepage.featuredSubtitle')}</p>
            </div>
            <Link to="/create" className="hidden sm:block">
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
            <div className="rounded-2xl border border-gray-100 bg-white shadow-sm text-center group hover:shadow-lg transition-all hover:-translate-y-1">
              <div className="flex flex-col space-y-1.5 p-6">
                <div className="mx-auto w-12 h-12 bg-blue-50 rounded-full flex items-center justify-center mb-4 group-hover:bg-blue-100 transition-colors">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-blue-600">
                    <path d="M2 6c.6.5 1.2 1 2.5 1C7 7 7 5 9.5 5c2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1" />
                    <path d="M2 12c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1" />
                    <path d="M2 18c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1" />
                  </svg>
                </div>
                <h3 className="text-xl font-semibold leading-none tracking-tight group-hover:text-blue-600 transition-colors">
                  {t('activities.diving')}
                </h3>
              </div>
              <div className="p-6 pt-0">
                <p className="text-gray-500 text-sm">{t('activities.divingDesc')}</p>
              </div>
            </div>

            {/* Hiking */}
            <div className="rounded-2xl border border-gray-100 bg-white shadow-sm text-center group hover:shadow-lg transition-all hover:-translate-y-1">
              <div className="flex flex-col space-y-1.5 p-6">
                <div className="mx-auto w-12 h-12 bg-blue-50 rounded-full flex items-center justify-center mb-4 group-hover:bg-blue-100 transition-colors">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-blue-600">
                    <path d="m8 3 4 8 5-5 5 15H2L8 3z" />
                  </svg>
                </div>
                <h3 className="text-xl font-semibold leading-none tracking-tight group-hover:text-blue-600 transition-colors">
                  {t('activities.hiking')}
                </h3>
              </div>
              <div className="p-6 pt-0">
                <p className="text-gray-500 text-sm">{t('activities.hikingDesc')}</p>
              </div>
            </div>

            {/* Excursions */}
            <div className="rounded-2xl border border-gray-100 bg-white shadow-sm text-center group hover:shadow-lg transition-all hover:-translate-y-1">
              <div className="flex flex-col space-y-1.5 p-6">
                <div className="mx-auto w-12 h-12 bg-blue-50 rounded-full flex items-center justify-center mb-4 group-hover:bg-blue-100 transition-colors">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-blue-600">
                    <circle cx="12" cy="12" r="10" />
                    <path d="m16.24 7.76-1.804 5.411a2 2 0 0 1-1.265 1.265L7.76 16.24l1.804-5.411a2 2 0 0 1 1.265-1.265z" />
                  </svg>
                </div>
                <h3 className="text-xl font-semibold leading-none tracking-tight group-hover:text-blue-600 transition-colors">
                  {t('activities.excursions')}
                </h3>
              </div>
              <div className="p-6 pt-0">
                <p className="text-gray-500 text-sm">{t('activities.excursionsDesc')}</p>
              </div>
            </div>

            {/* Gastronomy */}
            <div className="rounded-2xl border border-gray-100 bg-white shadow-sm text-center group hover:shadow-lg transition-all hover:-translate-y-1">
              <div className="flex flex-col space-y-1.5 p-6">
                <div className="mx-auto w-12 h-12 bg-blue-50 rounded-full flex items-center justify-center mb-4 group-hover:bg-blue-100 transition-colors">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-blue-600">
                    <path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2" />
                    <path d="M7 2v20" />
                    <path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7" />
                  </svg>
                </div>
                <h3 className="text-xl font-semibold leading-none tracking-tight group-hover:text-blue-600 transition-colors">
                  {t('activities.gastronomy')}
                </h3>
              </div>
              <div className="p-6 pt-0">
                <p className="text-gray-500 text-sm">{t('activities.gastronomyDesc')}</p>
              </div>
            </div>
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
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-blue-600">
                    <circle cx="12" cy="12" r="4" />
                    <path d="M12 2v2" /><path d="M12 20v2" />
                    <path d="m4.93 4.93 1.41 1.41" /><path d="m17.66 17.66 1.41 1.41" />
                    <path d="M2 12h2" /><path d="M20 12h2" />
                    <path d="m6.34 17.66-1.41 1.41" /><path d="m19.07 4.93-1.41 1.41" />
                  </svg>
                  <span className="text-sm">{t('discover.climate')}</span>
                </div>
                <div className="flex items-center gap-2">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-blue-600">
                    <path d="M2 6c.6.5 1.2 1 2.5 1C7 7 7 5 9.5 5c2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1" />
                    <path d="M2 12c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1" />
                    <path d="M2 18c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1" />
                  </svg>
                  <span className="text-sm">{t('discover.beaches')}</span>
                </div>
                <div className="flex items-center gap-2">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-blue-600">
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
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-blue-600">
                    <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0" />
                    <circle cx="12" cy="10" r="3" />
                  </svg>
                  <span className="text-sm">{t('discover.nature')}</span>
                </div>
              </div>
              <button className="inline-flex items-center justify-center text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 h-11 rounded-xl px-8 transition-all hover:shadow-md hover:shadow-blue-200">
                {t('discover.learnMore')}
              </button>
            </div>
            <div className="relative aspect-video rounded-2xl overflow-hidden shadow-xl">
              <iframe
                src="https://www.youtube.com/embed/67nE_apLm9Y"
                title={t('discover.videoTitle')}
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowFullScreen
                className="absolute inset-0 w-full h-full"
              />
            </div>
          </div>
        </div>
      </section>

      {/* Solidarity Projects */}
      <section id="projects" className="py-16 scroll-mt-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold mb-4">{t('projects.title')}</h2>
            <p className="text-lg text-gray-500 max-w-2xl mx-auto">{t('projects.subtitle')}</p>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {/* Coral Reefs */}
            <div className="rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden flex flex-col group hover:shadow-lg transition-all">
              <div className="aspect-[16/9] relative overflow-hidden">
                <img src="https://images.unsplash.com/photo-1546026423-cc4642628d2b?auto=format&fit=crop&q=80" alt={t('projects.coral.title')} className="absolute inset-0 h-full w-full object-cover transition-transform duration-300 group-hover:scale-105" />
              </div>
              <div className="flex flex-col space-y-1.5 p-6">
                <div className="flex items-center gap-3 mb-2">
                  <div className="w-8 h-8 bg-blue-50 rounded-full flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-blue-600">
                      <path d="M14 11a2 2 0 1 1-4 0 4 4 0 0 1 8 0 6 6 0 0 1-12 0 8 8 0 0 1 16 0 10 10 0 1 1-20 0 11.93 11.93 0 0 1 2.42-7.22 2 2 0 1 1 3.16 2.44" />
                    </svg>
                  </div>
                  <h3 className="font-semibold tracking-tight text-xl">{t('projects.coral.title')}</h3>
                </div>
              </div>
              <div className="p-6 pt-0 flex flex-col flex-1">
                <p className="text-gray-500 text-sm mb-4 flex-1">{t('projects.coral.description')}</p>
                <div className="grid grid-cols-3 gap-2 mb-4">
                  <div className="text-center p-2 bg-gray-50 rounded-lg">
                    <div className="font-bold text-blue-600">5000+</div>
                    <div className="text-xs text-gray-500">{t('projects.coral.stat1')}</div>
                  </div>
                  <div className="text-center p-2 bg-gray-50 rounded-lg">
                    <div className="font-bold text-blue-600">1000m&sup2;</div>
                    <div className="text-xs text-gray-500">{t('projects.coral.stat2')}</div>
                  </div>
                  <div className="text-center p-2 bg-gray-50 rounded-lg">
                    <div className="font-bold text-blue-600">15</div>
                    <div className="text-xs text-gray-500">{t('projects.coral.stat3')}</div>
                  </div>
                </div>
                <button className="w-full inline-flex items-center justify-center rounded-xl text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 h-10 px-4 transition-colors">
                  {t('projects.learnMore')}
                </button>
              </div>
            </div>

            {/* Turtle Reserve */}
            <div className="rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden flex flex-col group hover:shadow-lg transition-all">
              <div className="aspect-[16/9] relative overflow-hidden">
                <img src="https://images.unsplash.com/photo-1437622368342-7a3d73a34c8f?auto=format&fit=crop&q=80" alt={t('projects.turtles.title')} className="absolute inset-0 h-full w-full object-cover transition-transform duration-300 group-hover:scale-105" />
              </div>
              <div className="flex flex-col space-y-1.5 p-6">
                <div className="flex items-center gap-3 mb-2">
                  <div className="w-8 h-8 bg-blue-50 rounded-full flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-blue-600">
                      <path d="m12 10 2 4v3a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-3a8 8 0 1 0-16 0v3a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-3l2-4h4Z" />
                      <path d="M4.82 7.9 8 10" /><path d="M15.18 7.9 12 10" />
                      <path d="M16.93 10H20a2 2 0 0 1 0 4H2" />
                    </svg>
                  </div>
                  <h3 className="font-semibold tracking-tight text-xl">{t('projects.turtles.title')}</h3>
                </div>
              </div>
              <div className="p-6 pt-0 flex flex-col flex-1">
                <p className="text-gray-500 text-sm mb-4 flex-1">{t('projects.turtles.description')}</p>
                <div className="grid grid-cols-3 gap-2 mb-4">
                  <div className="text-center p-2 bg-gray-50 rounded-lg">
                    <div className="font-bold text-blue-600">200+</div>
                    <div className="text-xs text-gray-500">{t('projects.turtles.stat1')}</div>
                  </div>
                  <div className="text-center p-2 bg-gray-50 rounded-lg">
                    <div className="font-bold text-blue-600">30+</div>
                    <div className="text-xs text-gray-500">{t('projects.turtles.stat2')}</div>
                  </div>
                  <div className="text-center p-2 bg-gray-50 rounded-lg">
                    <div className="font-bold text-blue-600">15000+</div>
                    <div className="text-xs text-gray-500">{t('projects.turtles.stat3')}</div>
                  </div>
                </div>
                <button className="w-full inline-flex items-center justify-center rounded-xl text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 h-10 px-4 transition-colors">
                  {t('projects.learnMore')}
                </button>
              </div>
            </div>

            {/* Water Management */}
            <div className="rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden flex flex-col group hover:shadow-lg transition-all">
              <div className="aspect-[16/9] relative overflow-hidden">
                <img src="https://images.unsplash.com/photo-1468421870903-4df1664ac249?auto=format&fit=crop&q=80" alt={t('projects.water.title')} className="absolute inset-0 h-full w-full object-cover transition-transform duration-300 group-hover:scale-105" />
              </div>
              <div className="flex flex-col space-y-1.5 p-6">
                <div className="flex items-center gap-3 mb-2">
                  <div className="w-8 h-8 bg-blue-50 rounded-full flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-blue-600">
                      <path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z" />
                      <path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97" />
                    </svg>
                  </div>
                  <h3 className="font-semibold tracking-tight text-xl">{t('projects.water.title')}</h3>
                </div>
              </div>
              <div className="p-6 pt-0 flex flex-col flex-1">
                <p className="text-gray-500 text-sm mb-4 flex-1">{t('projects.water.description')}</p>
                <div className="grid grid-cols-3 gap-2 mb-4">
                  <div className="text-center p-2 bg-gray-50 rounded-lg">
                    <div className="font-bold text-blue-600">500k+</div>
                    <div className="text-xs text-gray-500">{t('projects.water.stat1')}</div>
                  </div>
                  <div className="text-center p-2 bg-gray-50 rounded-lg">
                    <div className="font-bold text-blue-600">200+</div>
                    <div className="text-xs text-gray-500">{t('projects.water.stat2')}</div>
                  </div>
                  <div className="text-center p-2 bg-gray-50 rounded-lg">
                    <div className="font-bold text-blue-600">50</div>
                    <div className="text-xs text-gray-500">{t('projects.water.stat3')}</div>
                  </div>
                </div>
                <button className="w-full inline-flex items-center justify-center rounded-xl text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 h-10 px-4 transition-colors">
                  {t('projects.learnMore')}
                </button>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-gray-900 text-gray-400">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="py-12 grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
              <div className="flex items-center gap-2 mb-4">
                <div className="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                  </svg>
                </div>
                <span className="text-lg font-bold text-white">BnB</span>
              </div>
              <p className="text-sm leading-relaxed">{t('footer.description')}</p>
            </div>
            <div>
              <h4 className="font-semibold text-white mb-4 text-sm uppercase tracking-wider">{t('footer.navigation')}</h4>
              <ul className="space-y-2.5 text-sm">
                <li>
                  <Link to="/" className="hover:text-white transition-colors">
                    {t('navbar.home')}
                  </Link>
                </li>
                <li>
                  <Link to="/create" className="hover:text-white transition-colors">
                    {t('navbar.createAccommodation')}
                  </Link>
                </li>
              </ul>
            </div>
            <div>
              <h4 className="font-semibold text-white mb-4 text-sm uppercase tracking-wider">{t('footer.contact')}</h4>
              <ul className="space-y-2.5 text-sm">
                <li>contact@bnb.com</li>
              </ul>
            </div>
          </div>
          <div className="border-t border-gray-800 py-6 text-center text-sm text-gray-500">
            {t('footer.copyright')}
          </div>
        </div>
      </footer>
    </div>
  );
};

export default HomePage;
