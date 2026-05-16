import { RootState } from '../../store';

export const selectConversations = (state: RootState) =>
  state.conversation?.items ?? [];

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
