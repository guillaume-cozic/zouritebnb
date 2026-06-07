import React from 'react';
import { useTranslation } from 'react-i18next';
import { ReviewTarget } from '../ReviewTypes';
import ReviewForm from './ReviewForm';

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

  if (!open) return null;

  const title =
    target === 'accommodation'
      ? t('review.titleAccommodation')
      : t('review.titleGuest');

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
      role="dialog"
      aria-modal="true"
      onClick={onClose}
    >
      <div
        className="w-full max-w-md bg-white rounded-2xl shadow-xl overflow-hidden"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="px-5 py-4 border-b border-gray-100">
          <h2 className="text-base font-semibold text-gray-900">{title}</h2>
          {subjectName && (
            <p className="text-sm text-gray-500 mt-0.5">{subjectName}</p>
          )}
        </div>
        <div className="px-5 py-5">
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
        </div>
      </div>
    </div>
  );
};

export default ReviewModal;
