export interface CreatePaymentIntentPayload {
  /** The server derives the amount, currency and metadata from these booking parameters. */
  accommodationId: string;
  checkIn: string;
  checkOut: string;
}

export interface PaymentIntentResponse {
  paymentIntentId: string;
  clientSecret: string;
}
