import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { resendVerificationEmail } from '../AuthSlice';
import { selectAuthUser } from '../AuthSelectors';

/**
 * Non-blocking reminder shown to authenticated users whose email is not yet confirmed.
 * Only rendered when emailVerified is explicitly false — a missing flag (stale session
 * from before the feature) is treated as "unknown" and stays silent until next login.
 */
const EmailVerificationBanner: React.FC = () => {
  const { t } = useTranslation();
  const dispatch = useAppDispatch();
  const user = useAppSelector(selectAuthUser);
  const [state, setState] = useState<'idle' | 'sending' | 'sent'>('idle');

  if (!user || user.emailVerified !== false) {
    return null;
  }

  const handleResend = async () => {
    setState('sending');
    await dispatch(resendVerificationEmail());
    setState('sent');
  };

  return (
    <div className="bg-amber-50 border-b border-amber-200">
      <div className="max-w-6xl mx-auto px-4 py-2.5 flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-sm text-amber-900">
        <span className="inline-flex items-center gap-2">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="shrink-0">
            <rect width="20" height="16" x="2" y="4" rx="2" />
            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
          </svg>
          {t('auth.emailVerification.bannerText')}
        </span>
        {state === 'sent' ? (
          <span className="font-medium text-emerald-700">{t('auth.emailVerification.resent')}</span>
        ) : (
          <button
            type="button"
            onClick={handleResend}
            disabled={state === 'sending'}
            className="font-semibold underline underline-offset-2 hover:text-amber-700 disabled:opacity-60"
          >
            {state === 'sending' ? t('auth.loading') : t('auth.emailVerification.resend')}
          </button>
        )}
      </div>
    </div>
  );
};

export default EmailVerificationBanner;
