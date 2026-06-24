import { createSelector } from '@reduxjs/toolkit';
import { RootState } from '../../store';

export const selectConversations = (state: RootState) =>
  state.conversation?.items ?? [];

/**
 * Number of unread messages across the user's conversations: incoming, non-system
 * messages newer than the last time the user opened that conversation. Used for the
 * navbar notification badge.
 */
export const selectUnreadCount = (state: RootState): number => {
  const userId = state.auth?.user?.id ?? null;
  const reads = state.conversation?.reads ?? {};
  const conversations = state.conversation?.items ?? [];

  let count = 0;
  for (const conversation of conversations) {
    const lastReadMs = reads[conversation.id]
      ? new Date(reads[conversation.id]).getTime()
      : 0;
    for (const message of conversation.messages) {
      if (message.isSystem) continue;
      if (message.authorUserId === userId) continue;
      if (new Date(message.sentAt).getTime() > lastReadMs) count++;
    }
  }
  return count;
};

/**
 * Unread message count per conversation id (same rule as {@link selectUnreadCount}:
 * incoming, non-system messages newer than the last open). Conversations with none
 * are omitted. Memoized so the returned map keeps a stable reference.
 */
export const selectUnreadCountByConversation = createSelector(
  [
    (state: RootState) => state.auth?.user?.id ?? null,
    (state: RootState) => state.conversation?.reads ?? {},
    (state: RootState) => state.conversation?.items ?? [],
  ],
  (userId, reads, conversations): Record<string, number> => {
    const result: Record<string, number> = {};
    for (const conversation of conversations) {
      const lastReadMs = reads[conversation.id]
        ? new Date(reads[conversation.id]).getTime()
        : 0;
      let count = 0;
      for (const message of conversation.messages) {
        if (message.isSystem) continue;
        if (message.authorUserId === userId) continue;
        if (new Date(message.sentAt).getTime() > lastReadMs) count++;
      }
      if (count > 0) result[conversation.id] = count;
    }
    return result;
  }
);

export const selectConversationsStatus = (state: RootState) =>
  state.conversation?.listStatus ?? 'idle';

export const selectConversationsError = (state: RootState) =>
  state.conversation?.listError ?? null;

export const selectCurrentConversation = (state: RootState) =>
  state.conversation?.current ?? null;

export const selectCurrentConversationStatus = (state: RootState) =>
  state.conversation?.currentStatus ?? 'idle';

export const selectCurrentConversationError = (state: RootState) =>
  state.conversation?.currentError ?? null;

export const selectSendMessageStatus = (state: RootState) =>
  state.conversation?.sendStatus ?? 'idle';

export const selectSendMessageError = (state: RootState) =>
  state.conversation?.sendError ?? null;
