import React, { useEffect, useMemo, useState } from 'react';
import { useParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import {
  fetchConversationsForTeam,
  fetchConversationById,
} from '../ConversationSlice';
import {
  selectConversations,
  selectConversationsStatus,
  selectCurrentConversation,
  selectCurrentConversationStatus,
} from '../ConversationSelectors';
import { selectAuthUser } from '../../auth/AuthSelectors';
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
import ConversationListItem from './ConversationListItem';
import ConversationThread from './ConversationThread';
import HostPanel from './HostPanel';

const AdminConversationsPage: React.FC = () => {
  const { t, i18n } = useTranslation();
  const dispatch = useAppDispatch();
  const { id } = useParams<{ id: string }>();

  const user = useAppSelector(selectAuthUser);
  const readOnly = !user;
  const conversations = useAppSelector(selectConversations);
  const listStatus = useAppSelector(selectConversationsStatus);
  const current = useAppSelector(selectCurrentConversation);
  const currentStatus = useAppSelector(selectCurrentConversationStatus);
  const reservation = useAppSelector(selectReservationById(current?.reservationId));
  const reservations = useAppSelector(selectReservations);

  const [search, setSearch] = useState('');
  const [onlyNeedsAction, setOnlyNeedsAction] = useState(false);
  const [busy, setBusy] = useState(false);

  const reservationStatusById = useMemo(() => {
    const map: Record<string, string> = {};
    for (const r of reservations) map[r.id] = r.status;
    return map;
  }, [reservations]);

  useEffect(() => {
    dispatch(fetchConversationsForTeam());
    dispatch(fetchReservations({}));
  }, [dispatch]);

  useEffect(() => {
    if (id) {
      dispatch(fetchConversationById(id));
    }
  }, [dispatch, id]);

  useEffect(() => {
    if (current?.reservationId) {
      dispatch(fetchReservationById(current.reservationId));
    }
  }, [dispatch, current?.reservationId]);

  const locale = i18n.language.startsWith('fr') ? 'fr-FR' : 'en-GB';

  const needsActionCount = useMemo(
    () => conversations.filter((c) => reservationStatusById[c.reservationId] === 'pending').length,
    [conversations, reservationStatusById]
  );

  const filtered = useMemo(() => {
    const sorted = [...conversations].sort(
      (a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime()
    );
    const byAction = onlyNeedsAction
      ? sorted.filter((c) => reservationStatusById[c.reservationId] === 'pending')
      : sorted;
    const q = search.trim().toLowerCase();
    if (!q) return byAction;
    return byAction.filter((c) =>
      c.reservationId.toLowerCase().includes(q) ||
      c.messages.some((m) => m.body.toLowerCase().includes(q))
    );
  }, [conversations, search, onlyNeedsAction, reservationStatusById]);

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

  return (
    <div className="h-[calc(100vh-4rem)] flex bg-white">
      {/* Left: conversations list */}
      <div className="w-72 xl:w-80 flex-shrink-0 border-r border-gray-100 flex flex-col bg-gray-50/40">
        <div className="px-5 pt-5 pb-3 border-b border-gray-100 bg-white">
          <h1 className="text-xl font-bold text-gray-900 tracking-tight">
            {t('admin.conversations.title')}
          </h1>
          <p className="text-xs text-gray-500 mt-0.5">
            {t('admin.conversations.subtitle')}
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
              className="w-full pl-9 pr-3 h-9 text-sm rounded-xl border border-gray-200 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 focus:bg-white transition-colors"
            />
          </div>
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
                : onlyNeedsAction
                  ? t('admin.conversations.noNeedsAction')
                  : t('admin.conversations.empty')}
            </div>
          )}

          <div className="space-y-1">
            {filtered.map((c) => (
              <ConversationListItem
                key={c.id}
                conversation={c}
                to={`/admin/conversations/${c.id}`}
                active={c.id === id}
                locale={locale}
                needsAction={reservationStatusById[c.reservationId] === 'pending'}
              />
            ))}
          </div>
        </div>
      </div>

      {/* Right: thread + host panel */}
      <div className="flex-1 min-w-0 flex flex-col">
        {!id && (
          <div className="flex-1 flex items-center justify-center bg-gray-50/40">
            <div className="text-center max-w-sm px-6">
              <div className="mx-auto w-16 h-16 rounded-2xl bg-blue-50 flex items-center justify-center mb-4">
                <svg className="text-blue-500" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
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
          <div className="flex-1 grid grid-cols-1 2xl:grid-cols-[1fr_300px] min-h-0">
            <div className="min-h-0 flex flex-col">
              <header className="px-5 py-3 border-b border-gray-100 bg-white flex items-center gap-3">
                <div className="flex-1 min-w-0">
                  <p className="text-[10px] uppercase tracking-wider text-gray-400 font-semibold">
                    {t('conversation.reservation')}
                  </p>
                  <p className="text-sm font-semibold text-gray-900 truncate">
                    {reservation ? reservation.guestName : current.reservationId.slice(0, 8) + '…'}
                  </p>
                </div>
                {reservation && (
                  <span className="text-xs text-gray-500 hidden sm:block">
                    {new Intl.DateTimeFormat(locale, { day: '2-digit', month: 'short' }).format(new Date(reservation.checkIn))}
                    {' → '}
                    {new Intl.DateTimeFormat(locale, { day: '2-digit', month: 'short' }).format(new Date(reservation.checkOut))}
                  </span>
                )}
                {reservation?.status === 'pending' && !readOnly && (
                  <div className="flex items-center gap-2 2xl:hidden">
                    <button
                      type="button"
                      onClick={handleAccept}
                      disabled={busy}
                      className="inline-flex items-center gap-1 h-8 px-3 rounded-lg text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 shadow-sm shadow-emerald-200"
                    >
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M20 6 9 17l-5-5" />
                      </svg>
                      {t('host.panel.accept')}
                    </button>
                    <button
                      type="button"
                      onClick={handleRefuse}
                      disabled={busy}
                      className="inline-flex items-center h-8 px-3 rounded-lg text-xs font-semibold text-rose-700 bg-white border border-rose-200 hover:bg-rose-50 disabled:opacity-60"
                    >
                      {t('host.panel.refuse')}
                    </button>
                  </div>
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

            <div className="hidden 2xl:block border-l border-gray-100 bg-gray-50/40 p-4 overflow-y-auto">
              {reservation && (
                <HostPanel
                  reservation={reservation}
                  locale={locale}
                  onAccept={handleAccept}
                  onRefuse={handleRefuse}
                  busy={busy}
                  readOnly={readOnly}
                />
              )}
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default AdminConversationsPage;
