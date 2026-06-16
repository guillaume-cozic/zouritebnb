import React from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { projectExcerpt } from '../../solidarityProject/SolidarityProjectText';
import { FeaturedSolidarityProject } from '../../solidarityProject/useFeaturedSolidarityProject';

const HeartIcon: React.FC<{ className?: string }> = ({ className }) => (
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={className} aria-hidden="true">
    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
  </svg>
);

interface PanelProps {
  featured: FeaturedSolidarityProject;
}

/** Panneau plein écran à droite (desktop). */
export const AuthSolidarityPanel: React.FC<PanelProps> = ({ featured }) => {
  const { t } = useTranslation();
  const { project, loading } = featured;

  return (
    <aside className="relative hidden lg:flex flex-col justify-end overflow-hidden bg-gradient-to-br from-primary-700 to-primary-900">
      {project?.imageUrl && (
        <img
          src={project.imageUrl}
          alt={project.title}
          className="absolute inset-0 h-full w-full object-cover"
        />
      )}
      <div className="absolute inset-0 bg-gradient-to-t from-gray-950/90 via-gray-950/45 to-gray-950/20" aria-hidden="true" />

      <div className="absolute top-10 left-12 right-12">
        <span className="inline-flex items-center gap-2 rounded-full bg-white/15 backdrop-blur px-3.5 py-1.5 text-xs font-semibold text-white ring-1 ring-white/20">
          <HeartIcon />
          {t('auth.solidarity.eyebrow')}
        </span>
        <p className="mt-4 text-white/85 text-sm leading-relaxed max-w-md">
          {t('auth.solidarity.intro')}
        </p>
      </div>

      <div className="relative p-12 text-white">
        {loading && !project ? (
          <div className="animate-pulse space-y-4">
            <div className="h-7 w-2/3 rounded-lg bg-white/20" />
            <div className="h-4 w-full rounded bg-white/15" />
            <div className="h-4 w-4/5 rounded bg-white/15" />
          </div>
        ) : project ? (
          <>
            <h2 className="text-3xl font-bold tracking-tight">{project.title}</h2>
            <p className="mt-3 text-white/80 leading-relaxed max-w-lg line-clamp-3">
              {projectExcerpt(project.description, 200)}
            </p>

            {project.keyFigures && project.keyFigures.length > 0 && (
              <div className="mt-7 flex flex-wrap gap-8">
                {project.keyFigures.slice(0, 3).map((fig) => (
                  <div key={`${fig.label}-${fig.value}`}>
                    <div className="text-2xl font-bold tracking-tight">{fig.value}</div>
                    <div className="text-xs text-white/70 mt-0.5">{fig.label}</div>
                  </div>
                ))}
              </div>
            )}

            <Link
              to={`/solidarity-projects/${project.id}`}
              className="mt-8 inline-flex items-center gap-2 rounded-xl bg-white/95 hover:bg-white text-primary-800 h-11 px-5 text-sm font-semibold transition-colors"
            >
              {t('auth.solidarity.learnMore')}
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                <path d="M5 12h14" />
                <path d="m12 5 7 7-7 7" />
              </svg>
            </Link>
          </>
        ) : (
          <>
            <h2 className="text-3xl font-bold tracking-tight">{t('auth.solidarity.fallbackTitle')}</h2>
            <p className="mt-3 text-white/80 leading-relaxed max-w-lg">{t('auth.solidarity.fallbackText')}</p>
          </>
        )}
      </div>
    </aside>
  );
};

/** Carte compacte affichée au-dessus du formulaire (mobile / tablette). */
export const AuthSolidarityTeaser: React.FC<PanelProps> = ({ featured }) => {
  const { t } = useTranslation();
  const { project } = featured;

  if (!project) {
    return null;
  }

  return (
    <Link
      to={`/solidarity-projects/${project.id}`}
      className="lg:hidden flex items-center gap-4 rounded-2xl border border-primary-100 bg-primary-50/70 p-3 mb-6 hover:bg-primary-50 transition-colors"
    >
      <div className="h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-gradient-to-br from-primary-100 to-primary-200">
        {project.imageUrl && (
          <img src={project.imageUrl} alt={project.title} className="h-full w-full object-cover" />
        )}
      </div>
      <div className="min-w-0">
        <span className="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-primary-700">
          <HeartIcon className="h-3.5 w-3.5" />
          {t('auth.solidarity.eyebrow')}
        </span>
        <p className="mt-0.5 text-sm font-semibold text-gray-900 truncate">{project.title}</p>
      </div>
    </Link>
  );
};
