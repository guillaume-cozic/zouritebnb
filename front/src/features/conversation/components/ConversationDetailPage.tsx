import React, { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { fetchConversationById } from '../ConversationSlice';
import {
  selectCurrentConversation,
  selectCurrentConversationStatus,
  selectCurrentConversationError,
} from '../ConversationSelectors';
import { selectAuthUser } from '../../auth/AuthSelectors';
import { fetchReservationById } from '../../reservation/ReservationSlice';
import { selectReservationById } from '../../reservation/ReservationSelectors';
import { isStayCompleted } from '../../review/reviewEligibility';
import { selectHasReviewed } from '../../review/ReviewSelectors';
import ReviewModal from '../../review/components/ReviewModal';
import ConversationThread from './ConversationThread';

const ConversationDetailPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const dispatch = useAppDispatch();
  const conversation = useAppSelector(selectCurrentConversation);
  const status = useAppSelector(selectCurrentConversationStatus);
  const error = useAppSelector(selectCurrentConversationError);
  const user = useAppSelector(selectAuthUser);
  const reservation = useAppSelector(selectReservationById(conversation?.reservationId));
  const hasReviewedAccommodation = useAppSelector(
    selectHasReviewed(conversation?.reservationId ?? '', 'accommodation')
  );
  const { t, i18n } = useTranslation();

  const [reviewOpen, setReviewOpen] = useState(false);

  useEffect(() => {
    if (id) {
      dispatch(fetchConversationById(id));
    }
  }, [dispatch, id]);

  useEffect(() => {
    if (conversation?.reservationId) {
      dispatch(fetchReservationById(conversation.reservationId));
    }
  }, [dispatch, conversation?.reservationId]);

  if (!user) return null;

  const isGuestViewer = !!conversation && conversation.guestUserId === user.id;
  const canReviewAccommodation =
    isGuestViewer &&
    !!reservation &&
    isStayCompleted(reservation) &&
    !hasReviewedAccommodation;

  const locale = i18n.language.startsWith('fr') ? 'fr-FR' : 'en-GB';

  return (
    <div className="h-[calc(100vh-4rem)] flex flex-col bg-white">
      <header className="border-b border-gray-100 bg-white px-4 sm:px-8 py-3">
        <div className="max-w-3xl mx-auto">
          <Link
            to="/conversations"
            className="inline-flex items-center gap-1.5 text-xs text-gray-500 hover:text-blue-600 transition-colors"
          >
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
              <path d="m15 18-6-6 6-6" />
            </svg>
            {t('conversation.backToList')}
          </Link>
          <div className="flex items-center justify-between gap-3 mt-1">
            <div>
              <h1 className="text-base font-semibold text-gray-900">{t('conversation.detailTitle')}</h1>
              {conversation && (
                <Link
                  to={`/accommodations/${conversation.accommodationId}`}
                  className="text-xs text-blue-600 hover:text-blue-700"
                >
                  {t('conversation.viewAccommodation')}
                </Link>
              )}
            </div>
            <div className="flex items-center gap-3">
              {canReviewAccommodation && (
                <button
                  type="button"
                  onClick={() => setReviewOpen(true)}
                  className="inline-flex items-center gap-1.5 h-8 px-3 rounded-xl text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 hover:bg-amber-100 transition-colors"
                >
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
                  </svg>
                  {t('review.rateAccommodation')}
                </button>
              )}
              {isGuestViewer && hasReviewedAccommodation && (
                <span className="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-700">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M20 6 9 17l-5-5" />
                  </svg>
                  {t('review.alreadyReviewedAccommodation')}
                </span>
              )}
              {conversation && (
                <span className="text-xs text-gray-400">
                  {new Intl.DateTimeFormat(locale, { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(conversation.createdAt))}
                </span>
              )}
            </div>
          </div>
        </div>
      </header>

      <div className="flex-1 min-h-0 max-w-3xl mx-auto w-full">
        {status === 'loading' && (
          <div className="h-full flex items-center justify-center text-sm text-gray-400">
            {t('conversation.loading')}
          </div>
        )}
        {status === 'failed' && (
          <div className="h-full flex items-center justify-center text-sm text-red-600">{error}</div>
        )}
        {status === 'succeeded' && conversation && (
          <ConversationThread conversation={conversation} currentUserId={user.id} />
        )}
      </div>

      {conversation && reviewOpen && (
        <ReviewModal
          open={reviewOpen}
          target="accommodation"
          reservationId={conversation.reservationId}
          accommodationId={conversation.accommodationId}
          onClose={() => setReviewOpen(false)}
        />
      )}
    </div>
  );
};

export default ConversationDetailPage;
