import React from 'react';
import { useTranslation } from 'react-i18next';
import { VerificationStatus } from '../UserProfileTypes';

interface VerificationBadgeProps {
  status: VerificationStatus;
  /** Compact variant: icon only with a tooltip (used in the navbar). */
  compact?: boolean;
}

const STYLES: Record<VerificationStatus, string> = {
  verified: 'bg-green-50 text-green-700 border-green-200',
  pending: 'bg-amber-50 text-amber-700 border-amber-200',
  rejected: 'bg-red-50 text-red-700 border-red-200',
  not_started: 'bg-gray-50 text-gray-600 border-gray-200',
};

const Icon: React.FC<{ status: VerificationStatus }> = ({ status }) => {
  if (status === 'verified') {
    return (
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
        <path d="M20 6 9 17l-5-5" />
      </svg>
    );
  }
  if (status === 'rejected') {
    return (
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
        <path d="M18 6 6 18" /><path d="m6 6 12 12" />
      </svg>
    );
  }
  return (
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
      <circle cx="12" cy="12" r="10" /><path d="M12 8v4" /><path d="M12 16h.01" />
    </svg>
  );
};

const VerificationBadge: React.FC<VerificationBadgeProps> = ({ status, compact = false }) => {
  const { t } = useTranslation();
  const label = t(`userProfile.verification.status.${status}`);

  if (compact) {
    return (
      <span
        className={`inline-flex items-center justify-center w-5 h-5 rounded-full border ${STYLES[status]}`}
        title={label as string}
        aria-label={label as string}
      >
        <Icon status={status} />
      </span>
    );
  }

  return (
    <span className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border text-xs font-semibold ${STYLES[status]}`}>
      <Icon status={status} />
      {label}
    </span>
  );
};

export default VerificationBadge;
