import React, { useEffect, useMemo } from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { fetchReservations } from '../../reservation/ReservationSlice';
import { selectReservations, selectReservationsStatus } from '../../reservation/ReservationSelectors';
import { fetchAccommodationSummary } from '../../accommodation/AccommodationSummarySlice';
import { selectAccommodationSummaries } from '../../accommodation/AccommodationSummarySelectors';
import { fetchConversationsForUser } from '../../conversation/ConversationSlice';
import { selectConversations } from '../../conversation/ConversationSelectors';
import { selectAuthUser } from '../../auth/AuthSelectors';
import { Reservation } from '../../reservation/ReservationTypes';

const startOfDay = (d: Date): Date => {
  const x = new Date(d);
  x.setHours(0, 0, 0, 0);
  return x;
};

const daysBetween = (a: Date, b: Date): number =>
  Math.round((startOfDay(b).getTime() - startOfDay(a).getTime()) / 86_400_000);

const nightsOf = (r: Reservation): number =>
  Math.max(1, daysBetween(new Date(r.checkIn), new Date(r.checkOut)));

const TravelerHomePage: React.FC = () => {
  const { t, i18n } = useTranslation();
  const dispatch = useAppDispatch();
  const user = useAppSelector(selectAuthUser);
  const reservations = useAppSelector(selectReservations);
  const status = useAppSelector(selectReservationsStatus);
  const summaries = useAppSelector(selectAccommodationSummaries);
  const conversations = useAppSelector(selectConversations);

  const locale = i18n.language.startsWith('fr') ? 'fr-FR' : 'en-GB';

  useEffect(() => {
    dispatch(fetchReservations({}));
    dispatch(fetchConversationsForUser());
  }, [dispatch]);

  // Resolve the accommodation label (name + city) for each reservation.
  useEffect(() => {
    const ids = new Set(reservations.map((r) => r.accommodationId));
    ids.forEach((id) => dispatch(fetchAccommodationSummary(id)));
  }, [dispatch, reservations]);

  const conversationByReservation = useMemo(() => {
    const map: Record<string, string> = {};
    for (const c of conversations) map[c.reservationId] = c.id;
    return map;
  }, [conversations]);

  const { upcoming, past } = useMemo(() => {
    const today = startOfDay(new Date());
    const up: Reservation[] = [];
    const pa: Reservation[] = [];
    for (const r of reservations) {
      if (r.status === 'cancelled' || r.status === 'refused') continue;
      const checkOut = startOfDay(new Date(r.checkOut));
      if (checkOut >= today) up.push(r);
      else if (r.status === 'confirmed') pa.push(r);
    }
    up.sort((a, b) => new Date(a.checkIn).getTime() - new Date(b.checkIn).getTime());
    pa.sort((a, b) => new Date(b.checkIn).getTime() - new Date(a.checkIn).getTime());
    return { upcoming: up, past: pa };
  }, [reservations]);

  const formatRange = (r: Reservation): string => {
    const fmt = new Intl.DateTimeFormat(locale, { day: '2-digit', month: 'short', year: 'numeric' });
    return `${fmt.format(new Date(r.checkIn))} → ${fmt.format(new Date(r.checkOut))}`;
  };

  const countdownLabel = (r: Reservation): string => {
    const today = startOfDay(new Date());
    const checkIn = startOfDay(new Date(r.checkIn));
    if (checkIn <= today) return t('travelerHome.inProgress');
    const days = daysBetween(today, checkIn);
    if (days === 0) return t('travelerHome.startsToday');
    return t('travelerHome.inDays', { count: days });
  };

  const greetingName = user?.firstName || user?.email?.split('@')[0] || '';

  return (
    <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      <header className="mb-8 relative">
        <div className="absolute -left-4 top-0 bottom-2 w-1 bg-gradient-to-b from-primary-500 via-primary-400 to-transparent rounded-full" aria-hidden="true" />
        <div className="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-primary-700">
          <span className="w-1.5 h-1.5 rounded-full bg-primary-500" />
          {t('backoffice.menu.travelerTitle')}
        </div>
        <h1 className="mt-2 text-3xl font-bold text-gray-900 tracking-tight">
          {greetingName ? t('travelerHome.greetingNamed', { name: greetingName }) : t('travelerHome.greeting')}
        </h1>
        <p className="text-gray-500 mt-1">{t('travelerHome.subtitle')}</p>
      </header>

      <section>
        <h2 className="text-lg font-bold text-gray-900 mb-4">{t('travelerHome.upcomingTitle')}</h2>

        {status === 'loading' && upcoming.length === 0 && (
          <div className="space-y-3">
            {[1, 2].map((i) => (
              <div key={i} className="h-28 rounded-2xl bg-gray-100 animate-pulse" />
            ))}
          </div>
        )}

        {status !== 'loading' && upcoming.length === 0 && (
          <div className="rounded-2xl border border-dashed border-gray-200 bg-white px-6 py-12 text-center">
            <div className="mx-auto w-14 h-14 rounded-2xl bg-primary-50 flex items-center justify-center mb-4">
              <svg className="text-primary-500" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
                <path d="M3 7v11a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7" />
                <path d="m3 7 9 6 9-6" />
                <path d="M3 7l9-4 9 4" />
              </svg>
            </div>
            <h3 className="text-base font-semibold text-gray-900">{t('travelerHome.emptyTitle')}</h3>
            <p className="text-sm text-gray-500 mt-1 mb-5">{t('travelerHome.emptyText')}</p>
            <Link
              to="/accommodations"
              className="inline-flex items-center justify-center h-10 px-5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 shadow-sm shadow-primary-200 transition-all"
            >
              {t('travelerHome.browse')}
            </Link>
          </div>
        )}

        <div className="space-y-3">
          {upcoming.map((r) => {
            const summary = summaries[r.accommodationId];
            const conversationId = conversationByReservation[r.id];
            return (
              <article
                key={r.id}
                className="rounded-2xl border border-gray-100 bg-white shadow-sm p-5 flex flex-col sm:flex-row sm:items-center gap-4"
              >
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2 flex-wrap">
                    <h3 className="text-base font-semibold text-gray-900 truncate">
                      {summary?.title ?? t('travelerHome.accommodationFallback')}
                    </h3>
                    <span
                      className={`inline-flex items-center text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full border ${
                        r.status === 'confirmed'
                          ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                          : 'bg-amber-50 text-amber-700 border-amber-200'
                      }`}
                    >
                      {t(`admin.reservations.status.${r.status}`)}
                    </span>
                  </div>
                  {summary?.city && (
                    <p className="flex items-center gap-1 text-xs text-gray-500 mt-1">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" />
                        <circle cx="12" cy="10" r="3" />
                      </svg>
                      {summary.city}
                    </p>
                  )}
                  <p className="text-sm text-gray-700 mt-2">
                    {formatRange(r)}
                    <span className="text-gray-400"> · {t('travelerHome.nights', { count: nightsOf(r) })}</span>
                  </p>
                </div>

                <div className="flex sm:flex-col items-start sm:items-end gap-2 sm:gap-3 sm:text-right">
                  <span className="inline-flex items-center h-7 px-3 rounded-full text-xs font-semibold bg-primary-50 text-primary-700">
                    {countdownLabel(r)}
                  </span>
                  <div className="flex items-center gap-3">
                    <Link to={`/hebergements/${r.accommodationId}`} className="text-xs font-medium text-gray-500 hover:text-primary-700">
                      {t('travelerHome.viewAccommodation')}
                    </Link>
                    <Link
                      to={conversationId ? `/account/conversations/${conversationId}` : '/account/conversations'}
                      className="text-xs font-semibold text-primary-600 hover:text-primary-700"
                    >
                      {t('travelerHome.viewConversation')}
                    </Link>
                  </div>
                </div>
              </article>
            );
          })}
        </div>
      </section>

      {past.length > 0 && (
        <section className="mt-10">
          <h2 className="text-lg font-bold text-gray-900 mb-4">{t('travelerHome.pastTitle')}</h2>
          <div className="space-y-2">
            {past.map((r) => {
              const summary = summaries[r.accommodationId];
              const conversationId = conversationByReservation[r.id];
              return (
                <Link
                  key={r.id}
                  to={conversationId ? `/account/conversations/${conversationId}` : '/account/conversations'}
                  className="flex items-center justify-between gap-3 rounded-xl border border-gray-100 bg-white px-4 py-3 hover:bg-gray-50 transition-colors"
                >
                  <div className="min-w-0">
                    <p className="text-sm font-medium text-gray-900 truncate">
                      {summary?.title ?? t('travelerHome.accommodationFallback')}
                      {summary?.city ? <span className="text-gray-400 font-normal"> · {summary.city}</span> : null}
                    </p>
                    <p className="text-xs text-gray-500 mt-0.5">{formatRange(r)}</p>
                  </div>
                  <svg className="flex-shrink-0 text-gray-300" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="m9 18 6-6-6-6" />
                  </svg>
                </Link>
              );
            })}
          </div>
        </section>
      )}
    </div>
  );
};

export default TravelerHomePage;
