export interface ConversationMessage {
  id: string;
  /** Message text, or null for a photo-only message. */
  body: string | null;
  authorUserId: string | null;
  sentAt: string;
  isSystem: boolean;
  /** Relative URL of the attached photo, absent/null for text messages. */
  attachmentUrl?: string | null;
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

export interface SendAttachmentPayload {
  conversationId: string;
  file: File;
  /** Optional caption sent along with the photo. */
  body?: string;
}
