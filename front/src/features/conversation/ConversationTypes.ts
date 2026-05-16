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
  createdAt: string;
  messages: ConversationMessage[];
}

export interface SendMessagePayload {
  conversationId: string;
  authorUserId: string;
  body: string;
}
