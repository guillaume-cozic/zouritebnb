import React from 'react';
import { Link, Navigate, useLocation } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import Footer from '../../../components/Footer';

interface ReservationSuccessState {
  accommodationTitle?: string;
  accommodationCity?: string;
  checkIn?: string;
  checkOut?: string;
  guests?: number;
  totalLabel?: string;
}

const ReservationSuccessPage: React.FC = () => {
  const { t, i18n } = useTranslation();
  const location = useLocation();
  const state = (location.state ?? null) as ReservationSuccessState | null;

  // The page only makes sense right after a successful request, which always
  // carries navigation state. A direct hit (refresh, deep link) has none.
  if (!state) {
    return <Navigate to="/account/conversations" replace />;
  }

  const locale = i18n.language.startsWith('fr') ? 'fr-FR' : 'en-GB';
  const formatDate = (s?: string) => {
    if (!s) return null;
    const d = new Date(s);
    if (isNaN(d.getTime())) return null;
    return new Intl.DateTimeFormat(locale, {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    }).format(d);
  };

  const checkInLabel = formatDate(state.checkIn);
  const checkOutLabel = formatDate(state.checkOut);

  return (
    <div className="min-h-[calc(100vh-4rem)] flex flex-col bg-gradient-to-b from-primary-50/30 via-white to-white">
      <div className="flex-1 w-full max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div className="text-center">
          <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 text-white shadow-lg shadow-emerald-200 mb-6">
            <svg
              width="30"
              height="30"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              strokeWidth="2.5"
              strokeLinecap="round"
              strokeLinejoin="round"
            >
              <path d="M20 6 9 17l-5-5" />
            </svg>
          </div>
          <div className="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-emerald-700">
            <span className="w-1.5 h-1.5 rounded-full bg-emerald-500" />
            {t('confirm.success.eyebrow')}
          </div>
          <h1 className="mt-2 text-3xl font-bold text-gray-900 tracking-tight">
            {t('confirm.success.title')}
          </h1>
          <p className="text-gray-500 mt-2">{t('confirm.success.subtitle')}</p>
        </div>

        <div className="mt-8 bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
          <h2 className="text-base font-semibold text-gray-900 mb-4">
            {t('confirm.success.summaryTitle')}
          </h2>
          <dl className="space-y-3 text-sm">
            {state.accommodationTitle && (
              <div className="flex justify-between gap-4">
                <dt className="text-gray-500">{t('confirm.tripDetails')}</dt>
                <dd className="text-gray-900 font-medium text-right">
                  {state.accommodationTitle}
                  {state.accommodationCity ? ` · ${state.accommodationCity}` : ''}
                </dd>
              </div>
            )}
            {checkInLabel && checkOutLabel && (
              <div className="flex justify-between gap-4">
                <dt className="text-gray-500">{t('confirm.dates')}</dt>
                <dd className="text-gray-900 font-medium text-right">
                  {checkInLabel} → {checkOutLabel}
                </dd>
              </div>
            )}
            {state.guests != null && (
              <div className="flex justify-between gap-4">
                <dt className="text-gray-500">{t('hero.guests')}</dt>
                <dd className="text-gray-900 font-medium text-right">{state.guests}</dd>
              </div>
            )}
            {state.totalLabel && (
              <div className="flex justify-between gap-4 pt-3 border-t border-gray-100">
                <dt className="text-gray-900 font-semibold">{t('detail.total')}</dt>
                <dd className="text-gray-900 font-bold text-right">{state.totalLabel}</dd>
              </div>
            )}
          </dl>
        </div>

        <div className="mt-5 rounded-xl bg-primary-50 border border-primary-100 px-4 py-3 text-sm text-primary-800 flex items-start gap-2">
          <svg
            className="flex-shrink-0 mt-0.5"
            width="16"
            height="16"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
          >
            <circle cx="12" cy="12" r="10" />
            <path d="M12 6v6l4 2" />
          </svg>
          <span>{t('confirm.success.hostDelay')}</span>
        </div>

        <div className="mt-8 flex flex-col sm:flex-row justify-center gap-3">
          <Link
            to="/account/conversations"
            className="inline-flex items-center justify-center h-11 px-6 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 shadow-sm shadow-primary-200 transition-all"
          >
            {t('confirm.success.viewConversations')}
          </Link>
          <Link
            to="/accommodations"
            className="inline-flex items-center justify-center h-11 px-6 rounded-xl border border-gray-200 text-sm font-medium text-gray-700 hover:bg-gray-50"
          >
            {t('confirm.success.browseMore')}
          </Link>
        </div>
      </div>
      <Footer />
    </div>
  );
};

export default ReservationSuccessPage;
