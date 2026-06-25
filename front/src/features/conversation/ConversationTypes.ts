export interface ConversationMessage {
  id: string;
  body: string;
  authorUserId: string | null;
  sentAt: string;
  isSystem: boolean;
}

export interface Conversation {
  '@id'?: string;
  id: string;
  reservationId: string;
  accommodationId: string;
  teamId: string;
  guestUserId: string;
  /** Guest's full name (first + last), so the host can identify their counterpart. */
  guestName?: string | null;
  /** Guest's avatar URL (relative), or null when they haven't uploaded a photo. */
  guestAvatarUrl?: string | null;
  createdAt: string;
  messages: ConversationMessage[];
}

export interface SendMessagePayload {
  conversationId: string;
  body: string;
}
