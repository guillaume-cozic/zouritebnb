import React, { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';
import DatePicker, { registerLocale } from 'react-datepicker';
import { fr } from 'date-fns/locale/fr';
import { enGB } from 'date-fns/locale/en-GB';
import 'react-datepicker/dist/react-datepicker.css';
import '../../../styles/datepicker-overrides.css';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { requestReservation, clearMutationError } from '../ReservationSlice';
import {
  selectReservationMutationError,
  selectReservationMutationStatus,
} from '../ReservationSelectors';
import { selectAuthUser } from '../../auth/AuthSelectors';

registerLocale('fr', fr);
registerLocale('en', enGB);

interface Props {
  isOpen: boolean;
  onClose: () => void;
  accommodationId: string;
  initialCheckIn?: string;
  initialCheckOut?: string;
}

const parseIso = (iso?: string): Date | null => {
  if (!iso) return null;
  const d = new Date(iso);
  return isNaN(d.getTime()) ? null : d;
};

const pad = (n: number) => String(n).padStart(2, '0');

const toApiDateTime = (date: Date, time: string): string => {
  const [hh, mm] = time.split(':');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${hh}:${mm}:00`;
};

const RequestReservationModal: React.FC<Props> = ({
  isOpen,
  onClose,
  accommodationId,
  initialCheckIn,
  initialCheckOut,
}) => {
  const { t, i18n } = useTranslation();
  const dispatch = useAppDispatch();
  const navigate = useNavigate();
  const user = useAppSelector(selectAuthUser);
  const mutationStatus = useAppSelector(selectReservationMutationStatus);
  const mutationError = useAppSelector(selectReservationMutationError);

  const fullName = [user?.firstName, user?.lastName].filter(Boolean).join(' ').trim();

  const [guestName, setGuestName] = useState(fullName || '');
  const [startDate, setStartDate] = useState<Date | null>(null);
  const [endDate, setEndDate] = useState<Date | null>(null);
  const [checkInTime, setCheckInTime] = useState('15:00');
  const [checkOutTime, setCheckOutTime] = useState('11:00');
  const [note, setNote] = useState('');
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    if (isOpen) {
      setGuestName(fullName || '');
      setStartDate(parseIso(initialCheckIn));
      setEndDate(parseIso(initialCheckOut));
      setCheckInTime('15:00');
      setCheckOutTime('11:00');
      setNote('');
      dispatch(clearMutationError());
    }
  }, [isOpen, initialCheckIn, initialCheckOut, fullName, dispatch]);

  if (!isOpen) return null;

  const locale = i18n.language.startsWith('fr') ? 'fr' : 'en';
  const nights =
    startDate && endDate
      ? Math.max(0, Math.round((endDate.getTime() - startDate.getTime()) / 86400000))
      : 0;

  const handleRangeChange = (dates: [Date | null, Date | null]) => {
    const [start, end] = dates;
    setStartDate(start);
    setEndDate(end);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!user || !startDate || !endDate || !guestName.trim()) return;

    setSubmitting(true);
    const result = await dispatch(
      requestReservation({
        accommodationId,
        guestUserId: user.id,
        checkIn: toApiDateTime(startDate, checkInTime),
        checkOut: toApiDateTime(endDate, checkOutTime),
        guestName: guestName.trim(),
        note: note.trim() || undefined,
      })
    );
    setSubmitting(false);

    if (requestReservation.fulfilled.match(result)) {
      onClose();
      navigate('/conversations');
    }
  };

  return (
    <div className="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40 p-4 overflow-y-auto">
      <div className="bg-white rounded-2xl shadow-2xl w-full max-w-2xl my-8">
        <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
          <div>
            <h2 className="text-lg font-semibold text-gray-900">{t('request.title')}</h2>
            <p className="text-xs text-gray-500 mt-0.5">{t('request.subtitle')}</p>
          </div>
          {nights > 0 && (
            <span className="text-sm font-medium text-blue-700 bg-blue-50 px-2.5 py-1 rounded-full">
              {nights} {nights > 1 ? t('request.nights') : t('request.night')}
            </span>
          )}
        </div>

        <form onSubmit={handleSubmit} className="px-6 py-5 space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              {t('request.guestName')}
            </label>
            <input
              type="text"
              value={guestName}
              onChange={(e) => setGuestName(e.target.value)}
              className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500"
              required
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              {t('request.dates')}
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
                {t('request.arrivalTime')}
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
                {t('request.departureTime')}
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

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              {t('request.note')} <span className="text-gray-400 font-normal">({t('request.optional')})</span>
            </label>
            <textarea
              value={note}
              onChange={(e) => setNote(e.target.value)}
              rows={3}
              maxLength={2000}
              placeholder={t('request.notePlaceholder') as string}
              className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 resize-none"
            />
          </div>

          <div className="rounded-xl bg-blue-50 border border-blue-100 px-3 py-2.5 text-xs text-blue-800">
            {t('request.hostHas24h')}
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
              {t('request.cancel')}
            </button>
            <button
              type="submit"
              disabled={submitting || mutationStatus === 'loading' || !startDate || !endDate || !guestName.trim()}
              className="px-5 py-2 text-sm font-semibold rounded-lg bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-60"
            >
              {submitting ? t('request.submitting') : t('request.submit')}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default RequestReservationModal;
