import React, { useEffect, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import Timeline, {
  TimelineGroupBase,
  TimelineItemBase,
  TimelineHeaders,
  SidebarHeader,
  DateHeader,
} from 'react-calendar-timeline';
import moment from 'moment';
import 'moment/locale/fr';
import dayjs from 'dayjs';
import 'dayjs/locale/fr';
import 'react-calendar-timeline/style.css';
import './reservationTimeline.css';
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

moment.locale('fr');
dayjs.locale('fr');

type ViewMode = 'month' | 'week';

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

const AllAccommodationsCalendarPage: React.FC = () => {
  const { t, i18n } = useTranslation();
  const isFr = i18n.language === 'fr';
  useEffect(() => {
    moment.locale(isFr ? 'fr' : 'en-gb');
  }, [isFr]);

  const dispatch = useAppDispatch();
  const reservations = useAppSelector(selectReservations);
  const status = useAppSelector(selectReservationsStatus);
  const error = useAppSelector(selectReservationsError);
  const accommodations = useAppSelector(selectManagedAccommodations);

  const [visibleDate, setVisibleDate] = useState<Date>(() => new Date());
  const [view, setView] = useState<ViewMode>('month');
  const [accommodationFilter, setAccommodationFilter] = useState<string>('all');

  const [modalOpen, setModalOpen] = useState(false);
  const [modalRange, setModalRange] = useState<{
    start: string;
    end: string;
    accommodationId?: string;
  }>({ start: '', end: '' });
  const [selectedReservation, setSelectedReservation] = useState<Reservation | null>(null);

  const { rangeStart, rangeEnd, title } = useMemo(() => {
    if (view === 'week') {
      const ws = moment(visibleDate).startOf('week');
      const we = moment(visibleDate).endOf('week');
      return {
        rangeStart: ws,
        rangeEnd: we,
        title: `${ws.format('D MMM')} – ${we.format('D MMM YYYY')}`,
      };
    }
    const ms = moment(visibleDate).startOf('month');
    const me = moment(visibleDate).endOf('month');
    return {
      rangeStart: ms,
      rangeEnd: me,
      title: ms.format('MMMM YYYY'),
    };
  }, [visibleDate, view, isFr]);

  useEffect(() => {
    dispatch(fetchAllAccommodations('all'));
  }, [dispatch]);

  useEffect(() => {
    dispatch(
      fetchReservations({
        from: formatLocalDate(rangeStart.toDate()),
        to: formatLocalDate(rangeEnd.toDate()),
      })
    );
  }, [dispatch, rangeStart, rangeEnd]);

  const groups: TimelineGroupBase[] = useMemo(
    () =>
      accommodations
        .filter((a) => accommodationFilter === 'all' || a.id === accommodationFilter)
        .map((a) => ({ id: a.id, title: a.title })),
    [accommodations, accommodationFilter]
  );

  const items: TimelineItemBase<number>[] = useMemo(
    () =>
      reservations
        .filter((r) => r.status !== 'cancelled')
        .filter(
          (r) => accommodationFilter === 'all' || r.accommodationId === accommodationFilter
        )
        .map((r) => {
          const c = colorForStatus(r.status);
          return {
            id: r.id,
            group: r.accommodationId,
            title: r.guestName,
            start_time: moment(r.checkIn).valueOf(),
            end_time: moment(r.checkOut).valueOf(),
            itemProps: {
              style: {
                backgroundColor: c.backgroundColor,
                color: c.textColor,
                borderRadius: 9999,
                border: 'none',
              },
            },
          };
        }),
    [reservations, accommodationFilter]
  );

  const handlePrev = () =>
    setVisibleDate((d) =>
      view === 'month'
        ? moment(d).subtract(1, 'month').toDate()
        : moment(d).subtract(1, 'week').toDate()
    );
  const handleNext = () =>
    setVisibleDate((d) =>
      view === 'month'
        ? moment(d).add(1, 'month').toDate()
        : moment(d).add(1, 'week').toDate()
    );
  const handleToday = () => setVisibleDate(new Date());

  const handleCanvasClick = (groupId: string | number, time: number) => {
    const start = moment(time).startOf('day').toDate();
    const end = moment(start).add(1, 'day').toDate();
    const checkOut = moment(end).subtract(1, 'day').toDate();
    setModalRange({
      start: buildCheckInString(start),
      end: buildCheckOutString(checkOut),
      accommodationId: String(groupId),
    });
    setModalOpen(true);
  };

  const handleItemClick = (itemId: string | number) => {
    const r = reservations.find((res) => res.id === String(itemId));
    if (r) setSelectedReservation(r);
  };

  const btnPrimary =
    'px-3 py-1.5 rounded-lg bg-blue-600 text-white text-sm hover:bg-blue-700';
  const segBase =
    'px-3 py-1.5 text-sm border border-gray-200 first:rounded-l-lg last:rounded-r-lg';
  const segActive = 'bg-blue-600 text-white border-blue-600';
  const segIdle = 'bg-white text-gray-700 hover:bg-gray-50';

  return (
    <div className="px-6 py-6">
      <div className="mb-4">
        <h1 className="text-2xl font-semibold text-gray-900">{t('calendar.title')}</h1>
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
        <div className="flex flex-wrap items-center justify-between gap-3 mb-4 p-3 border border-gray-200 rounded-xl bg-white">
          <div className="flex items-center gap-2">
            <button type="button" className={btnPrimary} onClick={handleToday}>
              {t('calendar.today')}
            </button>
            <button type="button" className={btnPrimary} onClick={handlePrev}>
              {t('calendar.prev')}
            </button>
            <button type="button" className={btnPrimary} onClick={handleNext}>
              {t('calendar.next')}
            </button>
            <span className="ml-3 text-sm font-medium text-gray-700">{title}</span>
          </div>
          <div className="flex items-center gap-3">
            <div className="inline-flex">
              <button
                type="button"
                className={`${segBase} ${view === 'month' ? segActive : segIdle}`}
                onClick={() => setView('month')}
              >
                {t('calendar.view.month')}
              </button>
              <button
                type="button"
                className={`${segBase} ${view === 'week' ? segActive : segIdle}`}
                onClick={() => setView('week')}
              >
                {t('calendar.view.week')}
              </button>
            </div>
            <div className="flex items-center gap-2">
              <label htmlFor="accommodation-filter" className="text-sm text-gray-600">
                {t('calendar.filter.accommodation')}
              </label>
              <select
                id="accommodation-filter"
                value={accommodationFilter}
                onChange={(e) => setAccommodationFilter(e.target.value)}
                className="px-3 py-1.5 rounded-lg border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="all">{t('calendar.filter.all')}</option>
                {accommodations.map((a) => (
                  <option key={a.id} value={a.id}>
                    {a.title}
                  </option>
                ))}
              </select>
            </div>
          </div>
        </div>
        {groups.length > 0 && (
          <div className="reservation-timeline">
            <Timeline
              groups={groups}
              items={items}
              visibleTimeStart={rangeStart.valueOf()}
              visibleTimeEnd={rangeEnd.valueOf()}
              buffer={1}
              sidebarWidth={220}
              lineHeight={48}
              itemHeightRatio={0.7}
              onTimeChange={(_s, _e, updateScrollCanvas) => {
                updateScrollCanvas(rangeStart.valueOf(), rangeEnd.valueOf());
              }}
              onCanvasClick={handleCanvasClick}
              onItemClick={handleItemClick}
              canMove={false}
              canResize={false}
              stackItems
            >
              <TimelineHeaders>
                <SidebarHeader>
                  {({ getRootProps }: { getRootProps: () => object }) => (
                    <div {...getRootProps()} className="rct-sidebar-header" />
                  )}
                </SidebarHeader>
                <DateHeader
                  unit="primaryHeader"
                  labelFormat={
                    (([startTime]: [dayjs.Dayjs]) =>
                      startTime.locale(isFr ? 'fr' : 'en-gb').format('MMMM YYYY')) as unknown as string
                  }
                />
                <DateHeader
                  unit="day"
                  labelFormat={
                    (([startTime]: [dayjs.Dayjs]) =>
                      startTime.locale(isFr ? 'fr' : 'en-gb').format('ddd D')) as unknown as string
                  }
                />
              </TimelineHeaders>
            </Timeline>
          </div>
        )}
      </div>
      <CreateReservationModal
        isOpen={modalOpen}
        onClose={() => setModalOpen(false)}
        initialCheckIn={modalRange.start}
        initialCheckOut={modalRange.end}
        accommodationId={modalRange.accommodationId}
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

export default AllAccommodationsCalendarPage;
