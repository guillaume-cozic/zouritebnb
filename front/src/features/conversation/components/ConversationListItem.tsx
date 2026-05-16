import React from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { Conversation, ConversationMessage } from '../ConversationTypes';

interface Props {
  conversation: Conversation;
  to: string;
  active?: boolean;
  locale: string;
  needsAction?: boolean;
}

const lastMessage = (conversation: Conversation): ConversationMessage | null => {
  if (conversation.messages.length === 0) return null;
  return conversation.messages[conversation.messages.length - 1];
};

const formatRelative = (iso: string, locale: string): string => {
  const date = new Date(iso);
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffHrs = diffMs / 3_600_000;

  if (diffHrs < 24 && date.toDateString() === now.toDateString()) {
    return new Intl.DateTimeFormat(locale, { hour: '2-digit', minute: '2-digit' }).format(date);
  }
  if (diffHrs < 24 * 7) {
    return new Intl.DateTimeFormat(locale, { weekday: 'short' }).format(date);
  }
  return new Intl.DateTimeFormat(locale, { day: '2-digit', month: 'short' }).format(date);
};

const initials = (name: string): string => {
  const parts = name.trim().split(/\s+/);
  if (parts.length === 0) return '?';
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
};

const avatarHueFromUuid = (uuid: string): number => {
  let hash = 0;
  for (let i = 0; i < uuid.length; i++) hash = (hash * 31 + uuid.charCodeAt(i)) | 0;
  return Math.abs(hash) % 360;
};

const ConversationListItem: React.FC<Props> = ({ conversation, to, active = false, locale, needsAction = false }) => {
  const { t } = useTranslation();
  const last = lastMessage(conversation);

  const previewLabel = t('conversation.itemTitle');
  const hue = avatarHueFromUuid(conversation.guestUserId);
  const avatar = initials(previewLabel);

  return (
    <Link
      to={to}
      aria-label={needsAction ? `${previewLabel} — ${t('conversation.needsActionBadge')}` : previewLabel}
      className={`block rounded-xl px-3 py-2.5 transition-all border ${
        active
          ? 'bg-blue-50/70 border-blue-200 shadow-sm'
          : needsAction
            ? 'bg-amber-50/40 border-transparent hover:border-amber-200 hover:bg-amber-50/70'
            : 'bg-white border-transparent hover:border-gray-200 hover:bg-gray-50/70'
      }`}
    >
      <div className="flex items-start gap-3">
        <div className="relative flex-shrink-0">
          <div
            className="w-10 h-10 rounded-full flex items-center justify-center text-white text-xs font-bold"
            style={{ background: `linear-gradient(135deg, hsl(${hue}, 65%, 55%), hsl(${(hue + 30) % 360}, 65%, 45%))` }}
          >
            {avatar}
          </div>
          {needsAction && (
            <span
              aria-hidden="true"
              className="absolute -top-0.5 -right-0.5 w-3 h-3 rounded-full bg-amber-500 ring-2 ring-white"
            />
          )}
        </div>

        <div className="flex-1 min-w-0">
          <div className="flex items-baseline justify-between gap-2">
            <p className={`text-sm font-semibold truncate ${active ? 'text-blue-900' : 'text-gray-900'}`}>
              {previewLabel}
            </p>
            {last && (
              <span className="text-[11px] text-gray-400 flex-shrink-0">
                {formatRelative(last.sentAt, locale)}
              </span>
            )}
          </div>
          <div className="flex items-center gap-2 mt-0.5">
            {last && (
              <p className={`text-xs truncate flex-1 ${last.isSystem ? 'italic text-gray-500' : 'text-gray-600'}`}>
                {last.body}
              </p>
            )}
            {needsAction && (
              <span className="flex-shrink-0 inline-flex items-center h-[18px] px-1.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-200">
                {t('conversation.needsActionBadge')}
              </span>
            )}
          </div>
        </div>
      </div>
    </Link>
  );
};

export default ConversationListItem;
