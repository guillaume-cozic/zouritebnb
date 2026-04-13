import React, { useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { fetchSolidarityProjects } from '../SolidarityProjectSlice';
import {
  selectSolidarityProjects,
  selectSolidarityProjectsStatus,
  selectSolidarityProjectsError,
} from '../SolidarityProjectSelectors';
import SolidarityProjectCard from './SolidarityProjectCard';

const SolidarityProjectsSection: React.FC = () => {
  const { t } = useTranslation();
  const dispatch = useAppDispatch();
  const projects = useAppSelector(selectSolidarityProjects);
  const status = useAppSelector(selectSolidarityProjectsStatus);
  const error = useAppSelector(selectSolidarityProjectsError);

  useEffect(() => {
    if (status === 'idle') {
      dispatch(fetchSolidarityProjects());
    }
  }, [dispatch, status]);

  const preview = projects.slice(0, 3);

  return (
    <section id="projects" className="py-16 scroll-mt-16">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center mb-12">
          <h2 className="text-3xl font-bold mb-4">{t('projects.title')}</h2>
          <p className="text-lg text-gray-500 max-w-2xl mx-auto">{t('projects.subtitle')}</p>
        </div>

        {status === 'loading' && (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {[1, 2, 3].map((i) => (
              <div key={i} className="rounded-2xl border border-gray-100 bg-white overflow-hidden animate-pulse">
                <div className="aspect-[16/9] bg-gray-200" />
                <div className="p-6 space-y-3">
                  <div className="h-5 bg-gray-200 rounded-lg w-3/4" />
                  <div className="h-4 bg-gray-100 rounded-lg w-full" />
                  <div className="h-4 bg-gray-100 rounded-lg w-2/3" />
                </div>
              </div>
            ))}
          </div>
        )}

        {status === 'failed' && (
          <p className="text-center text-red-500">{error}</p>
        )}

        {status === 'succeeded' && preview.length === 0 && (
          <p className="text-center text-gray-500">{t('projects.empty')}</p>
        )}

        {preview.length > 0 && (
          <>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
              {preview.map((p) => (
                <SolidarityProjectCard key={p.id} project={p} />
              ))}
            </div>
            <div className="mt-10 text-center">
              <Link
                to="/solidarity-projects"
                className="inline-flex items-center gap-2 rounded-xl text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 h-11 px-6 transition-all hover:shadow-md hover:shadow-blue-200"
              >
                {t('projects.viewAll')}
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                  <path d="M5 12h14" />
                  <path d="m12 5 7 7-7 7" />
                </svg>
              </Link>
            </div>
          </>
        )}
      </div>
    </section>
  );
};

export default SolidarityProjectsSection;
