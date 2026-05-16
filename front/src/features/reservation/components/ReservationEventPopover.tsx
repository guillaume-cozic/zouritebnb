import React from 'react';
import { useTranslation } from 'react-i18next';
import { useAppDispatch } from '../../../store/hooks';
import { Reservation } from '../ReservationTypes';
import { cancelReservation, confirmReservation } from '../ReservationSlice';
import { colorForStatus } from './CalendarEventColor';

interface Props {
  reservation: Reservation;
  onClose: () => void;
}

const ReservationEventPopover: React.FC<Props> = ({ reservation, onClose }) => {
  const { t, i18n } = useTranslation();
  const dispatch = useAppDispatch();
  const colors = colorForStatus(reservation.status);

  const canConfirm = reservation.status === 'pending';
  const canCancel = reservation.status === 'pending' || reservation.status === 'confirmed';

  const dateLocale = i18n.language.startsWith('fr') ? 'fr-FR' : 'en-GB';
  const fmt = (iso: string) =>
    iso ? new Date(iso).toLocaleString(dateLocale) : '';

  const handleConfirm = async () => {
    await dispatch(confirmReservation(reservation.id));
    onClose();
  };
  const handleCancel = async () => {
    await dispatch(cancelReservation(reservation.id));
    onClose();
  };

  return (
    <div className="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40">
      <div className="bg-white rounded-xl shadow-xl w-full max-w-sm mx-4">
        <div className="px-5 py-4 border-b border-gray-100 flex items-start justify-between">
          <div>
            <h3 className="text-base font-semibold text-gray-900">{reservation.guestName}</h3>
            <span className={`mt-1 inline-block text-xs px-2 py-0.5 rounded-full ${colors.badgeClass}`}>
              {t(`calendar.status.${reservation.status}`)}
            </span>
          </div>
          <button onClick={onClose} className="text-gray-400 hover:text-gray-600 text-xl leading-none">
            ×
          </button>
        </div>
        <div className="px-5 py-4 text-sm text-gray-700 space-y-1">
          <div>
            <span className="font-medium">{t('calendar.checkIn')}: </span>
            {fmt(reservation.checkIn)}
          </div>
          <div>
            <span className="font-medium">{t('calendar.checkOut')}: </span>
            {fmt(reservation.checkOut)}
          </div>
        </div>
        <div className="px-5 py-3 border-t border-gray-100 flex justify-end gap-2">
          <button
            onClick={handleConfirm}
            disabled={!canConfirm}
            className="px-3 py-1.5 text-sm rounded-lg bg-green-600 text-white hover:bg-green-700 disabled:opacity-40 disabled:cursor-not-allowed"
          >
            {t('calendar.action.confirm')}
          </button>
          <button
            onClick={handleCancel}
            disabled={!canCancel}
            className="px-3 py-1.5 text-sm rounded-lg bg-red-600 text-white hover:bg-red-700 disabled:opacity-40 disabled:cursor-not-allowed"
          >
            {t('calendar.action.cancel')}
          </button>
        </div>
      </div>
    </div>
  );
};

export default ReservationEventPopover;
