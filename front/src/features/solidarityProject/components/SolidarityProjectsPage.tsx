import React, { useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { fetchSolidarityProjects } from '../SolidarityProjectSlice';
import {
  selectSolidarityProjects,
  selectSolidarityProjectsStatus,
  selectSolidarityProjectsError,
} from '../SolidarityProjectSelectors';
import SolidarityProjectCard from './SolidarityProjectCard';
import Footer from '../../../components/Footer';

const SolidarityProjectsPage: React.FC = () => {
  const { t } = useTranslation();
  const dispatch = useAppDispatch();
  const projects = useAppSelector(selectSolidarityProjects);
  const status = useAppSelector(selectSolidarityProjectsStatus);
  const error = useAppSelector(selectSolidarityProjectsError);

  useEffect(() => {
    dispatch(fetchSolidarityProjects());
  }, [dispatch]);

  return (
    <div className="min-h-[calc(100vh-4rem)] flex flex-col bg-gray-50/50">
      <div className="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div className="text-center mb-12">
          <h1 className="text-4xl font-bold mb-4">{t('projects.pageTitle')}</h1>
          <p className="text-lg text-gray-500 max-w-2xl mx-auto">{t('projects.pageSubtitle')}</p>
        </div>

        {status === 'loading' && (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {[1, 2, 3, 4, 5, 6].map((i) => (
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

        {status === 'succeeded' && projects.length === 0 && (
          <p className="text-center text-gray-500">{t('projects.empty')}</p>
        )}

        {projects.length > 0 && (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {projects.map((p) => (
              <SolidarityProjectCard key={p.id} project={p} />
            ))}
          </div>
        )}
      </div>
      <Footer />
    </div>
  );
};

export default SolidarityProjectsPage;
