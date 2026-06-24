import React, { useEffect, useMemo, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import {
  fetchConversationsForTeam,
  fetchConversationsForUser,
  fetchConversationById,
} from '../ConversationSlice';
import {
  selectConversations,
  selectConversationsStatus,
  selectCurrentConversation,
  selectCurrentConversationStatus,
  selectUnreadCountByConversation,
} from '../ConversationSelectors';
import { selectAuthUser } from '../../auth/AuthSelectors';
import { selectHasAccommodation } from '../../accommodationManagement/AccommodationManagementSelectors';
import { fetchAccommodationSummary } from '../../accommodation/AccommodationSummarySlice';
import { selectAccommodationSummaries } from '../../accommodation/AccommodationSummarySelectors';
import {
  confirmReservation,
  fetchReservationById,
  fetchReservations,
  refuseReservation,
} from '../../reservation/ReservationSlice';
import {
  selectReservationById,
  selectReservations,
} from '../../reservation/ReservationSelectors';
import { isStayCompleted } from '../../review/reviewEligibility';
import { selectHasReviewed, selectReviewableReservationIds } from '../../review/ReviewSelectors';
import { downloadReservationInvoice } from '../../reservation/invoiceDownload';
import ReviewModal from '../../review/components/ReviewModal';
import ConversationListItem from './ConversationListItem';
import ConversationThread from './ConversationThread';
import HostPanel from './HostPanel';
import HostProfileCard from '../../hostProfile/components/HostProfileCard';

interface MessagingPageProps {
  role: 'host' | 'guest';
}

/**
 * Shared master-detail messaging page used by both hosts (/admin/conversations) and
 * travelers (/account/conversations): the same list + thread layout, with role-specific
 * side panel — accept/refuse actions for hosts, read-only reservation detail and a
 * review CTA for travelers.
 */
const MessagingPage: React.FC<MessagingPageProps> = ({ role }) => {
  const { t, i18n } = useTranslation();
  const dispatch = useAppDispatch();
  const { id } = useParams<{ id: string }>();
  const isHost = role === 'host';
  const basePath = isHost ? '/admin/conversations' : '/account/conversations';

  const user = useAppSelector(selectAuthUser);
  const hasAccommodation = useAppSelector(selectHasAccommodation);
  const accommodationSummaries = useAppSelector(selectAccommodationSummaries);
  const readOnly = !user;
  const conversations = useAppSelector(selectConversations);
  const unreadByConversation = useAppSelector(selectUnreadCountByConversation);
  const reviewableByReservation = useAppSelector(selectReviewableReservationIds);
  const listStatus = useAppSelector(selectConversationsStatus);
  const current = useAppSelector(selectCurrentConversation);
  const currentStatus = useAppSelector(selectCurrentConversationStatus);
  const reservation = useAppSelector(selectReservationById(current?.reservationId));
  const reservations = useAppSelector(selectReservations);
  const hasReviewedAccommodation = useAppSelector(
    selectHasReviewed(current?.reservationId ?? '', 'accommodation')
  );

  const [search, setSearch] = useState('');
  const [onlyNeedsAction, setOnlyNeedsAction] = useState(false);
  const [busy, setBusy] = useState(false);
  const [reviewOpen, setReviewOpen] = useState(false);

  const reservationStatusById = useMemo(() => {
    const map: Record<string, string> = {};
    for (const r of reservations) map[r.id] = r.status;
    return map;
  }, [reservations]);

  useEffect(() => {
    if (isHost) {
      dispatch(fetchConversationsForTeam());
      dispatch(fetchReservations({}));
    } else {
      dispatch(fetchConversationsForUser());
    }
  }, [dispatch, isHost]);

  useEffect(() => {
    if (id) dispatch(fetchConversationById(id));
  }, [dispatch, id]);

  // Traveler inbox: label each conversation with its accommodation (name + city).
  // The summary thunk is cached by id, so this fetches each accommodation at most once.
  useEffect(() => {
    if (isHost) return;
    const ids = new Set(conversations.map((c) => c.accommodationId));
    ids.forEach((accommodationId) => dispatch(fetchAccommodationSummary(accommodationId)));
  }, [dispatch, isHost, conversations]);

  useEffect(() => {
    if (current?.reservationId) dispatch(fetchReservationById(current.reservationId));
  }, [dispatch, current?.reservationId]);

  const locale = i18n.language.startsWith('fr') ? 'fr-FR' : 'en-GB';

  const needsActionCount = useMemo(
    () =>
      isHost
        ? conversations.filter((c) => reservationStatusById[c.reservationId] === 'pending').length
        : 0,
    [isHost, conversations, reservationStatusById]
  );

  const filtered = useMemo(() => {
    const sorted = [...conversations].sort(
      (a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime()
    );
    const byAction =
      isHost && onlyNeedsAction
        ? sorted.filter((c) => reservationStatusById[c.reservationId] === 'pending')
        : sorted;
    const q = search.trim().toLowerCase();
    if (!q) return byAction;
    return byAction.filter(
      (c) =>
        c.reservationId.toLowerCase().includes(q) ||
        c.messages.some((m) => m.body.toLowerCase().includes(q))
    );
  }, [isHost, conversations, search, onlyNeedsAction, reservationStatusById]);

  const canReviewAccommodation =
    !isHost &&
    !!reservation &&
    isStayCompleted(reservation) &&
    !hasReviewedAccommodation;

  const handleAccept = async () => {
    if (!reservation) return;
    setBusy(true);
    await dispatch(confirmReservation(reservation.id));
    setBusy(false);
  };

  const handleRefuse = async () => {
    if (!reservation) return;
    setBusy(true);
    await dispatch(refuseReservation(reservation.id));
    setBusy(false);
  };

  const handleDownloadInvoice = async () => {
    if (!reservation) return;
    setBusy(true);
    try {
      await downloadReservationInvoice(reservation.id);
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="h-[calc(100vh-4rem)] flex bg-white">
      {/* Left: conversations list */}
      <div className="w-72 xl:w-80 flex-shrink-0 border-r border-gray-100 flex flex-col bg-gray-50/40">
        <div className="px-5 pt-5 pb-3 border-b border-gray-100 bg-white">
          <h1 className="text-xl font-bold text-gray-900 tracking-tight">
            {isHost ? t('admin.conversations.title') : t('conversation.inboxTitle')}
          </h1>
          <p className="text-xs text-gray-500 mt-0.5">
            {isHost ? t('admin.conversations.subtitle') : t('conversation.inboxSubtitle')}
          </p>
          <div className="mt-3 relative">
            <svg
              className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"
              width="14"
              height="14"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              strokeWidth="2"
              strokeLinecap="round"
              strokeLinejoin="round"
            >
              <circle cx="11" cy="11" r="8" />
              <path d="m21 21-4.3-4.3" />
            </svg>
            <input
              type="search"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder={t('admin.conversations.searchPlaceholder') as string}
              className="w-full pl-9 pr-3 h-9 text-sm rounded-xl border border-gray-200 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 focus:bg-white transition-colors"
            />
          </div>
          {isHost && hasAccommodation && (
            <div className="mt-2 flex items-center justify-between">
              <button
                type="button"
                onClick={() => setOnlyNeedsAction((v) => !v)}
                aria-pressed={onlyNeedsAction}
                className={`inline-flex items-center gap-1.5 h-7 pl-2 pr-2.5 rounded-full text-xs font-semibold border transition-colors ${
                  onlyNeedsAction
                    ? 'bg-amber-500 text-white border-amber-500 shadow-sm shadow-amber-200'
                    : 'bg-white text-amber-700 border-amber-200 hover:bg-amber-50'
                }`}
              >
                <span
                  className={`w-1.5 h-1.5 rounded-full ${
                    onlyNeedsAction ? 'bg-white' : 'bg-amber-500'
                  }`}
                />
                {t('admin.conversations.needsActionToggle')}
                {needsActionCount > 0 && (
                  <span
                    className={`ml-0.5 inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full text-[10px] font-bold ${
                      onlyNeedsAction ? 'bg-white/25 text-white' : 'bg-amber-100 text-amber-800'
                    }`}
                  >
                    {needsActionCount}
                  </span>
                )}
              </button>
            </div>
          )}
        </div>

        <div className="flex-1 overflow-y-auto px-2 py-2">
          {listStatus === 'loading' && (
            <div className="space-y-2 px-1">
              {[1, 2, 3, 4].map((i) => (
                <div key={i} className="h-16 rounded-xl bg-gray-100 animate-pulse" />
              ))}
            </div>
          )}

          {listStatus === 'succeeded' && filtered.length === 0 && (
            <div className="px-4 py-10 text-center text-sm text-gray-400">
              {search
                ? t('admin.conversations.noMatch')
                : isHost && onlyNeedsAction
                  ? t('admin.conversations.noNeedsAction')
                  : isHost
                    ? t('admin.conversations.empty')
                    : t('conversation.empty')}
            </div>
          )}

          <div className="space-y-1">
            {filtered.map((c) => (
              <ConversationListItem
                key={c.id}
                conversation={c}
                to={`${basePath}/${c.id}`}
                active={c.id === id}
                locale={locale}
                needsAction={isHost && reservationStatusById[c.reservationId] === 'pending'}
                unreadCount={unreadByConversation[c.id] ?? 0}
                reviewable={!isHost && !!reviewableByReservation[c.reservationId]}
                accommodationTitle={isHost ? undefined : accommodationSummaries[c.accommodationId]?.title}
                accommodationCity={isHost ? undefined : accommodationSummaries[c.accommodationId]?.city}
              />
            ))}
          </div>
        </div>
      </div>

      {/* Right: thread + reservation panel */}
      <div className="flex-1 min-w-0 flex flex-col">
        {!id && (
          <div className="flex-1 flex items-center justify-center bg-gray-50/40">
            <div className="text-center max-w-sm px-6">
              <div className="mx-auto w-16 h-16 rounded-2xl bg-primary-50 flex items-center justify-center mb-4">
                <svg className="text-primary-500" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
                  <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                </svg>
              </div>
              <h2 className="text-lg font-semibold text-gray-900">
                {t('admin.conversations.placeholderTitle')}
              </h2>
              <p className="text-sm text-gray-500 mt-1">
                {t('admin.conversations.placeholderSubtitle')}
              </p>
            </div>
          </div>
        )}

        {id && currentStatus === 'loading' && (
          <div className="flex-1 flex items-center justify-center text-gray-400 text-sm">
            {t('conversation.loading')}
          </div>
        )}

        {id && current && (
          <div className="flex-1 flex flex-col lg:flex-row min-h-0">
            <div className="min-h-0 flex flex-col flex-1 order-2 lg:order-1">
              <header className="px-5 py-3 border-b border-gray-100 bg-white flex items-center gap-3">
                <div className="flex-1 min-w-0">
                  <p className="text-[10px] uppercase tracking-wider text-gray-400 font-semibold">
                    {isHost ? t('conversation.reservation') : t('conversation.accommodation')}
                  </p>
                  {isHost ? (
                    <p className="text-sm font-semibold text-gray-900 truncate">
                      {reservation ? reservation.guestName : current.reservationId.slice(0, 8) + '…'}
                    </p>
                  ) : (
                    // Quick link to the listing from the header (the reservation panel
                    // below/aside also links to it).
                    <Link
                      to={`/accommodations/${current.accommodationId}`}
                      className="block text-sm font-semibold text-gray-900 truncate hover:text-primary-700 transition-colors"
                    >
                      {accommodationSummaries[current.accommodationId]?.title ?? t('conversation.detailTitle')}
                      {accommodationSummaries[current.accommodationId]?.city
                        ? ` · ${accommodationSummaries[current.accommodationId]?.city}`
                        : ''}
                    </Link>
                  )}
                </div>
                {reservation && (
                  <span className="text-xs text-gray-500 hidden sm:block">
                    {new Intl.DateTimeFormat(locale, { day: '2-digit', month: 'short' }).format(new Date(reservation.checkIn))}
                    {' → '}
                    {new Intl.DateTimeFormat(locale, { day: '2-digit', month: 'short' }).format(new Date(reservation.checkOut))}
                  </span>
                )}
                {reservation?.status === 'confirmed' && (
                  <button
                    type="button"
                    onClick={handleDownloadInvoice}
                    disabled={busy}
                    className="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg text-xs font-semibold text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 disabled:opacity-60 transition-colors"
                  >
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                      <path d="M7 10l5 5 5-5" />
                      <path d="M12 15V3" />
                    </svg>
                    {t('conversation.downloadInvoice')}
                  </button>
                )}
                {canReviewAccommodation && (
                  <button
                    type="button"
                    onClick={() => setReviewOpen(true)}
                    className="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 hover:bg-amber-100 transition-colors"
                  >
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                      <path d="M12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
                    </svg>
                    {t('review.rateAccommodation')}
                  </button>
                )}
              </header>
              <div className="flex-1 min-h-0">
                <ConversationThread
                  conversation={current}
                  currentUserId={user?.id ?? ''}
                  readOnly={readOnly}
                />
              </div>
            </div>

            {(!isHost || reservation) && (
              <div className="order-1 lg:order-2 shrink-0 lg:w-[300px] max-h-[45vh] lg:max-h-none overflow-y-auto border-b lg:border-b-0 lg:border-l border-gray-100 bg-gray-50/40 p-4">
                {/* Travelers see who their host is at the top of the panel. */}
                {!isHost && (
                  <div className="mb-4 rounded-2xl border border-gray-100 bg-white px-4 py-3">
                    <HostProfileCard teamId={current.teamId} variant="compact" />
                  </div>
                )}
                {reservation && (
                  <HostPanel
                    reservation={reservation}
                    locale={locale}
                    onAccept={handleAccept}
                    onRefuse={handleRefuse}
                    busy={busy}
                    readOnly={readOnly || !isHost}
                  />
                )}
              </div>
            )}
          </div>
        )}
      </div>

      {current && reviewOpen && (
        <ReviewModal
          open={reviewOpen}
          target="accommodation"
          reservationId={current.reservationId}
          accommodationId={current.accommodationId}
          onClose={() => setReviewOpen(false)}
        />
      )}
    </div>
  );
};

export default MessagingPage;
