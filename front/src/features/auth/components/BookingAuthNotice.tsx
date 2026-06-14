import React from 'react';
import { useTranslation } from 'react-i18next';

interface BookingAuthNoticeProps {
  mode: 'login' | 'register';
}

/**
 * Contextual banner shown on the login/register pages when the user was sent there
 * while trying to book: it explains an account is required to confirm the booking and
 * pay, and reassures that nothing is charged until the host accepts.
 */
const BookingAuthNotice: React.FC<BookingAuthNoticeProps> = ({ mode }) => {
  const { t } = useTranslation();

  return (
    <div className="mb-6 rounded-2xl border border-primary-200 bg-primary-50/70 p-4 text-left">
      <div className="flex gap-3">
        <span className="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary-100 text-primary-700">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
            <path d="M5 13l4 4L19 7" />
          </svg>
        </span>
        <div>
          <p className="text-sm font-semibold text-gray-900">{t('auth.bookingContext.title')}</p>
          <p className="mt-1 text-sm text-gray-600">{t(`auth.bookingContext.${mode}`)}</p>
          <p className="mt-2 text-xs text-gray-500">{t('auth.bookingContext.reassurance')}</p>
        </div>
      </div>
    </div>
  );
};

export default BookingAuthNotice;
