import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { AccommodationReview, MAX_RATING } from '../ReviewTypes';
import { replyToReview } from '../ReviewSlice';
import { useAppDispatch } from '../../../store/hooks';
import { Avatar, Button, Textarea } from '../../../components/ui';

interface StarsProps {
  rating: number;
}

const Stars: React.FC<StarsProps> = ({ rating }) => (
  <div className="flex items-center gap-0.5" aria-label={`${rating}/${MAX_RATING}`}>
    {Array.from({ length: MAX_RATING }, (_, i) => i + 1).map((star) => {
      const active = rating >= star;
      return (
        <svg
          key={star}
          width="16"
          height="16"
          viewBox="0 0 24 24"
          fill={active ? '#f59e0b' : 'none'}
          stroke={active ? '#f59e0b' : '#d1d5db'}
          strokeWidth="1.5"
          strokeLinecap="round"
          strokeLinejoin="round"
        >
          <path d="M12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
        </svg>
      );
    })}
  </div>
);

interface HostReplyBlockProps {
  review: AccommodationReview;
  accommodationId: string;
  canReply: boolean;
}

/** Shows the host reply (to everyone) and, for the host, an inline form to add/edit it. */
const HostReplyBlock: React.FC<HostReplyBlockProps> = ({ review, accommodationId, canReply }) => {
  const { t } = useTranslation();
  const dispatch = useAppDispatch();
  const [editing, setEditing] = useState(false);
  const [reply, setReply] = useState(review.hostReply ?? '');
  const [busy, setBusy] = useState(false);

  const submit = async () => {
    if (reply.trim() === '') return;
    setBusy(true);
    await dispatch(replyToReview({ reviewId: review.id, accommodationId, reply }));
    setBusy(false);
    setEditing(false);
  };

  if (review.hostReply && !editing) {
    return (
      <div className="mt-3 rounded-xl bg-gray-50 border border-gray-100 p-3">
        <p className="text-xs font-semibold text-gray-700 mb-1">{t('review.hostReply.title')}</p>
        <p className="text-sm text-gray-600 leading-relaxed whitespace-pre-line">{review.hostReply}</p>
        {canReply && (
          <button
            type="button"
            onClick={() => { setReply(review.hostReply ?? ''); setEditing(true); }}
            className="mt-2 text-xs font-semibold text-primary-600 hover:text-primary-700"
          >
            {t('review.hostReply.edit')}
          </button>
        )}
      </div>
    );
  }

  if (!canReply) {
    return null;
  }

  if (!editing) {
    return (
      <button
        type="button"
        onClick={() => setEditing(true)}
        className="mt-3 text-sm font-semibold text-primary-600 hover:text-primary-700"
      >
        {t('review.hostReply.action')}
      </button>
    );
  }

  return (
    <div className="mt-3 space-y-2">
      <Textarea
        value={reply}
        onChange={(e) => setReply(e.target.value)}
        rows={3}
        maxLength={2000}
        placeholder={t('review.hostReply.placeholder') as string}
      />
      <div className="flex justify-end gap-2">
        <Button variant="ghost" size="sm" onClick={() => setEditing(false)} disabled={busy}>
          {t('review.hostReply.cancel')}
        </Button>
        <Button size="sm" onClick={submit} loading={busy} disabled={reply.trim() === ''}>
          {t('review.hostReply.submit')}
        </Button>
      </div>
    </div>
  );
};

interface Props {
  reviews: AccommodationReview[];
  accommodationId: string;
  /** When true, the current user is a member of the owning team and may reply. */
  canReply?: boolean;
}

const AccommodationReviews: React.FC<Props> = ({ reviews, accommodationId, canReply = false }) => {
  const { t, i18n } = useTranslation();

  if (reviews.length === 0) {
    return null;
  }

  const average =
    reviews.reduce((sum, r) => sum + r.rating, 0) / reviews.length;

  const formatDate = (iso: string) =>
    new Date(iso).toLocaleDateString(i18n.language, { year: 'numeric', month: 'long' });

  return (
    <section className="mt-10 pt-8 border-t border-gray-100">
      <div className="flex items-center gap-2 mb-6">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="#f59e0b" stroke="#f59e0b" strokeWidth="1.5" strokeLinejoin="round">
          <path d="M12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
        </svg>
        <h2 className="text-xl font-bold text-gray-900">
          {average.toFixed(1)} · {t('review.reviewCount', { count: reviews.length })}
        </h2>
      </div>

      <div className="grid gap-6 sm:grid-cols-2">
        {reviews.map((review) => (
          <article key={review.id} className="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <header className="flex items-center gap-3 mb-3">
              <Avatar
                avatarUrl={review.authorAvatarUrl}
                name={review.authorName}
                sizeClassName="h-10 w-10"
                fallbackClassName="bg-primary-100 text-primary-700"
              />
              <div>
                <p className="font-semibold text-gray-900 text-sm">{review.authorName}</p>
                <p className="text-xs text-gray-400">{formatDate(review.createdAt)}</p>
              </div>
            </header>
            <Stars rating={review.rating} />
            <p className="mt-3 text-sm text-gray-600 leading-relaxed">{review.comment}</p>
            <HostReplyBlock review={review} accommodationId={accommodationId} canReply={canReply} />
          </article>
        ))}
      </div>
    </section>
  );
};

export default AccommodationReviews;
