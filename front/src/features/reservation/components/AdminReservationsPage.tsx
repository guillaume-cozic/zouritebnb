import React, { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { fetchReservations } from '../ReservationSlice';
import {
  selectReservations,
  selectReservationsStatus,
  selectReservationsError,
} from '../ReservationSelectors';
import { fetchConversationsForTeam } from '../../conversation/ConversationSlice';
import { selectConversations } from '../../conversation/ConversationSelectors';
import { selectAuthTeamId } from '../../auth/AuthSelectors';
import { Reservation, ReservationStatus } from '../ReservationTypes';

const STATUS_FILTERS: Array<{ key: 'all' | ReservationStatus; labelKey: string }> = [
  { key: 'all', labelKey: 'admin.reservations.filter.all' },
  { key: 'pending', labelKey: 'admin.reservations.filter.pending' },
  { key: 'confirmed', labelKey: 'admin.reservations.filter.confirmed' },
  { key: 'refused', labelKey: 'admin.reservations.filter.refused' },
  { key: 'cancelled', labelKey: 'admin.reservations.filter.cancelled' },
];

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

const formatDate = (iso: string, locale: string): string =>
  new Intl.DateTimeFormat(locale, { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(iso));

const AdminReservationsPage: React.FC = () => {
  const { t, i18n } = useTranslation();
  const dispatch = useAppDispatch();
  const teamId = useAppSelector(selectAuthTeamId);
  const reservations = useAppSelector(selectReservations);
  const status = useAppSelector(selectReservationsStatus);
  const error = useAppSelector(selectReservationsError);
  const conversations = useAppSelector(selectConversations);

  const [statusFilter, setStatusFilter] = useState<'all' | ReservationStatus>('all');

  useEffect(() => {
    dispatch(fetchReservations({}));
    if (teamId) {
      dispatch(fetchConversationsForTeam(teamId));
    }
  }, [dispatch, teamId]);

  const conversationByReservation = useMemo(() => {
    const map = new Map<string, string>();
    for (const c of conversations) {
      map.set(c.reservationId, c.id);
    }
    return map;
  }, [conversations]);

  const filtered = useMemo(() => {
    const sorted = [...reservations].sort((a, b) => b.checkIn.localeCompare(a.checkIn));
    if (statusFilter === 'all') return sorted;
    return sorted.filter((r) => r.status === statusFilter);
  }, [reservations, statusFilter]);

  const pendingCount = reservations.filter((r) => r.status === 'pending').length;
  const locale = i18n.language.startsWith('fr') ? 'fr-FR' : 'en-GB';

  return (
    <div className="px-6 sm:px-10 py-8 w-full">
      <header className="mb-6 flex items-end justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{t('admin.reservations.title')}</h1>
          <p className="text-gray-500 mt-1 text-sm">{t('admin.reservations.subtitle')}</p>
        </div>
        {pendingCount > 0 && (
          <span className="inline-flex items-center gap-1.5 text-sm font-semibold text-amber-700 bg-amber-50 border border-amber-200 px-3 py-1.5 rounded-full">
            <span className="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse" />
            {t('admin.reservations.pendingBadge', { count: pendingCount })}
          </span>
        )}
      </header>

      <div className="flex flex-wrap items-center gap-2 mb-5">
        {STATUS_FILTERS.map((f) => {
          const active = statusFilter === f.key;
          const count = f.key === 'all' ? reservations.length : reservations.filter((r) => r.status === f.key).length;
          return (
            <button
              key={f.key}
              type="button"
              onClick={() => setStatusFilter(f.key)}
              className={`inline-flex items-center gap-1.5 px-3 py-1.5 text-sm rounded-full border transition-colors ${
                active
                  ? 'bg-blue-600 border-blue-600 text-white'
                  : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'
              }`}
            >
              {t(f.labelKey)}
              <span className={`text-xs ${active ? 'text-blue-100' : 'text-gray-400'}`}>{count}</span>
            </button>
          );
        })}
      </div>

      <div className="bg-white rounded-2xl border border-gray-100 overflow-hidden">
        {status === 'loading' && (
          <div className="px-6 py-10 text-center text-gray-400 text-sm">{t('admin.reservations.loading')}</div>
        )}
        {status === 'failed' && (
          <div className="px-6 py-10 text-center text-red-600 text-sm">{error}</div>
        )}
        {status === 'succeeded' && filtered.length === 0 && (
          <div className="px-6 py-16 text-center text-gray-400 text-sm">{t('admin.reservations.empty')}</div>
        )}
        {filtered.length > 0 && (
          <ul className="divide-y divide-gray-100">
            {filtered.map((r: Reservation) => {
              const conversationId = conversationByReservation.get(r.id);
              const row = (
                <div className="px-5 py-4 flex flex-col sm:flex-row sm:items-center gap-3 hover:bg-gray-50/70 transition-colors">
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2 flex-wrap">
                      <span className="font-semibold text-gray-900">{r.guestName}</span>
                      <span
                        className={`inline-flex items-center text-[11px] font-semibold uppercase tracking-wider px-2 py-0.5 rounded-full border ${statusBadgeClass(r.status)}`}
                      >
                        {t(`admin.reservations.status.${r.status}`)}
                      </span>
                    </div>
                    <p className="text-sm text-gray-500 mt-0.5">
                      {formatDate(r.checkIn, locale)} → {formatDate(r.checkOut, locale)}
                    </p>
                  </div>
                  <div className="text-right text-sm">
                    {typeof r.totalPrice === 'number' && (
                      <div className="font-semibold text-gray-900">
                        {new Intl.NumberFormat(locale, { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(r.totalPrice)}
                      </div>
                    )}
                    {conversationId && (
                      <div className="text-xs text-blue-600 mt-0.5">{t('admin.reservations.openConversation')} →</div>
                    )}
                  </div>
                </div>
              );
              return (
                <li key={r.id}>
                  {conversationId ? (
                    <Link to={`/admin/conversations/${conversationId}`}>{row}</Link>
                  ) : (
                    row
                  )}
                </li>
              );
            })}
          </ul>
        )}
      </div>
    </div>
  );
};

export default AdminReservationsPage;
