import React, { useEffect, useMemo } from 'react';
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
  current: Reservation[];
  arriving: Reservation[];
  leavingToday: Reservation[];
}

const bucketize = (reservations: Reservation[], today: Date): Bucket => {
  const t0 = startOfDay(today);
  const current: Reservation[] = [];
  const arriving: Reservation[] = [];
  const leavingToday: Reservation[] = [];

  for (const r of reservations) {
    if (r.status !== 'confirmed') continue;
    const ci = startOfDay(new Date(r.checkIn));
    const co = startOfDay(new Date(r.checkOut));
    if (ci <= t0 && t0 < co) current.push(r);
    if (ci > t0 && daysBetween(t0, ci) <= 14) arriving.push(r);
    if (co.getTime() === t0.getTime()) leavingToday.push(r);
  }

  current.sort((a, b) => new Date(a.checkOut).getTime() - new Date(b.checkOut).getTime());
  arriving.sort((a, b) => new Date(a.checkIn).getTime() - new Date(b.checkIn).getTime());
  leavingToday.sort((a, b) => a.guestName.localeCompare(b.guestName));

  return { current, arriving, leavingToday };
};

interface CardProps {
  reservation: Reservation;
  accommodationTitle: string;
  locale: string;
  variant: 'current' | 'arriving' | 'leaving';
  today: Date;
}

const formatDate = (iso: string, locale: string): string =>
  new Intl.DateTimeFormat(locale, { weekday: 'short', day: '2-digit', month: 'short' }).format(new Date(iso));

const StayCard: React.FC<CardProps> = ({ reservation, accommodationTitle, locale, variant, today }) => {
  const { t } = useTranslation();
  const checkIn = new Date(reservation.checkIn);
  const checkOut = new Date(reservation.checkOut);
  const totalNights = Math.max(1, daysBetween(checkIn, checkOut));

  let highlight = '';
  if (variant === 'current') {
    const nightsDone = daysBetween(checkIn, today) + 1;
    const leavesIn = daysBetween(today, checkOut);
    highlight =
      leavesIn === 1
        ? t('hostHome.current.leavingTomorrow')
        : t('hostHome.current.dayCount', { day: nightsDone, total: totalNights });
  } else if (variant === 'arriving') {
    const inDays = daysBetween(today, checkIn);
    highlight =
      inDays === 0
        ? t('hostHome.arriving.today')
        : inDays === 1
          ? t('hostHome.arriving.tomorrow')
          : t('hostHome.arriving.inDays', { count: inDays });
  } else {
    highlight = t('hostHome.leaving.today');
  }

  const palette = {
    current: {
      ring: 'ring-emerald-200/60',
      bg: 'bg-gradient-to-br from-emerald-50 via-white to-white',
      pill: 'bg-emerald-100 text-emerald-800',
      ribbon: 'from-emerald-400 to-teal-500',
    },
    arriving: {
      ring: 'ring-amber-200/60',
      bg: 'bg-gradient-to-br from-amber-50 via-white to-white',
      pill: 'bg-amber-100 text-amber-800',
      ribbon: 'from-amber-400 to-orange-500',
    },
    leaving: {
      ring: 'ring-sky-200/60',
      bg: 'bg-gradient-to-br from-sky-50 via-white to-white',
      pill: 'bg-sky-100 text-sky-800',
      ribbon: 'from-sky-400 to-blue-500',
    },
  }[variant];

  const initial = reservation.guestName.trim().charAt(0).toUpperCase() || '?';

  return (
    <article
      className={`relative overflow-hidden rounded-2xl bg-white ring-1 ${palette.ring} shadow-sm hover:shadow-md transition-shadow`}
    >
      <div className={`h-1.5 bg-gradient-to-r ${palette.ribbon}`} />
      <div className={`p-5 ${palette.bg}`}>
        <div className="flex items-start gap-4">
          <div
            className={`flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center text-lg font-bold ${palette.pill}`}
          >
            {initial}
          </div>
          <div className="flex-1 min-w-0">
            <h3 className="text-base font-semibold text-gray-900 truncate">
              {reservation.guestName}
            </h3>
            <p className="text-xs text-gray-500 truncate mt-0.5">{accommodationTitle}</p>
          </div>
          <span className={`flex-shrink-0 text-[11px] font-semibold uppercase tracking-wide px-2.5 py-1 rounded-full ${palette.pill}`}>
            {highlight}
          </span>
        </div>

        <div className="mt-4 flex items-center gap-3 text-xs text-gray-600">
          <div className="flex items-center gap-1.5">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-gray-400">
              <path d="M5 12h14" />
              <path d="m12 5 7 7-7 7" />
            </svg>
            <span>{formatDate(reservation.checkIn, locale)}</span>
          </div>
          <span className="text-gray-300">→</span>
          <div className="flex items-center gap-1.5">
            <span>{formatDate(reservation.checkOut, locale)}</span>
          </div>
          <span className="ml-auto text-gray-400">
            {totalNights === 1
              ? t('hostHome.nightsOne')
              : t('hostHome.nightsOther', { count: totalNights })}
          </span>
        </div>

        <div className="mt-4 flex items-center justify-between">
          <Link
            to={`/admin/accommodations/${reservation.accommodationId}/calendar`}
            className="text-xs font-medium text-gray-500 hover:text-gray-700 inline-flex items-center gap-1"
          >
            {t('hostHome.viewCalendar')}
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
              <path d="M7 17 17 7" />
              <path d="M7 7h10v10" />
            </svg>
          </Link>
        </div>
      </div>
    </article>
  );
};

