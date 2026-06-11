import React, { useEffect, useMemo, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { Calendar, momentLocalizer, Views, View, SlotInfo } from 'react-big-calendar';
import moment from 'moment';
import 'moment/locale/fr';
import 'react-big-calendar/lib/css/react-big-calendar.css';
import './reservationBigCalendar.css';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { fetchReservations } from '../ReservationSlice';
import {
  selectReservations,
  selectReservationsError,
  selectReservationsStatus,
} from '../ReservationSelectors';
import { fetchAllAccommodations } from '../../accommodationManagement/AccommodationManagementSlice';
import { selectManagedAccommodations } from '../../accommodationManagement/AccommodationManagementSelectors';
import CreateReservationModal from './CreateReservationModal';
import ReservationEventPopover from './ReservationEventPopover';
import { Reservation } from '../ReservationTypes';
import { colorForStatus } from './CalendarEventColor';

const pad = (n: number) => String(n).padStart(2, '0');

const formatLocalDateTime = (d: Date): string =>
  `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(
    d.getMinutes()
  )}:${pad(d.getSeconds())}`;

const formatLocalDate = (d: Date): string =>
  `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

const buildCheckInString = (date: Date): string => {
  const d = new Date(date);
  d.setHours(15, 0, 0, 0);
  return formatLocalDateTime(d);
};
const buildCheckOutString = (date: Date): string => {
  const d = new Date(date);
  d.setHours(11, 0, 0, 0);
  return formatLocalDateTime(d);
};

const addDays = (d: Date, n: number): Date => {
  const c = new Date(d);
  c.setDate(c.getDate() + n);
  return c;
};

interface ReservationEvent {
  id: string;
  title: string;
  start: Date;
  end: Date;
  resource: Reservation;
}

const AccommodationCalendarPage: React.FC = () => {
  const { id = '' } = useParams<{ id: string }>();
  const { t, i18n } = useTranslation();
  const isFr = i18n.language === 'fr';

  const localizer = useMemo(() => {
    moment.locale(isFr ? 'fr' : 'en-gb');
    return momentLocalizer(moment);
  }, [isFr]);

  const formats = useMemo(
    () => ({
      monthHeaderFormat: (date: Date) =>
        moment(date).locale(isFr ? 'fr' : 'en-gb').format('MMMM YYYY'),
      dayHeaderFormat: (date: Date) =>
        moment(date).locale(isFr ? 'fr' : 'en-gb').format('dddd D MMMM'),
      dayRangeHeaderFormat: ({ start, end }: { start: Date; end: Date }) =>
        `${moment(start).locale(isFr ? 'fr' : 'en-gb').format('D MMM')} – ${moment(end).locale(isFr ? 'fr' : 'en-gb').format('D MMM YYYY')}`,
      weekdayFormat: (date: Date) =>
        moment(date).locale(isFr ? 'fr' : 'en-gb').format('ddd'),
      dayFormat: (date: Date) =>
        moment(date).locale(isFr ? 'fr' : 'en-gb').format('ddd D'),
    }),
    [isFr]
  );

  const dispatch = useAppDispatch();
  const reservations = useAppSelector(selectReservations);
  const status = useAppSelector(selectReservationsStatus);
  const error = useAppSelector(selectReservationsError);
  const accommodations = useAppSelector(selectManagedAccommodations);

  const [visibleDate, setVisibleDate] = useState<Date>(() => new Date());
  const [view, setView] = useState<View>(Views.MONTH);

  const [modalOpen, setModalOpen] = useState(false);
  const [modalRange, setModalRange] = useState<{ start: string; end: string }>({
    start: '',
    end: '',
  });
  const [selectedReservation, setSelectedReservation] = useState<Reservation | null>(null);

  const { rangeFrom, rangeTo } = useMemo(() => {
    if (view === Views.WEEK) {
      const ws = moment(visibleDate).startOf('week').toDate();
      const we = moment(visibleDate).endOf('week').toDate();
      return { rangeFrom: formatLocalDate(ws), rangeTo: formatLocalDate(we) };
    }
    const monthStart = moment(visibleDate).startOf('month');
    const gridStart = monthStart.clone().startOf('week');
    const gridEnd = gridStart.clone().add(6 * 7 - 1, 'day');
    return { rangeFrom: gridStart.format('YYYY-MM-DD'), rangeTo: gridEnd.format('YYYY-MM-DD') };
  }, [visibleDate, view]);

  useEffect(() => {
    if (id) dispatch(fetchReservations({ accommodationId: id, from: rangeFrom, to: rangeTo }));
  }, [dispatch, id, rangeFrom, rangeTo]);

  useEffect(() => {
    if (accommodations.length === 0) dispatch(fetchAllAccommodations('all'));
  }, [dispatch, accommodations.length]);

  const accommodation = accommodations.find((a) => a.id === id);

  const events: ReservationEvent[] = useMemo(
    () =>
      reservations
        .filter((r) => r.accommodationId === id && r.status !== 'cancelled')
        .map((r) => ({
          id: r.id,
          title: r.guestName,
          start: new Date(r.checkIn),
          end: new Date(r.checkOut),
          resource: r,
        })),
    [reservations, id]
  );

  const eventPropGetter = (event: ReservationEvent) => {
    const c = colorForStatus(event.resource.status);
    return {
      className: `reservation-event-${event.resource.status}`,
      style: {
        backgroundColor: c.backgroundColor,
        color: c.textColor,
        border: 'none',
        borderRadius: '9999px',
        padding: '2px 10px',
      },
    };
  };

  const handleSelectSlot = (slot: SlotInfo) => {
    const start = slot.start as Date;
    const end = slot.end as Date;
    // react-big-calendar end is exclusive at day boundary in month view → checkout previous day
    const checkOutDate = view === Views.MONTH ? addDays(end, -1) : end;
    setModalRange({
      start: buildCheckInString(start),
      end: buildCheckOutString(checkOutDate),
    });
    setModalOpen(true);
  };

  const handleSelectEvent = (event: ReservationEvent) => {
    setSelectedReservation(event.resource);
  };

  const messages = isFr
    ? {
        today: t('calendar.today'),
        previous: t('calendar.prev'),
        next: t('calendar.next'),
        month: t('calendar.view.month'),
        week: t('calendar.view.week'),
      }
    : undefined;

  return (
    <div className="px-6 py-6">
      <div className="mb-4">
        <Link
          to="/admin/calendar"
          className="inline-flex items-center gap-1 text-sm text-primary-600 hover:text-primary-700 mb-2"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            width="14"
            height="14"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
          >
            <path d="m15 18-6-6 6-6" />
          </svg>
          {t('calendar.backToAll')}
        </Link>
        <h1 className="text-2xl font-semibold text-gray-900">
          {accommodation ? accommodation.title : t('calendar.title')}
        </h1>
        <p className="text-sm text-gray-500">{t('calendar.subtitle')}</p>
      </div>
      {status === 'loading' && (
        <div className="text-sm text-gray-500 mb-2">{t('calendar.loading')}</div>
      )}
      {status === 'failed' && error && (
        <div className="text-sm text-red-600 mb-2">
          {t('calendar.error')}: {error}
        </div>
      )}
      <div className="bg-white rounded-xl border border-gray-100 p-4">
        <div style={{ height: 700 }}>
          <Calendar
            key={isFr ? 'fr' : 'en'}
            localizer={localizer}
            culture={isFr ? 'fr' : 'en-gb'}
            formats={formats}
            events={events}
            startAccessor="start"
            endAccessor="end"
            views={[Views.MONTH, Views.WEEK]}
            view={view}
            onView={(v) => setView(v)}
            date={visibleDate}
            onNavigate={(d) => setVisibleDate(d)}
            selectable="ignoreEvents"
            onSelectSlot={handleSelectSlot}
            onSelectEvent={handleSelectEvent}
            eventPropGetter={eventPropGetter}
            messages={messages}
          />
        </div>
      </div>
      <CreateReservationModal
        isOpen={modalOpen}
        onClose={() => setModalOpen(false)}
        initialCheckIn={modalRange.start}
        initialCheckOut={modalRange.end}
        accommodationId={id}
      />
      {selectedReservation && (
        <ReservationEventPopover
          reservation={selectedReservation}
          onClose={() => setSelectedReservation(null)}
        />
      )}
    </div>
  );
};

export default AccommodationCalendarPage;
