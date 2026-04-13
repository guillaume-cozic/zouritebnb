import React, { useEffect } from 'react';
import { Link, useParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { fetchSolidarityProjectById } from '../SolidarityProjectSlice';
import {
  selectCurrentSolidarityProject,
  selectCurrentSolidarityProjectStatus,
  selectCurrentSolidarityProjectError,
} from '../SolidarityProjectSelectors';
import { SolidarityProject } from '../SolidarityProjectTypes';

const formatDate = (iso: string, locale: string): string => {
  try {
    return new Intl.DateTimeFormat(locale, {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    }).format(new Date(iso));
  } catch {
    return iso;
  }
};

const readingMinutes = (text: string): number => {
  const words = text.trim().split(/\s+/).length;
  return Math.max(1, Math.ceil(words / 200));
};

const renderBody = (description: string): React.ReactNode => {
  // If the description already contains HTML (future WYSIWYG output), render it.
  if (/<[a-z][\s\S]*>/i.test(description)) {
    return (
      <div
        className="prose-blog"
        dangerouslySetInnerHTML={{ __html: description }}
      />
    );
  }
  // Fallback: split plain text on blank lines into paragraphs.
  return (
    <div className="prose-blog">
      {description
        .split(/\n{2,}/)
        .map((para, i) => (
          <p key={i}>{para}</p>
        ))}
    </div>
  );
};

const ArticleSkeleton: React.FC = () => (
  <div className="animate-pulse">
    <div className="h-[420px] bg-gray-200" />
    <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 -mt-20 relative">
      <div className="bg-white rounded-2xl shadow-xl p-10 space-y-4">
        <div className="h-4 bg-gray-200 rounded w-32" />
        <div className="h-10 bg-gray-200 rounded w-3/4" />
        <div className="h-4 bg-gray-100 rounded w-full" />
        <div className="h-4 bg-gray-100 rounded w-full" />
        <div className="h-4 bg-gray-100 rounded w-2/3" />
      </div>
    </div>
  </div>
);

interface ArticleProps {
  project: SolidarityProject;
}

const Article: React.FC<ArticleProps> = ({ project }) => {
  const { t, i18n } = useTranslation();
  const minutes = readingMinutes(project.description);

  return (
    <article>
      {/* Hero image */}
      <div className="relative h-[420px] sm:h-[520px] overflow-hidden bg-gradient-to-br from-blue-100 to-blue-200">
        {project.imageUrl ? (
          <>
            <img
              src={project.imageUrl}
              alt={project.title}
              className="absolute inset-0 h-full w-full object-cover"
            />
            <div className="absolute inset-0 bg-gradient-to-b from-black/30 via-black/10 to-black/60" />
          </>
        ) : (
          <div className="absolute inset-0 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.2" className="text-blue-300">
              <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
            </svg>
          </div>
        )}

        {/* Back link overlay */}
        <div className="absolute top-6 left-0 right-0">
          <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <Link
              to="/solidarity-projects"
              className="inline-flex items-center gap-2 text-sm font-medium text-white/90 hover:text-white bg-black/30 backdrop-blur-sm rounded-full px-4 py-2 transition-all"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                <path d="m12 19-7-7 7-7" />
                <path d="M19 12H5" />
              </svg>
              {t('projects.back')}
            </Link>
          </div>
        </div>

        {/* Title overlay */}
        <div className="absolute bottom-0 left-0 right-0 pb-12">
          <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <span
              className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold mb-4 ${
                project.status === 'active'
                  ? 'bg-green-500 text-white'
                  : 'bg-gray-700 text-white'
              }`}
            >
              <span className={`w-1.5 h-1.5 rounded-full ${project.status === 'active' ? 'bg-white animate-pulse' : 'bg-gray-300'}`} />
              {t(`projects.status.${project.status}`)}
            </span>
            <h1 className="text-4xl sm:text-5xl font-bold text-white leading-tight tracking-tight drop-shadow-lg">
              {project.title}
            </h1>
          </div>
        </div>
      </div>

      {/* Body */}
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 -mt-10 relative">
        <div className="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
          {/* Meta bar */}
          <div className="flex flex-wrap items-center gap-x-6 gap-y-3 px-8 sm:px-12 py-5 border-b border-gray-100 text-sm text-gray-500">
            <div className="flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <rect width="18" height="18" x="3" y="4" rx="2" />
                <path d="M16 2v4M8 2v4M3 10h18" />
              </svg>
              <time dateTime={project.createdAt}>{formatDate(project.createdAt, i18n.language)}</time>
            </div>
            <div className="flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <circle cx="12" cy="12" r="10" />
                <polyline points="12 6 12 12 16 14" />
              </svg>
              <span>{t('projects.readingTime', { count: minutes })}</span>
            </div>
            <div className="flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M14 11a2 2 0 1 1-4 0 4 4 0 0 1 8 0 6 6 0 0 1-12 0 8 8 0 0 1 16 0 10 10 0 1 1-20 0 11.93 11.93 0 0 1 2.42-7.22 2 2 0 1 1 3.16 2.44" />
              </svg>
              <span>{t('projects.category')}</span>
            </div>
          </div>

          {/* Content */}
          <div className="px-8 sm:px-12 py-10">
            {renderBody(project.description)}
          </div>

          {/* CTA footer */}
          <div className="px-8 sm:px-12 py-8 bg-gradient-to-br from-blue-50 to-white border-t border-gray-100">
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
              <div>
                <p className="text-lg font-semibold text-gray-900">{t('projects.cta.title')}</p>
                <p className="text-sm text-gray-500 mt-1">{t('projects.cta.subtitle')}</p>
              </div>
              <button className="inline-flex items-center justify-center gap-2 rounded-xl text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 h-11 px-6 transition-all hover:shadow-md hover:shadow-blue-200">
                {t('projects.cta.button')}
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                  <path d="M20.42 4.58a5.4 5.4 0 0 0-7.65 0L12 5.34l-.77-.76a5.4 5.4 0 0 0-7.65 7.65l8.42 8.42 8.42-8.42a5.4 5.4 0 0 0 0-7.65z" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        {/* Back to list */}
        <div className="mt-10 mb-16 text-center">
          <Link
            to="/solidarity-projects"
            className="inline-flex items-center gap-2 text-sm font-medium text-gray-500 hover:text-blue-600 transition-colors"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
              <path d="m15 18-6-6 6-6" />
            </svg>
            {t('projects.back')}
          </Link>
        </div>
      </div>
    </article>
  );
};

const SolidarityProjectDetailPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const dispatch = useAppDispatch();
  const project = useAppSelector(selectCurrentSolidarityProject);
  const status = useAppSelector(selectCurrentSolidarityProjectStatus);
  const error = useAppSelector(selectCurrentSolidarityProjectError);

  useEffect(() => {
    if (id) {
      dispatch(fetchSolidarityProjectById(id));
    }
  }, [dispatch, id]);

  return (
    <div className="min-h-screen bg-gray-50/50 -mt-16 pt-16">
      {status === 'loading' && <ArticleSkeleton />}
      {status === 'failed' && (
        <p className="text-center text-red-500 py-20">{error}</p>
      )}
      {status === 'succeeded' && project && <Article project={project} />}
    </div>
  );
};

export default SolidarityProjectDetailPage;
