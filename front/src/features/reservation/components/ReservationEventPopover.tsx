import React from 'react';
import { useTranslation } from 'react-i18next';
import { useAppDispatch } from '../../../store/hooks';
import { Reservation } from '../ReservationTypes';
import { cancelReservation, confirmReservation } from '../ReservationSlice';
import { colorForStatus } from './CalendarEventColor';
import { Button, Modal } from '../../../components/ui';

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
    await dispatch(cancelReservation({ id: reservation.id }));
    onClose();
  };

  return (
    <Modal
      open
      onClose={onClose}
      size="sm"
      title={reservation.guestName}
      subtitle={
        <span className={`inline-block text-xs px-2 py-0.5 rounded-full ${colors.badgeClass}`}>
          {t(`calendar.status.${reservation.status}`)}
        </span>
      }
      action={
        <button
          type="button"
          onClick={onClose}
          aria-label={t('calendar.cancel')}
          className="text-surface-400 hover:text-surface-600 text-xl leading-none"
        >
          ×
        </button>
      }
      footer={
        <>
          <Button variant="success" size="sm" onClick={handleConfirm} disabled={!canConfirm}>
            {t('calendar.action.confirm')}
          </Button>
          <Button variant="danger" size="sm" onClick={handleCancel} disabled={!canCancel}>
            {t('calendar.action.cancel')}
          </Button>
        </>
      }
    >
      <div className="text-sm text-surface-700 space-y-1">
        <div>
          <span className="font-medium">{t('calendar.checkIn')}: </span>
          {fmt(reservation.checkIn)}
        </div>
        <div>
          <span className="font-medium">{t('calendar.checkOut')}: </span>
          {fmt(reservation.checkOut)}
        </div>
      </div>
    </Modal>
  );
};

export default ReservationEventPopover;
