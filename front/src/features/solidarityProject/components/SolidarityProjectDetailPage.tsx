import React, { useEffect, useMemo, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import {
  fetchSolidarityProjectById,
  fetchSolidarityProjects,
} from '../SolidarityProjectSlice';
import {
  selectCurrentSolidarityProject,
  selectCurrentSolidarityProjectStatus,
  selectCurrentSolidarityProjectError,
  selectSolidarityProjects,
} from '../SolidarityProjectSelectors';
import { KeyFigure, SolidarityProject } from '../SolidarityProjectTypes';
import { stripHtml } from '../SolidarityProjectText';
import SolidarityProjectCard from './SolidarityProjectCard';
import Footer from '../../../components/Footer';

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
  const words = stripHtml(text).split(/\s+/).length;
  return Math.max(1, Math.ceil(words / 200));
};

const renderBody = (description: string): React.ReactNode => {
  const paragraphs = description.split(/\n{2,}/);
  // The drop cap only flatters long-form content; on a one-liner it
  // towers over the text and looks broken.
  const editorial = paragraphs.length > 1 || description.length > 280;
  const classes = editorial ? 'prose-blog prose-blog--editorial' : 'prose-blog';
  if (/<[a-z][\s\S]*>/i.test(description)) {
    return (
      <div
        className={classes}
        dangerouslySetInnerHTML={{ __html: description }}
      />
    );
  }
  return (
    <div className={classes}>
      {paragraphs.map((para, i) => (
        <p key={i}>{para}</p>
      ))}
    </div>
  );
};

const useScrollProgress = (): number => {
  const [progress, setProgress] = useState(0);

  useEffect(() => {
    const compute = () => {
      const h = document.documentElement;
      const scrolled = h.scrollTop || document.body.scrollTop;
      const height = h.scrollHeight - h.clientHeight;
      setProgress(height > 0 ? Math.min(1, Math.max(0, scrolled / height)) : 0);
    };
    compute();
    window.addEventListener('scroll', compute, { passive: true });
    window.addEventListener('resize', compute);
    return () => {
      window.removeEventListener('scroll', compute);
      window.removeEventListener('resize', compute);
    };
  }, []);

  return progress;
};

const ReadingProgressBar: React.FC<{ value: number }> = ({ value }) => (
  <div
    className="fixed top-0 left-0 right-0 h-1 z-50 pointer-events-none"
    aria-hidden="true"
  >
    <div
      className="h-full bg-gradient-to-r from-primary-400 via-primary-500 to-primary-600 transition-[width] duration-150 ease-out shadow-sm shadow-primary-500/30"
      style={{ width: `${value * 100}%` }}
    />
  </div>
);

interface ShareButtonsProps {
  title: string;
  orientation?: 'vertical' | 'horizontal';
}

const ShareButtons: React.FC<ShareButtonsProps> = ({
  title,
  orientation = 'vertical',
}) => {
  const { t } = useTranslation();
  const [copied, setCopied] = useState(false);
  const url = typeof window !== 'undefined' ? window.location.href : '';

  const copy = async () => {
    try {
      await navigator.clipboard.writeText(url);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch {
      /* ignore */
    }
  };

  const twitterUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(
    title
  )}&url=${encodeURIComponent(url)}`;
  const facebookUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(
    url
  )}`;

  const isVertical = orientation === 'vertical';
  const wrapper = isVertical
    ? 'flex flex-col items-center gap-2'
    : 'flex items-center gap-2';
  const button =
    'inline-flex items-center justify-center w-10 h-10 rounded-full text-gray-500 bg-white border border-gray-200 hover:text-primary-600 hover:border-primary-200 hover:bg-primary-50 transition-all';

  return (
    <div className={wrapper}>
      {isVertical && (
        <span className="text-[10px] uppercase tracking-widest text-gray-400 font-semibold mb-1">
          {t('projects.share.label')}
        </span>
      )}
      <a
        href={twitterUrl}
        target="_blank"
        rel="noopener noreferrer"
        className={button}
        aria-label={t('projects.share.twitter')}
      >
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
          <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
        </svg>
      </a>
      <a
        href={facebookUrl}
        target="_blank"
        rel="noopener noreferrer"
        className={button}
        aria-label={t('projects.share.facebook')}
      >
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
          <path d="M9.101 23.691v-7.98H6.627v-3.667h2.474v-1.58c0-4.085 1.848-5.978 5.858-5.978.401 0 .955.042 1.468.103a8.68 8.68 0 0 1 1.141.195v3.325a8.623 8.623 0 0 0-.653-.036 26.805 26.805 0 0 0-.733-.009c-.707 0-1.259.096-1.675.309a1.686 1.686 0 0 0-.679.622c-.258.42-.374.995-.374 1.752v1.297h3.919l-.386 2.103-.287 1.564h-3.246v8.245C19.396 23.238 24 18.179 24 12.044c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.628 3.874 10.35 9.101 11.647Z" />
        </svg>
      </a>
      <button
        type="button"
        onClick={copy}
        className={`${button} relative`}
        aria-label={t('projects.share.copy')}
      >
        {copied ? (
          <svg
            xmlns="http://www.w3.org/2000/svg"
            width="16"
            height="16"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2.5"
            strokeLinecap="round"
            strokeLinejoin="round"
            className="text-success-600"
          >
            <path d="M20 6 9 17l-5-5" />
          </svg>
        ) : (
          <svg
            xmlns="http://www.w3.org/2000/svg"
            width="16"
            height="16"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
          >
            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
          </svg>
        )}
      </button>
      {copied && isVertical && (
        <span className="text-[10px] text-success-600 font-medium mt-1">
          {t('projects.share.copied')}
        </span>
      )}
    </div>
  );
};

