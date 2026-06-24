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
  const avatar = host.avatarUrl ? (
    <img
      src={`${API_BASE}${host.avatarUrl}`}
      alt={name}
      className={`rounded-full object-cover border border-gray-200 ${variant === 'full' ? 'w-16 h-16' : 'w-10 h-10'}`}
    />
  ) : (
    <span
      className={`rounded-full bg-primary-600 text-white flex items-center justify-center font-semibold ${variant === 'full' ? 'w-16 h-16 text-xl' : 'w-10 h-10 text-sm'}`}
    >
      {initial}
    </span>
  );

  if (variant === 'compact') {
    return (
      <div className="flex items-center gap-3">
        {avatar}
        <div className="min-w-0">
          <p className="text-[10px] font-semibold uppercase tracking-wider text-gray-400">{t('host.profile.label')}</p>
          <p className="text-sm font-semibold text-gray-900 truncate">{name}</p>
        </div>
      </div>
    );
  }

  return (
    <section className="border-t border-gray-100 pt-8 mt-8">
      <h2 className="text-xl font-bold text-gray-900 mb-4">{t('host.profile.title')}</h2>
      <div className="flex items-start gap-4">
        {avatar}
        <div className="min-w-0">
          <p className="text-base font-semibold text-gray-900">{name}</p>
          {host.bio && <p className="text-sm text-gray-600 mt-2 whitespace-pre-line">{host.bio}</p>}
        </div>
      </div>
    </section>
  );
};

export default HostProfileCard;
