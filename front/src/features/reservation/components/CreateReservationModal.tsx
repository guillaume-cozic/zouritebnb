import React, { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { createReservation, clearMutationError } from '../ReservationSlice';
import {
  selectReservationMutationError,
  selectReservationMutationStatus,
} from '../ReservationSelectors';
import { selectManagedAccommodations } from '../../accommodationManagement/AccommodationManagementSelectors';
import { fetchAllAccommodations } from '../../accommodationManagement/AccommodationManagementSlice';

interface Props {
  isOpen: boolean;
  onClose: () => void;
  initialCheckIn: string;
  initialCheckOut: string;
  accommodationId?: string;
}

const toLocalDateTimeInput = (iso: string): string => {
  if (!iso) return '';
  // expects YYYY-MM-DDTHH:mm:ss → trim seconds for input[type=datetime-local]
  return iso.length >= 16 ? iso.substring(0, 16) : iso;
};

const fromInputToApi = (value: string): string => {
  if (!value) return '';
  // datetime-local returns YYYY-MM-DDTHH:mm
  return value.length === 16 ? `${value}:00` : value;
};

const CreateReservationModal: React.FC<Props> = ({
  isOpen,
  onClose,
  initialCheckIn,
  initialCheckOut,
  accommodationId,
}) => {
  const { t } = useTranslation();
  const dispatch = useAppDispatch();
  const accommodations = useAppSelector(selectManagedAccommodations);
  const mutationStatus = useAppSelector(selectReservationMutationStatus);
  const mutationError = useAppSelector(selectReservationMutationError);

  const [guestName, setGuestName] = useState('');
  const [checkIn, setCheckIn] = useState(toLocalDateTimeInput(initialCheckIn));
  const [checkOut, setCheckOut] = useState(toLocalDateTimeInput(initialCheckOut));
  const [chosenAccommodation, setChosenAccommodation] = useState(accommodationId ?? '');
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    if (isOpen) {
      setGuestName('');
      setCheckIn(toLocalDateTimeInput(initialCheckIn));
      setCheckOut(toLocalDateTimeInput(initialCheckOut));
      setChosenAccommodation(accommodationId ?? '');
      dispatch(clearMutationError());
      if (!accommodationId && accommodations.length === 0) {
        dispatch(fetchAllAccommodations('all'));
      }
    }
  }, [isOpen, initialCheckIn, initialCheckOut, accommodationId, dispatch, accommodations.length]);

  if (!isOpen) return null;

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    const targetId = accommodationId ?? chosenAccommodation;
    if (!targetId || !guestName || !checkIn || !checkOut) return;
    setSubmitting(true);
    const result = await dispatch(
      createReservation({
        accommodationId: targetId,
        checkIn: fromInputToApi(checkIn),
        checkOut: fromInputToApi(checkOut),
        guestName,
      })
    );
    setSubmitting(false);
    if (createReservation.fulfilled.match(result)) {
      onClose();
    }
  };

  return (
    <div className="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40">
      <div className="bg-white rounded-xl shadow-xl w-full max-w-md mx-4">
        <div className="px-6 py-4 border-b border-gray-100">
          <h2 className="text-lg font-semibold text-gray-900">{t('calendar.createTitle')}</h2>
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
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                {t('calendar.checkIn')}
              </label>
              <input
                type="datetime-local"
                value={checkIn}
                onChange={(e) => setCheckIn(e.target.value)}
                className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                required
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                {t('calendar.checkOut')}
              </label>
              <input
                type="datetime-local"
                value={checkOut}
                onChange={(e) => setCheckOut(e.target.value)}
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
              disabled={submitting || mutationStatus === 'loading'}
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
