import React, { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import DatePicker, { registerLocale } from 'react-datepicker';
import { fr } from 'date-fns/locale/fr';
import { enGB } from 'date-fns/locale/en-GB';
import 'react-datepicker/dist/react-datepicker.css';
import '../../../styles/datepicker-overrides.css';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { createReservation, reservationModalOpened } from '../ReservationSlice';
import {
  selectReservationMutationError,
  selectReservationMutationStatus,
} from '../ReservationSelectors';
import { selectManagedAccommodations } from '../../accommodationManagement/AccommodationManagementSelectors';

registerLocale('fr', fr);
registerLocale('en', enGB);

interface Props {
  isOpen: boolean;
  onClose: () => void;
  initialCheckIn: string;
  initialCheckOut: string;
  accommodationId?: string;
}

const parseIso = (iso: string): Date | null => {
  if (!iso) return null;
  const d = new Date(iso);
  return isNaN(d.getTime()) ? null : d;
};

const pad = (n: number) => String(n).padStart(2, '0');

const toApiDateTime = (date: Date, time: string): string => {
  const [hh, mm] = time.split(':');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${hh}:${mm}:00`;
};

const extractTime = (iso: string, fallback: string): string => {
  const d = parseIso(iso);
  if (!d) return fallback;
  return `${pad(d.getHours())}:${pad(d.getMinutes())}`;
};

const CreateReservationModal: React.FC<Props> = ({
  isOpen,
  onClose,
  initialCheckIn,
  initialCheckOut,
  accommodationId,
}) => {
  const { t, i18n } = useTranslation();
  const dispatch = useAppDispatch();
  const accommodations = useAppSelector(selectManagedAccommodations);
  const mutationStatus = useAppSelector(selectReservationMutationStatus);
  const mutationError = useAppSelector(selectReservationMutationError);

  const [guestName, setGuestName] = useState('');
  const [startDate, setStartDate] = useState<Date | null>(null);
  const [endDate, setEndDate] = useState<Date | null>(null);
  const [checkInTime, setCheckInTime] = useState('15:00');
  const [checkOutTime, setCheckOutTime] = useState('11:00');
  const [chosenAccommodation, setChosenAccommodation] = useState(accommodationId ?? '');
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    if (isOpen) {
      setGuestName('');
      setStartDate(parseIso(initialCheckIn));
      setEndDate(parseIso(initialCheckOut));
      setCheckInTime(extractTime(initialCheckIn, '15:00'));
      setCheckOutTime(extractTime(initialCheckOut, '11:00'));
      setChosenAccommodation(accommodationId ?? '');
      dispatch(reservationModalOpened({ accommodationId }));
    }
  }, [isOpen, initialCheckIn, initialCheckOut, accommodationId, dispatch]);

  if (!isOpen) return null;

  const handleRangeChange = (dates: [Date | null, Date | null]) => {
    const [start, end] = dates;
    setStartDate(start);
    setEndDate(end);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    const targetId = accommodationId ?? chosenAccommodation;
    if (!targetId || !guestName || !startDate || !endDate) return;
    setSubmitting(true);
    const result = await dispatch(
      createReservation({
        accommodationId: targetId,
        checkIn: toApiDateTime(startDate, checkInTime),
        checkOut: toApiDateTime(endDate, checkOutTime),
        guestName,
      })
    );
    setSubmitting(false);
    if (createReservation.fulfilled.match(result)) {
      onClose();
    }
  };

  const locale = i18n.language.startsWith('fr') ? 'fr' : 'en';
  const nights =
    startDate && endDate
      ? Math.max(0, Math.round((endDate.getTime() - startDate.getTime()) / 86400000))
      : 0;


  return (
    <div className="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40 p-4 overflow-y-auto">
      <div className="bg-white rounded-xl shadow-xl w-full max-w-2xl my-8">
        <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
          <h2 className="text-lg font-semibold text-gray-900">{t('calendar.createTitle')}</h2>
          {nights > 0 && (
            <span className="text-sm text-gray-500">
              {nights} {nights > 1 ? 'nuits' : 'nuit'}
            </span>
          )}
        </div>
        <form onSubmit={handleSubmit} className="px-6 py-4 space-y-4">
          {!accommodationId && (
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                {t('calendar.accommodation')}
              </label>
              <select
                value={chosenAccommodation}
                onChange={(e) => setChosenAccommodation(e.target.value)}
                className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                required
              >
                <option value="">—</option>
                {accommodations.map((a) => (
                  <option key={a.id} value={a.id}>
                    {a.title}
                  </option>
                ))}
              </select>
            </div>
          )}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              {t('calendar.guestName')}
            </label>
            <input
              type="text"
              value={guestName}
              onChange={(e) => setGuestName(e.target.value)}
              className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
              required
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              {t('calendar.checkIn')} → {t('calendar.checkOut')}
            </label>
            {(startDate || endDate) && (
              <div className="mb-2 text-sm text-gray-700">
                {startDate ? startDate.toLocaleDateString(locale === 'fr' ? 'fr-FR' : 'en-GB') : '—'}
                {' → '}
                {endDate ? endDate.toLocaleDateString(locale === 'fr' ? 'fr-FR' : 'en-GB') : '—'}
              </div>
            )}
            <div className="flex justify-center">
              <DatePicker
                selected={startDate}
                onChange={handleRangeChange}
                startDate={startDate ?? undefined}
                endDate={endDate ?? undefined}
                selectsRange
                inline
                monthsShown={2}
                locale={locale}
                minDate={new Date()}
              />
            </div>
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                {t('calendar.checkIn')}
              </label>
              <input
                type="time"
                value={checkInTime}
                onChange={(e) => setCheckInTime(e.target.value)}
                className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                required
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                {t('calendar.checkOut')}
              </label>
              <input
                type="time"
                value={checkOutTime}
                onChange={(e) => setCheckOutTime(e.target.value)}
                className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                required
              />
            </div>
          </div>
          {mutationError && (
            <div className="text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
              {mutationError}
            </div>
          )}
          <div className="flex justify-end gap-2 pt-2">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 text-sm rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50"
            >
              {t('calendar.cancel')}
            </button>
            <button
              type="submit"
              disabled={submitting || mutationStatus === 'loading' || !startDate || !endDate}
              className="px-4 py-2 text-sm rounded-lg bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-60"
            >
              {t('calendar.submit')}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default CreateReservationModal;
