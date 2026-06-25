import React, { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { sendMessage, clearSendError } from '../ConversationSlice';
import { selectSendMessageStatus, selectSendMessageError } from '../ConversationSelectors';
import { Conversation, ConversationMessage } from '../ConversationTypes';
import { selectAuthUser } from '../../auth/AuthSelectors';
import { fetchHostProfile } from '../../hostProfile/HostProfileSlice';
import { selectHostProfileByTeamId } from '../../hostProfile/HostProfileSelectors';
import { Avatar } from '../../../components/ui';

interface Props {
  conversation: Conversation;
  currentUserId: string;
  readOnly?: boolean;
  readOnlyMessage?: string;
}

const formatTime = (iso: string, locale: string): string =>
  new Intl.DateTimeFormat(locale, {
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(iso));

const formatDayHeader = (iso: string, locale: string): string =>
  new Intl.DateTimeFormat(locale, {
    weekday: 'long',
    day: '2-digit',
    month: 'long',
  }).format(new Date(iso));

const sameDay = (a: string, b: string): boolean =>
  new Date(a).toDateString() === new Date(b).toDateString();

const SystemBubble: React.FC<{ message: ConversationMessage; locale: string }> = ({ message, locale }) => (
  <div className="flex justify-center my-4">
    <div className="max-w-xl rounded-full bg-gray-100/80 text-gray-600 px-4 py-2 text-xs text-center whitespace-pre-line italic">
      {message.body}
      <span className="ml-2 text-[10px] not-italic text-gray-400">
        {formatTime(message.sentAt, locale)}
      </span>
    </div>
  </div>
);

const UserBubble: React.FC<{
  message: ConversationMessage;
  locale: string;
  isHost: boolean;
  authorLabel: string;
  mine: boolean;
  avatarUrl?: string | null;
  avatarName: string;
}> = ({ message, locale, isHost, authorLabel, mine, avatarUrl, avatarName }) => {
  // Host messages always on the LEFT (white card, emerald accent), guest always on the RIGHT (blue gradient).
  const onRight = !isHost;
  const avatar = (
    <Avatar
      avatarUrl={avatarUrl}
      name={avatarName}
      sizeClassName="w-8 h-8"
      textClassName="text-xs"
      fallbackClassName={isHost ? 'bg-emerald-600 text-white' : 'bg-primary-600 text-white'}
      className="shrink-0 self-end border border-white shadow-sm"
    />
  );
  return (
    <div className={`flex items-end gap-2 ${onRight ? 'justify-end' : 'justify-start'} mb-2`}>
      {!onRight && avatar}
      <div className={`flex flex-col ${onRight ? 'items-end' : 'items-start'} max-w-[80%] sm:max-w-md`}>
        <span className={`text-[10px] uppercase tracking-wider font-bold mb-1 px-1 ${isHost ? 'text-emerald-700' : 'text-primary-700'}`}>
          {authorLabel}
          {mine && <span className="ml-1 normal-case text-gray-400 font-medium">· vous</span>}
        </span>
        <div
          className={`rounded-2xl px-4 py-2.5 text-sm whitespace-pre-line shadow-sm ${
            isHost
              ? 'bg-white border border-emerald-200 text-gray-800 rounded-bl-md'
              : 'bg-gradient-to-br from-primary-600 to-primary-700 text-white rounded-br-md'
          }`}
        >
          <div className="leading-relaxed">{message.body}</div>
          <div className={`text-[10px] mt-1 ${isHost ? 'text-gray-400' : 'text-primary-100'}`}>
            {formatTime(message.sentAt, locale)}
          </div>
        </div>
      </div>
      {onRight && avatar}
    </div>
  );
};

const DayDivider: React.FC<{ iso: string; locale: string }> = ({ iso, locale }) => (
  <div className="flex items-center gap-3 my-4">
    <div className="flex-1 h-px bg-gray-200" />
    <span className="text-[11px] uppercase tracking-wider font-semibold text-gray-400">
      {formatDayHeader(iso, locale)}
    </span>
    <div className="flex-1 h-px bg-gray-200" />
  </div>
);

const ConversationThread: React.FC<Props> = ({ conversation, currentUserId, readOnly = false, readOnlyMessage }) => {
  const { t, i18n } = useTranslation();
  const dispatch = useAppDispatch();
  const sendStatus = useAppSelector(selectSendMessageStatus);
  const sendError = useAppSelector(selectSendMessageError);
  const authUser = useAppSelector(selectAuthUser);
  const host = useAppSelector(selectHostProfileByTeamId(conversation.teamId));

  // The host avatar/name shown on host-side bubbles comes from the public host profile.
  useEffect(() => {
    dispatch(fetchHostProfile(conversation.teamId));
  }, [dispatch, conversation.teamId]);

  const myName = authUser?.firstName || authUser?.email.split('@')[0] || t('conversation.guest');
  const hostName =
    [host?.firstName, host?.lastName].filter(Boolean).join(' ').trim() || t('conversation.host');
  // The guest's identity (name + avatar) comes from the conversation itself, so the host
  // sees who they are talking to. On the traveler side these resolve to the guest's own
  // messages (handled via `mine`), so the fallback to a generic label is never reached.
  const guestDisplayName = conversation.guestName?.trim() || t('conversation.guest');
  const guestAvatarUrl = conversation.guestAvatarUrl ?? null;

  const [body, setBody] = useState('');
  const messagesEndRef = useRef<HTMLDivElement | null>(null);

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [conversation.messages.length, conversation.id]);

  useEffect(() => {
    return () => {
      dispatch(clearSendError());
    };
  }, [dispatch]);

  const locale = i18n.language.startsWith('fr') ? 'fr-FR' : 'en-GB';

  const handleSend = async (e: React.FormEvent) => {
    e.preventDefault();
    const trimmed = body.trim();
    if (!trimmed) return;
    const result = await dispatch(
      sendMessage({
        conversationId: conversation.id,
        body: trimmed,
      })
    );
    if (sendMessage.fulfilled.match(result)) {
      setBody('');
    }
  };

  return (
    <div className="flex flex-col h-full bg-gradient-to-b from-gray-50/50 to-gray-50">
      <div className="flex-1 overflow-y-auto px-4 sm:px-6 py-5">
        {conversation.messages.map((m, idx) => {
          const prev = conversation.messages[idx - 1];
          const showDivider = !prev || !sameDay(prev.sentAt, m.sentAt);
          const isGuest = m.authorUserId === conversation.guestUserId;
          const isHost = !m.isSystem && !isGuest;
          const mine = m.authorUserId === currentUserId;
          // My own photo for my messages; the host's photo for host-side messages;
          // the guest's photo (from the conversation) for guest-side messages.
          const avatarUrl = mine ? authUser?.avatarUrl : isHost ? host?.avatarUrl : guestAvatarUrl;
          const avatarName = mine ? myName : isHost ? hostName : guestDisplayName;
          return (
            <React.Fragment key={m.id}>
              {showDivider && <DayDivider iso={m.sentAt} locale={locale} />}
              {m.isSystem ? (
                <SystemBubble message={m} locale={locale} />
              ) : (
                <UserBubble
                  message={m}
                  locale={locale}
                  isHost={isHost}
                  authorLabel={isHost ? t('conversation.host') : t('conversation.guest')}
                  mine={mine}
                  avatarUrl={avatarUrl}
                  avatarName={avatarName}
                />
              )}
            </React.Fragment>
          );
        })}
        <div ref={messagesEndRef} />
      </div>

      {readOnly ? (
        <div className="border-t border-gray-100 bg-amber-50/60 px-4 sm:px-6 py-3 flex items-center gap-2 text-sm text-amber-800">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="flex-shrink-0">
            <rect x="3" y="11" width="18" height="11" rx="2" />
            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
          </svg>
          <span className="flex-1">{readOnlyMessage || t('conversation.readOnly')}</span>
          <a href="/login" className="font-semibold text-amber-900 underline underline-offset-2 hover:text-amber-700 whitespace-nowrap">
            {t('conversation.login')}
          </a>
        </div>
      ) : (
        <form onSubmit={handleSend} className="border-t border-gray-100 bg-white px-4 sm:px-6 py-3">
          <div className="flex gap-2 items-end">
            <textarea
              value={body}
              onChange={(e) => setBody(e.target.value)}
              rows={1}
              maxLength={5000}
              placeholder={t('conversation.placeholder') as string}
              className="flex-1 resize-none border border-gray-200 bg-gray-50 rounded-2xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 focus:bg-white max-h-32 transition-colors"
              onKeyDown={(e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                  e.preventDefault();
                  handleSend(e as unknown as React.FormEvent);
                }
              }}
            />
            <button
              type="submit"
              disabled={!body.trim() || sendStatus === 'loading'}
              className="rounded-full bg-gradient-to-br from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white h-10 w-10 flex items-center justify-center disabled:opacity-50 transition-all shadow-sm shadow-primary-200 disabled:shadow-none"
              aria-label={t('conversation.send') as string}
            >
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                <path d="M22 2 11 13" />
                <path d="M22 2l-7 20-4-9-9-4 20-7Z" />
              </svg>
            </button>
          </div>
          {sendError && (
            <p className="mt-2 text-xs text-red-600">{sendError}</p>
          )}
        </form>
      )}
    </div>
  );
};

export default ConversationThread;
