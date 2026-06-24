import React, { useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { fetchHostProfile } from '../HostProfileSlice';
import { selectHostProfileByTeamId } from '../HostProfileSelectors';

const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:8080';

interface Props {
  teamId: string | null | undefined;
  /** 'full' for the accommodation page (with bio); 'compact' for the messaging panel. */
  variant?: 'full' | 'compact';
}

const fullName = (firstName: string | null, lastName: string | null): string =>
  [firstName, lastName].filter(Boolean).join(' ').trim();

/**
 * Public host identity (photo + name, plus bio in the full variant), resolved from the
 * owning team. Loads the public host profile on mount; renders nothing until it resolves.
 */
const HostProfileCard: React.FC<Props> = ({ teamId, variant = 'full' }) => {
  const { t } = useTranslation();
  const dispatch = useAppDispatch();
  const host = useAppSelector(selectHostProfileByTeamId(teamId));

  useEffect(() => {
    if (teamId) dispatch(fetchHostProfile(teamId));
  }, [dispatch, teamId]);

  if (!host) return null;

  const name = fullName(host.firstName, host.lastName) || t('host.profile.fallbackName');
  const initial = name.charAt(0).toUpperCase();

  const renderAvatar = (sizeClass: string, textClass: string, ring = '') =>
    host.avatarUrl ? (
      <img
        src={`${API_BASE}${host.avatarUrl}`}
        alt={name}
        className={`${sizeClass} ${ring} rounded-full object-cover`}
      />
    ) : (
      <span
        className={`${sizeClass} ${ring} ${textClass} rounded-full bg-gradient-to-br from-primary-500 to-primary-700 text-white flex items-center justify-center font-semibold`}
      >
        {initial}
      </span>
    );

  if (variant === 'compact') {
    return (
      <div className="flex items-center gap-3">
        {renderAvatar('w-10 h-10', 'text-sm', 'border border-gray-200')}
        <div className="min-w-0">
          <p className="text-[10px] font-semibold uppercase tracking-wider text-gray-400">{t('host.profile.label')}</p>
          <p className="text-sm font-semibold text-gray-900 truncate">{name}</p>
        </div>
      </div>
    );
  }

  return (
    <section className="mt-10">
      <h2 className="text-xl font-bold text-gray-900 mb-4">{t('host.profile.title')}</h2>
      <div className="rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden">
        <div className="bg-gradient-to-br from-primary-50 via-white to-white px-6 pt-6 pb-5">
          <div className="flex items-center gap-5">
            <div className="shrink-0">
              {renderAvatar('w-20 h-20', 'text-2xl', 'ring-4 ring-white shadow-md')}
            </div>
            <div className="min-w-0">
              <p className="text-[11px] font-semibold uppercase tracking-wider text-primary-700">
                {t('host.profile.label')}
              </p>
              <p className="text-2xl font-bold text-gray-900 leading-tight truncate">{name}</p>
              <p className="mt-1 flex items-center gap-1.5 text-sm text-gray-500">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                  <path d="M3 9.5 12 3l9 6.5V21a1 1 0 0 1-1 1h-5v-7h-6v7H4a1 1 0 0 1-1-1z" />
                </svg>
                {t('host.profile.subtitle')}
              </p>
            </div>
          </div>
        </div>
        {host.bio && (
          <div className="px-6 pb-6 pt-1">
            <p className="text-[15px] leading-relaxed text-gray-600 whitespace-pre-line">{host.bio}</p>
          </div>
        )}
      </div>
    </section>
  );
};

export default HostProfileCard;
