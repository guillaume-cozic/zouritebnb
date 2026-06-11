import React, { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { fetchReservations } from '../../reservation/ReservationSlice';
import {
  selectReservations,
  selectReservationsStatus,
} from '../../reservation/ReservationSelectors';
import { fetchAllAccommodations } from '../AccommodationManagementSlice';
import { selectManagedAccommodations } from '../AccommodationManagementSelectors';
import { Reservation } from '../../reservation/ReservationTypes';

type Tab = 'today' | 'upcoming' | 'leaving';

const API_BASE = process.env.REACT_APP_API_URL || 'http://localhost:8080';

const startOfDay = (d: Date): Date => {
  const x = new Date(d);
  x.setHours(0, 0, 0, 0);
  return x;
};

const daysBetween = (a: Date, b: Date): number => {
  const ms = startOfDay(b).getTime() - startOfDay(a).getTime();
  return Math.round(ms / 86_400_000);
};

interface Bucket {
  today: Reservation[];
  upcoming: Reservation[];
  leaving: Reservation[];
}

const bucketize = (reservations: Reservation[], today: Date): Bucket => {
  const t0 = startOfDay(today);
  const todayList: Reservation[] = [];
  const upcoming: Reservation[] = [];
  const leaving: Reservation[] = [];

  for (const r of reservations) {
    if (r.status !== 'confirmed') continue;
    const ci = startOfDay(new Date(r.checkIn));
    const co = startOfDay(new Date(r.checkOut));
    if (ci <= t0 && t0 < co) todayList.push(r);
    if (ci > t0 && daysBetween(t0, ci) <= 30) upcoming.push(r);
    if (co.getTime() === t0.getTime()) leaving.push(r);
  }

  todayList.sort((a, b) => new Date(a.checkOut).getTime() - new Date(b.checkOut).getTime());
  upcoming.sort((a, b) => new Date(a.checkIn).getTime() - new Date(b.checkIn).getTime());
  leaving.sort((a, b) => a.guestName.localeCompare(b.guestName));

  return { today: todayList, upcoming, leaving };
};

const formatDateRange = (checkInIso: string, checkOutIso: string, locale: string): string => {
  const a = new Date(checkInIso);
  const b = new Date(checkOutIso);
  const aDay = a.getDate();
  const bDay = b.getDate();
  const sameMonth = a.getMonth() === b.getMonth() && a.getFullYear() === b.getFullYear();
  const monthFmt = new Intl.DateTimeFormat(locale, { month: 'long' });

  if (sameMonth) {
    return `${aDay}–${bDay} ${monthFmt.format(b)}`;
  }
  return `${aDay} ${monthFmt.format(a)}–${bDay} ${monthFmt.format(b)}`;
};

const initials = (name: string): string => {
  const parts = name.trim().split(/\s+/);
  if (parts.length === 0 || '' === parts[0]) return '?';
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
};

const hueFromName = (name: string): number => {
  let hash = 0;
  for (let i = 0; i < name.length; i++) hash = (hash * 31 + name.charCodeAt(i)) | 0;
  return Math.abs(hash) % 360;
};

const absoluteUrl = (url: string | null | undefined): string | null => {
  if (!url) return null;
  if (url.startsWith('http://') || url.startsWith('https://')) return url;
  return `${API_BASE}${url.startsWith('/') ? '' : '/'}${url}`;
};

interface StayCardProps {
  reservation: Reservation;
  accommodationTitle: string;
  accommodationThumbnailUrl: string | null;
  locale: string;
  variant: Tab;
  today: Date;
}

const StayCard: React.FC<StayCardProps> = ({
  reservation,
  accommodationTitle,
  accommodationThumbnailUrl,
  locale,
  variant,
  today,
}) => {
  const { t } = useTranslation();
  const dateRange = formatDateRange(reservation.checkIn, reservation.checkOut, locale);
  const nightsTotal = Math.max(1, daysBetween(new Date(reservation.checkIn), new Date(reservation.checkOut)));
  const hue = hueFromName(reservation.guestName);
  const initial = initials(reservation.guestName);
  const thumbnail = absoluteUrl(accommodationThumbnailUrl);

  let highlight: string | null = null;
  if (variant === 'today') {
    const leavesIn = daysBetween(today, new Date(reservation.checkOut));
    if (leavesIn === 1) highlight = t('hostHome.current.leavingTomorrow');
  } else if (variant === 'upcoming') {
    const inDays = daysBetween(today, new Date(reservation.checkIn));
    if (inDays === 0) highlight = t('hostHome.arriving.today');
    else if (inDays === 1) highlight = t('hostHome.arriving.tomorrow');
    else if (inDays <= 7) highlight = t('hostHome.arriving.inDays', { count: inDays });
  }

  return (
    <article className="group bg-white rounded-3xl border border-gray-100 px-6 sm:px-10 py-10 sm:py-12 text-center hover:border-primary-200 hover:shadow-md hover:-translate-y-0.5 transition-all">
      <p className="text-sm text-gray-500">{dateRange}</p>

      <div className="my-7 sm:my-8 flex justify-center">
        <div className="relative">
          <div
            className="w-20 h-20 sm:w-24 sm:h-24 rounded-full flex items-center justify-center text-white text-2xl sm:text-3xl font-semibold ring-4 ring-white shadow-sm"
            style={{
              background: `linear-gradient(135deg, hsl(${hue}, 65%, 58%), hsl(${(hue + 30) % 360}, 65%, 48%))`,
            }}
          >
            {initial}
          </div>
          {thumbnail && (
            <div className="absolute -bottom-1 -right-2 w-10 h-10 sm:w-11 sm:h-11 rounded-full overflow-hidden ring-4 ring-white shadow-sm bg-gray-100">
              <img
                src={thumbnail}
                alt={accommodationTitle}
                className="w-full h-full object-cover"
                loading="lazy"
              />
            </div>
          )}
        </div>
      </div>

      <h3 className="text-xl sm:text-2xl font-semibold text-gray-900 tracking-tight">
        {reservation.guestName}
      </h3>
      <p className="text-sm text-gray-500 mt-1.5">{accommodationTitle}</p>

      <div className="mt-5 flex items-center justify-center gap-3 text-xs text-gray-400">
        <span>
          {nightsTotal === 1 ? t('hostHome.nightsOne') : t('hostHome.nightsOther', { count: nightsTotal })}
        </span>
        {highlight && (
          <>
            <span className="text-gray-300">•</span>
            <span className="font-medium text-primary-700">{highlight}</span>
          </>
        )}
        <span className="text-gray-300">•</span>
        <Link
          to={`/admin/accommodations/${reservation.accommodationId}/calendar`}
          className="text-primary-600 hover:text-primary-700 font-medium inline-flex items-center gap-0.5"
        >
          {t('hostHome.viewCalendar')}
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
            <path d="M7 17 17 7" />
            <path d="M7 7h10v10" />
          </svg>
        </Link>
      </div>
    </article>
  );
};

interface AccommodationSummary {
  title: string;
  thumbnailUrl: string | null;
}

const HostHomePage: React.FC = () => {
  const { t, i18n } = useTranslation();
  const dispatch = useAppDispatch();

  const reservations = useAppSelector(selectReservations);
  const reservationsStatus = useAppSelector(selectReservationsStatus);
  const accommodations = useAppSelector(selectManagedAccommodations);

  const [tab, setTab] = useState<Tab>('today');

  useEffect(() => {
    dispatch(fetchReservations({}));
    dispatch(fetchAllAccommodations('all'));
  }, [dispatch]);

  const today = useMemo(() => startOfDay(new Date()), []);
  const locale = i18n.language.startsWith('fr') ? 'fr-FR' : 'en-GB';

  const accommodationsById = useMemo(() => {
    const map: Record<string, AccommodationSummary> = {};
    for (const a of accommodations) {
      if (a.id) map[a.id] = { title: a.title ?? '', thumbnailUrl: a.thumbnailUrl };
    }
    return map;
  }, [accommodations]);

  const buckets = useMemo(() => bucketize(reservations, today), [reservations, today]);

  const greeting = useMemo(() => {
    const hour = new Date().getHours();
    if (hour < 6) return t('hostHome.greeting.night');
    if (hour < 12) return t('hostHome.greeting.morning');
    if (hour < 18) return t('hostHome.greeting.afternoon');
    return t('hostHome.greeting.evening');
  }, [t]);

  const todayLabel = new Intl.DateTimeFormat(locale, {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
  }).format(today);

  const tabs: { key: Tab; labelKey: string; count: number }[] = [
    { key: 'today', labelKey: 'hostHome.tabs.today', count: buckets.today.length },
    { key: 'upcoming', labelKey: 'hostHome.tabs.upcoming', count: buckets.upcoming.length },
    { key: 'leaving', labelKey: 'hostHome.tabs.leaving', count: buckets.leaving.length },
  ];

  const list = buckets[tab];

  const emptyKey =
    tab === 'today' ? 'hostHome.current.empty' :
    tab === 'upcoming' ? 'hostHome.arriving.empty' :
    'hostHome.leaving.empty';

  const isLoading = reservationsStatus === 'loading' && reservations.length === 0;

  return (
    <div className="min-h-full">
      <div className="max-w-3xl mx-auto px-4 sm:px-6 py-10">
        <header className="mb-10 text-center relative">
          <div className="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-primary-700">
            <span className="w-1.5 h-1.5 rounded-full bg-primary-500" />
            {todayLabel}
          </div>
          <h1 className="mt-3 text-3xl sm:text-4xl font-bold text-gray-900 tracking-tight">
            {greeting}
          </h1>
          <p className="text-gray-500 mt-2 max-w-xl mx-auto">{t('hostHome.subtitle')}</p>
        </header>

        <div className="mb-8 flex justify-center">
          <div role="tablist" className="inline-flex items-center gap-2 p-1 rounded-full bg-white border border-gray-200 shadow-sm">
            {tabs.map((tb) => (
              <button
                key={tb.key}
                role="tab"
                aria-selected={tab === tb.key}
                onClick={() => setTab(tb.key)}
                className={`relative inline-flex items-center gap-2 h-9 px-5 rounded-full text-sm font-medium transition-all ${
                  tab === tb.key
                    ? 'bg-gray-900 text-white shadow-sm'
                    : 'text-gray-600 hover:text-gray-900'
                }`}
              >
                {t(tb.labelKey)}
                {tb.count > 0 && (
                  <span
                    className={`inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-[11px] font-semibold ${
                      tab === tb.key ? 'bg-white/20 text-white' : 'bg-primary-50 text-primary-700'
                    }`}
                  >
                    {tb.count}
                  </span>
                )}
              </button>
            ))}
          </div>
        </div>

        {isLoading ? (
          <div className="space-y-6">
            {[1, 2].map((i) => (
              <div key={i} className="h-72 rounded-3xl bg-white border border-gray-100 animate-pulse" />
            ))}
          </div>
        ) : list.length === 0 ? (
          <div className="rounded-3xl bg-white border border-dashed border-gray-200 px-6 py-20 text-center">
            <div className="mx-auto w-14 h-14 rounded-2xl bg-primary-50 flex items-center justify-center mb-4">
              <svg className="text-primary-500" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
                <path d="M3 9.5 12 3l9 6.5V21a1 1 0 0 1-1 1h-5v-7h-6v7H4a1 1 0 0 1-1-1z" />
              </svg>
            </div>
            <p className="text-gray-500 max-w-sm mx-auto">{t(emptyKey)}</p>
          </div>
        ) : (
          <div className="space-y-6">
            {list.map((r) => {
              const acc = accommodationsById[r.accommodationId];
              return (
                <StayCard
                  key={r.id}
                  reservation={r}
                  accommodationTitle={acc?.title ?? ''}
                  accommodationThumbnailUrl={acc?.thumbnailUrl ?? null}
                  locale={locale}
                  variant={tab}
                  today={today}
                />
              );
            })}
          </div>
        )}
      </div>
    </div>
  );
};

export default HostHomePage;
