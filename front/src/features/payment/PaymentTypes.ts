export interface CreatePaymentIntentPayload {
  amountCents: number;
  currency: string;
  description: string;
  metadata?: Record<string, string>;
}

export interface PaymentIntentResponse {
  paymentIntentId: string;
  clientSecret: string;
}
