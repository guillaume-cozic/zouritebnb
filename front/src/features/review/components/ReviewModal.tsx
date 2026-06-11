import React from 'react';
import { useTranslation } from 'react-i18next';
import { ReviewTarget } from '../ReviewTypes';
import ReviewForm from './ReviewForm';
import { Modal } from '../../../components/ui';

interface Props {
  open: boolean;
  target: ReviewTarget;
  reservationId: string;
  accommodationId: string;
  guestUserId?: string;
  /** Optional name displayed in the modal title (guest name or accommodation title). */
  subjectName?: string;
  onClose: () => void;
  onSubmitted?: () => void;
}

const ReviewModal: React.FC<Props> = ({
  open,
  target,
  reservationId,
  accommodationId,
  guestUserId,
  subjectName,
  onClose,
  onSubmitted,
}) => {
  const { t } = useTranslation();

  const title =
    target === 'accommodation'
      ? t('review.titleAccommodation')
      : t('review.titleGuest');

  return (
    <Modal open={open} onClose={onClose} title={title} subtitle={subjectName}>
      <ReviewForm
        target={target}
        reservationId={reservationId}
        accommodationId={accommodationId}
        guestUserId={guestUserId}
        onCancel={onClose}
        onSubmitted={() => {
          onSubmitted?.();
          onClose();
        }}
      />
    </Modal>
  );
};

export default ReviewModal;