interface SectionProps {
  title: string;
  subtitle: string;
  icon: React.ReactNode;
  iconBg: string;
  emptyMessage: string;
  reservations: Reservation[];
  accommodationsById: Record<string, string>;
  locale: string;
  variant: 'current' | 'arriving' | 'leaving';
  today: Date;
}

const Section: React.FC<SectionProps> = ({
  title,
  subtitle,
  icon,
  iconBg,
  emptyMessage,
  reservations,
  accommodationsById,
  locale,
  variant,
  today,
}) => (
  <section className="mb-10">
    <header className="mb-4 flex items-center gap-3">
      <div className={`flex-shrink-0 w-10 h-10 rounded-2xl flex items-center justify-center ${iconBg}`}>
        {icon}
      </div>
      <div className="flex-1 min-w-0">
        <h2 className="text-lg font-bold text-gray-900 tracking-tight">{title}</h2>
        <p className="text-sm text-gray-500">{subtitle}</p>
      </div>
      {reservations.length > 0 && (
        <span className="text-xs font-semibold text-gray-400">
          {reservations.length}
        </span>
      )}
    </header>

    {reservations.length === 0 ? (
      <div className="rounded-2xl bg-white border border-dashed border-gray-200 px-6 py-10 text-center text-sm text-gray-400">
        {emptyMessage}
      </div>
    ) : (
      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        {reservations.map((r) => (
          <StayCard
            key={r.id}
            reservation={r}
            accommodationTitle={accommodationsById[r.accommodationId] ?? ''}
            locale={locale}
            variant={variant}
            today={today}
          />
        ))}
      </div>
    )}
  </section>
);

const HostHomePage: React.FC = () => {
  const { t, i18n } = useTranslation();
  const dispatch = useAppDispatch();

  const reservations = useAppSelector(selectReservations);
  const reservationsStatus = useAppSelector(selectReservationsStatus);
  const accommodations = useAppSelector(selectManagedAccommodations);

  useEffect(() => {
    dispatch(fetchReservations({}));
    dispatch(fetchAllAccommodations('all'));
  }, [dispatch]);

  const today = useMemo(() => startOfDay(new Date()), []);
  const locale = i18n.language.startsWith('fr') ? 'fr-FR' : 'en-GB';

  const accommodationsById = useMemo(() => {
    const map: Record<string, string> = {};
    for (const a of accommodations) {
      if (a.id) map[a.id] = a.title ?? '';
    }
    return map;
  }, [accommodations]);

  const { current, arriving, leavingToday } = useMemo(
    () => bucketize(reservations, today),
    [reservations, today]
  );

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

  return (
    <div className="min-h-full bg-gray-50/40">
      <div className="max-w-6xl mx-auto px-6 lg:px-10 py-10">
        <header className="mb-10">
          <p className="text-xs font-semibold uppercase tracking-wider text-amber-600">
            {todayLabel}
          </p>
          <h1 className="mt-2 text-3xl font-bold text-gray-900 tracking-tight">
            {greeting}
          </h1>
          <p className="text-gray-500 mt-1 max-w-2xl">{t('hostHome.subtitle')}</p>
        </header>

        {reservationsStatus === 'loading' && reservations.length === 0 ? (
          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            {[1, 2, 3].map((i) => (
              <div key={i} className="h-44 rounded-2xl bg-white border border-gray-100 animate-pulse" />
            ))}
          </div>
        ) : (
          <>
            <Section
              title={t('hostHome.current.title')}
              subtitle={t('hostHome.current.subtitle')}
              icon={
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-emerald-700">
                  <path d="M3 9.5 12 3l9 6.5V21a1 1 0 0 1-1 1h-5v-7h-6v7H4a1 1 0 0 1-1-1z" />
                </svg>
              }
              iconBg="bg-emerald-100"
              emptyMessage={t('hostHome.current.empty')}
              reservations={current}
              accommodationsById={accommodationsById}
              locale={locale}
              variant="current"
              today={today}
            />
            <Section
              title={t('hostHome.arriving.title')}
              subtitle={t('hostHome.arriving.subtitle')}
              icon={
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-amber-700">
                  <path d="M12 2v6" />
                  <path d="m8 6 4-4 4 4" />
                  <path d="M3 13a9 9 0 1 0 18 0" />
                </svg>
              }
              iconBg="bg-amber-100"
              emptyMessage={t('hostHome.arriving.empty')}
              reservations={arriving}
              accommodationsById={accommodationsById}
              locale={locale}
              variant="arriving"
              today={today}
            />
            <Section
              title={t('hostHome.leaving.title')}
              subtitle={t('hostHome.leaving.subtitle')}
              icon={
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-sky-700">
                  <path d="M5 12h14" />
                  <path d="m12 5 7 7-7 7" />
                </svg>
              }
              iconBg="bg-sky-100"
              emptyMessage={t('hostHome.leaving.empty')}
              reservations={leavingToday}
              accommodationsById={accommodationsById}
              locale={locale}
              variant="leaving"
              today={today}
            />
          </>
        )}
      </div>
    </div>
  );
};

export default HostHomePage;
