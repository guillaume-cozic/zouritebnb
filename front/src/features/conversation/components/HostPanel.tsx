import React from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { Reservation, ReservationStatus } from '../../reservation/ReservationTypes';

interface Props {
  reservation: Reservation;
  locale: string;
  onAccept: () => void;
  onRefuse: () => void;
  busy: boolean;
  readOnly?: boolean;
  /** When set, renders a "cancel reservation" button for the traveler. */
  onCancel?: () => void;
  /** Whether the cancel button should be shown (reservation cancellable + traveler). */
  canCancel?: boolean;
  /** Traveler: open the date-modification dialog. */
  onRequestModification?: () => void;
  /** Whether the traveler may request a date change (confirmed, not started, no pending change). */
  canRequestModification?: boolean;
  /** Host: approve the pending date change. */
  onApproveModification?: () => void;
  /** Host: reject the pending date change. */
  onRejectModification?: () => void;
}

const formatDate = (iso: string, locale: string): string =>
  new Intl.DateTimeFormat(locale, { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(iso));

const statusBadgeClass = (status: ReservationStatus): string => {
  switch (status) {
    case 'pending':
      return 'bg-amber-50 text-amber-700 border-amber-200';
    case 'confirmed':
      return 'bg-emerald-50 text-emerald-700 border-emerald-200';
    case 'refused':
      return 'bg-rose-50 text-rose-700 border-rose-200';
    case 'cancelled':
      return 'bg-gray-50 text-gray-600 border-gray-200';
  }
};

const HostPanel: React.FC<Props> = ({ reservation, locale, onAccept, onRefuse, busy, readOnly = false, onCancel, canCancel = false, onRequestModification, canRequestModification = false, onApproveModification, onRejectModification }) => {
  const { t } = useTranslation();
  const isPending = reservation.status === 'pending';
  const showActions = isPending && !readOnly;
  const showCancel = canCancel && !!onCancel;
  const formatMoney = (amount: number): string =>
    new Intl.NumberFormat(locale, { style: 'currency', currency: 'EUR', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount);

  return (
    <aside className="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
      <div className="px-5 py-4 border-b border-gray-100 bg-gradient-to-br from-primary-50/60 via-white to-white">
        <div className="flex items-center justify-between gap-3">
          <h2 className="text-sm font-semibold text-gray-900">{t('host.panel.title')}</h2>
          <span
            className={`inline-flex items-center text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full border ${statusBadgeClass(reservation.status)}`}
          >
            {t(`admin.reservations.status.${reservation.status}`)}
          </span>
        </div>
      </div>

      <div className="px-5 py-4 space-y-3">
        <div>
          <p className="text-[10px] font-semibold uppercase tracking-wider text-gray-400">{t('host.panel.guest')}</p>
          <p className="text-sm font-medium text-gray-900 mt-0.5">{reservation.guestName}</p>
        </div>

        <div className="grid grid-cols-2 gap-3">
          <div>
            <p className="text-[10px] font-semibold uppercase tracking-wider text-gray-400">{t('host.panel.checkIn')}</p>
            <p className="text-sm text-gray-900 mt-0.5">{formatDate(reservation.checkIn, locale)}</p>
          </div>
          <div>
            <p className="text-[10px] font-semibold uppercase tracking-wider text-gray-400">{t('host.panel.checkOut')}</p>
            <p className="text-sm text-gray-900 mt-0.5">{formatDate(reservation.checkOut, locale)}</p>
          </div>
        </div>

        {typeof (reservation.totalPaid ?? reservation.totalPrice) === 'number' && (
          <div className="pt-3 border-t border-gray-100 flex items-baseline justify-between">
            <p className="text-[10px] font-semibold uppercase tracking-wider text-gray-400">{t('host.panel.total')}</p>
            <p className="text-base font-bold text-gray-900">
              {new Intl.NumberFormat(locale, { style: 'currency', currency: 'EUR', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(
                reservation.totalPaid ?? reservation.totalPrice ?? 0
              )}
            </p>
          </div>
        )}
      </div>

      {reservation.pendingModification && (
        <div className="px-5 pb-4">
          <div className="rounded-xl bg-sky-50 border border-sky-200 px-3 py-2.5 text-xs text-sky-900 space-y-1">
            <p className="font-semibold">{t('modification.pendingTitle')}</p>
            <p>{formatDate(reservation.pendingModification.checkIn, locale)} → {formatDate(reservation.pendingModification.checkOut, locale)}</p>
            <p>
              {t('modification.priceDifference')} :{' '}
              <span className="font-semibold">
                {reservation.pendingModification.priceDifference >= 0 ? '+' : ''}
                {formatMoney(reservation.pendingModification.priceDifference)}
              </span>
            </p>
          </div>
          {onApproveModification && onRejectModification ? (
            <div className="flex flex-col gap-2 mt-3">
              <button
                type="button"
                onClick={onApproveModification}
                disabled={busy}
                className="inline-flex items-center justify-center gap-2 h-10 rounded-xl text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 transition-colors shadow-sm shadow-emerald-200"
              >
                {t('modification.approve')}
              </button>
              <button
                type="button"
                onClick={onRejectModification}
                disabled={busy}
                className="inline-flex items-center justify-center gap-2 h-10 rounded-xl text-sm font-semibold text-rose-700 bg-white border border-rose-200 hover:bg-rose-50 disabled:opacity-60 transition-colors"
              >
                {t('modification.reject')}
              </button>
            </div>
          ) : (
            <p className="text-xs text-surface-500 mt-2">{t('modification.awaitingHost')}</p>
          )}
        </div>
      )}

      {canRequestModification && onRequestModification && !reservation.pendingModification && (
        <div className="px-5 pb-4">
          <button
            type="button"
            onClick={onRequestModification}
            disabled={busy}
            className="w-full inline-flex items-center justify-center gap-2 h-10 rounded-xl text-sm font-semibold text-surface-700 bg-white border border-surface-200 hover:bg-surface-50 disabled:opacity-60 transition-colors"
          >
            {t('modification.requestAction')}
          </button>
        </div>
      )}

      {showActions && (
        <div className="px-5 pb-4">
          <div className="rounded-xl bg-amber-50 border border-amber-200 px-3 py-2.5 text-xs text-amber-800 mb-3 flex items-start gap-2">
            <svg className="flex-shrink-0 mt-0.5" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
              <circle cx="12" cy="12" r="10" />
              <polyline points="12 6 12 12 16 14" />
            </svg>
            <span>{t('host.panel.pendingNotice')}</span>
          </div>
          <div className="flex flex-col gap-2">
            <button
              type="button"
              onClick={onAccept}
              disabled={busy}
              className="inline-flex items-center justify-center gap-2 h-10 rounded-xl text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 transition-colors shadow-sm shadow-emerald-200"
            >
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round">
                <path d="M20 6 9 17l-5-5" />
              </svg>
              {t('host.panel.accept')}
            </button>
            <button
              type="button"
              onClick={onRefuse}
              disabled={busy}
              className="inline-flex items-center justify-center gap-2 h-10 rounded-xl text-sm font-semibold text-rose-700 bg-white border border-rose-200 hover:bg-rose-50 disabled:opacity-60 transition-colors"
            >
              {t('host.panel.refuse')}
            </button>
          </div>
        </div>
      )}

      {showCancel && (
        <div className="px-5 pb-4">
          {typeof reservation.refundAmount === 'number' && (
            <div className="rounded-xl bg-gray-50 border border-gray-200 px-3 py-2.5 text-xs text-gray-600 mb-3">
              {t('conversation.cancel.refundPreview', {
                amount: formatMoney(reservation.refundAmount),
                percent: reservation.refundPercentage ?? 0,
              })}
            </div>
          )}
          <button
            type="button"
            onClick={onCancel}
            disabled={busy}
            className="w-full inline-flex items-center justify-center gap-2 h-10 rounded-xl text-sm font-semibold text-rose-700 bg-white border border-rose-200 hover:bg-rose-50 disabled:opacity-60 transition-colors"
          >
            {t('conversation.cancel.action')}
          </button>
        </div>
      )}

      <div className="px-5 py-3 border-t border-gray-100 bg-gray-50/50">
        <Link
          to={`/hebergements/${reservation.accommodationId}`}
          className="flex items-center justify-center gap-1.5 text-xs font-medium text-primary-600 hover:text-primary-700"
        >
          {t('conversation.viewAccommodation')}
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
            <path d="M7 17 17 7" />
            <path d="M7 7h10v10" />
          </svg>
        </Link>
      </div>
    </aside>
  );
};

export default HostPanel;
