import React from 'react';
import { useTranslation } from 'react-i18next';
import { Spinner } from './Spinner';

export type SaveIndicatorStatus = 'idle' | 'saving' | 'saved' | 'error';

/**
 * Unified auto-save badge, driven by the per-section/per-form save states held
 * in the Redux store. Replaces the page-local SaveStatusBadge / SaveIndicator.
 */
export const SaveIndicator: React.FC<{ status: SaveIndicatorStatus }> = ({ status }) => {
  const { t } = useTranslation();

  if (status === 'idle') return null;

  const config = {
    saving: {
      className: 'bg-primary-50 text-primary-600 border-primary-100',
      icon: <Spinner />,
      label: t('autosave.saving'),
    },
    saved: {
      className: 'bg-success-50 text-success-600 border-success-100',
      icon: (
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5" /></svg>
      ),
      label: t('autosave.saved'),
    },
    error: {
      className: 'bg-danger-50 text-danger-600 border-danger-100',
      icon: (
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" aria-hidden="true"><circle cx="12" cy="12" r="10" /><path d="m15 9-6 6" /><path d="m9 9 6 6" /></svg>
      ),
      label: t('autosave.error'),
    },
  }[status];

  return (
    <span
      role="status"
      className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border transition-all ${config.className}`}
    >
      {config.icon}
      {config.label}
    </span>
  );
};
