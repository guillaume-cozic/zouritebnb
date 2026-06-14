/**
 * Client-side read tracking for conversations.
 *
 * The API has no notion of read/unread messages, so we persist, per conversation,
 * the timestamp of the most recent message the user has seen (i.e. opened). A message
 * is "unread" when it is newer than that timestamp and was not sent by the user.
 */
const STORAGE_KEY = 'conversation.reads';

export type ConversationReads = Record<string, string>; // conversationId -> ISO lastReadAt

export const loadConversationReads = (): ConversationReads => {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    return raw ? (JSON.parse(raw) as ConversationReads) : {};
  } catch {
    return {};
  }
};

export const saveConversationReads = (reads: ConversationReads): void => {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(reads));
  } catch {
    /* storage unavailable (private mode, quota) — read state is best-effort */
  }
};