const KeyFiguresStrip: React.FC<{ figures: KeyFigure[] }> = ({ figures }) => (
  <dl className="grid grid-cols-2 lg:grid-cols-4 gap-px bg-gray-100 rounded-t-2xl overflow-hidden border-b border-gray-100">
    {figures.map((figure) => (
      <div key={figure.label} className="bg-white px-4 py-6 text-center">
        <dd className="text-2xl sm:text-3xl font-bold text-primary-600 tracking-tight">
          {figure.value}
        </dd>
        <dt className="mt-1.5 text-[11px] uppercase tracking-widest text-gray-500 font-semibold">
          {figure.label}
        </dt>
      </div>
    ))}
  </dl>
);

const ArticleSkeleton: React.FC = () => (
  <div className="animate-pulse">
    <div className="h-[420px] bg-gray-200" />
    <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 -mt-20 relative">
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
  related: SolidarityProject[];
}

const Article: React.FC<ArticleProps> = ({ project, related }) => {
  const { t, i18n } = useTranslation();
  const minutes = readingMinutes(project.description);

  return (
    <article>
      {/* Hero */}
      <header className="relative h-[460px] sm:h-[560px] overflow-hidden bg-gradient-to-br from-primary-100 to-primary-200">
        {project.imageUrl ? (
          <>
            <img
              src={project.imageUrl}
              alt={project.title}
              className="absolute inset-0 h-full w-full object-cover"
            />
            <div className="absolute inset-0 bg-gradient-to-b from-black/40 via-black/20 to-black/80" />
          </>
        ) : (
          <div className="absolute inset-0 flex items-center justify-center">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="96"
              height="96"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              strokeWidth="1.2"
              className="text-primary-300"
            >
              <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
            </svg>
          </div>
        )}

        {/* Back link overlay */}
        <div className="absolute top-6 left-0 right-0">
          <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <Link
              to="/solidarity-projects"
              className="inline-flex items-center gap-2 text-sm font-medium text-white/90 hover:text-white bg-black/30 backdrop-blur-sm rounded-full px-4 py-2 transition-all"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth="2.5"
                strokeLinecap="round"
                strokeLinejoin="round"
              >
                <path d="m12 19-7-7 7-7" />
                <path d="M19 12H5" />
              </svg>
              {t('projects.back')}
            </Link>
          </div>
        </div>

        {/* Title overlay */}
        <div className="absolute bottom-0 left-0 right-0 pb-14 sm:pb-16">
          <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="flex items-center gap-3 mb-5">
              <span className="text-[11px] uppercase tracking-[0.18em] font-semibold text-white/80">
                {t('projects.category')}
              </span>
              <span className="h-px w-8 bg-white/40" />
              <span
                className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold ${
                  project.status === 'active'
                    ? 'bg-success-500/90 text-white'
                    : 'bg-gray-700/80 text-white'
                }`}
              >
                <span
                  className={`w-1.5 h-1.5 rounded-full ${
                    project.status === 'active'
                      ? 'bg-white animate-pulse'
                      : 'bg-gray-300'
                  }`}
                />
                {t(`projects.status.${project.status}`)}
              </span>
            </div>
            <h1 className="text-4xl sm:text-5xl lg:text-6xl font-bold text-white leading-[1.05] tracking-tight drop-shadow-lg max-w-3xl">
              {project.title}
            </h1>
            <div className="mt-5 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-white/80">
              <time dateTime={project.createdAt} className="flex items-center gap-1.5">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="14"
                  height="14"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  strokeWidth="2"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                >
                  <rect width="18" height="18" x="3" y="4" rx="2" />
                  <path d="M16 2v4M8 2v4M3 10h18" />
                </svg>
                {formatDate(project.createdAt, i18n.language)}
              </time>
              <span className="flex items-center gap-1.5">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="14"
                  height="14"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  strokeWidth="2"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                >
                  <circle cx="12" cy="12" r="10" />
                  <polyline points="12 6 12 12 16 14" />
                </svg>
                {t('projects.readingTime', { count: minutes })}
              </span>
              <span className="flex items-center gap-1.5">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="14"
                  height="14"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  strokeWidth="2"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                >
                  <path d="M20 10c0 7-8 12-8 12s-8-5-8-12a8 8 0 0 1 16 0Z" />
                  <circle cx="12" cy="10" r="3" />
                </svg>
                {t('projects.location')}
              </span>
            </div>
          </div>
        </div>
      </header>

      {/* Body */}
      <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 -mt-12 relative">
        <div className="relative bg-white rounded-2xl shadow-xl border border-gray-100">
          {/* Floating share — desktop only. Lives outside the clipped CTA
              footer: an overflow-hidden ancestor would swallow the -left
              offset entirely. */}
          <div className="hidden xl:block absolute -left-20 top-12 bottom-12">
            <div className="sticky top-28">
              <ShareButtons title={project.title} orientation="vertical" />
            </div>
          </div>

          {(project.keyFigures?.length ?? 0) > 0 && (
            <KeyFiguresStrip figures={project.keyFigures!} />
          )}

          <div className="px-6 sm:px-12 lg:px-16 py-12 sm:py-16">
            {renderBody(project.description)}

            {/* Inline share — mobile/tablet */}
            <div className="xl:hidden mt-12 pt-8 border-t border-gray-100 flex items-center justify-between gap-4">
              <span className="text-xs uppercase tracking-widest text-gray-400 font-semibold">
                {t('projects.share.label')}
              </span>
              <ShareButtons title={project.title} orientation="horizontal" />
            </div>
          </div>

          {/* CTA footer */}
          <div className="relative px-6 sm:px-12 lg:px-16 py-10 bg-gradient-to-br from-primary-50 via-white to-primary-100/60 border-t border-gray-100 rounded-b-2xl overflow-hidden">
            <div
              className="absolute -right-10 -top-10 w-40 h-40 rounded-full bg-primary-100/60 blur-3xl"
              aria-hidden="true"
            />
            <div className="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5">
              <div className="flex items-start gap-4">
                <span className="flex-shrink-0 w-12 h-12 rounded-2xl bg-primary-600 text-white flex items-center justify-center shadow-lg shadow-primary-200">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="22"
                    height="22"
                    viewBox="0 0 24 24"
                    fill="currentColor"
                  >
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                  </svg>
                </span>
                <div>
                  <p className="text-lg font-bold text-gray-900 leading-tight">
                    {t('projects.cta.title')}
                  </p>
                  <p className="text-sm text-gray-600 mt-1">
                    {t('projects.cta.subtitle')}
                  </p>
                </div>
              </div>
              <button className="inline-flex items-center justify-center gap-2 rounded-xl text-sm font-semibold text-white bg-primary-600 hover:bg-primary-700 h-12 px-7 transition-all hover:shadow-lg hover:shadow-primary-200 hover:-translate-y-0.5">
                {t('projects.cta.button')}
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="16"
                  height="16"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  strokeWidth="2.5"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                >
                  <path d="M5 12h14" />
                  <path d="m12 5 7 7-7 7" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        {/* Related projects */}
        {related.length > 0 && (
          <section className="mt-20 mb-16">
            <div className="flex items-end justify-between gap-4 mb-8">
              <div>
                <h2 className="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">
                  {t('projects.related.title')}
                </h2>
                <p className="text-gray-500 mt-2">
                  {t('projects.related.subtitle')}
                </p>
              </div>
              <Link
                to="/solidarity-projects"
                className="hidden sm:inline-flex items-center gap-2 text-sm font-semibold text-primary-600 hover:text-primary-700 transition-colors whitespace-nowrap"
              >
                {t('projects.viewAll')}
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="14"
                  height="14"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  strokeWidth="2.5"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                >
                  <path d="M5 12h14" />
                  <path d="m12 5 7 7-7 7" />
                </svg>
              </Link>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {related.map((p) => (
                <SolidarityProjectCard key={p.id} project={p} />
              ))}
            </div>
          </section>
        )}
      </div>
    </article>
  );
};

const SolidarityProjectDetailPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const { i18n } = useTranslation();
  const dispatch = useAppDispatch();
  const project = useAppSelector(selectCurrentSolidarityProject);
  const status = useAppSelector(selectCurrentSolidarityProjectStatus);
  const error = useAppSelector(selectCurrentSolidarityProjectError);
  const allProjects = useAppSelector(selectSolidarityProjects);
  const progress = useScrollProgress();

  // Recharge le projet courant quand l'id ou la langue change.
  useEffect(() => {
    if (id) {
      dispatch(fetchSolidarityProjectById(id));
    }
  }, [dispatch, id, i18n.language]);

  // Recharge les projets liés au premier rendu puis à chaque changement de langue.
  useEffect(() => {
    dispatch(fetchSolidarityProjects());
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [dispatch, i18n.language]);

  useEffect(() => {
    window.scrollTo({ top: 0, behavior: 'auto' });
  }, [id]);

  const related = useMemo(
    () => allProjects.filter((p) => p.id !== id).slice(0, 3),
    [allProjects, id]
  );

  return (
    <div className="min-h-screen bg-gray-50/50 -mt-16 pt-16">
      <ReadingProgressBar value={progress} />
      {status === 'loading' && <ArticleSkeleton />}
      {status === 'failed' && (
        <p className="text-center text-danger-500 py-20">{error}</p>
      )}
      {status === 'succeeded' && project && (
        <Article project={project} related={related} />
      )}
      <Footer />
    </div>
  );
};

export default SolidarityProjectDetailPage;
