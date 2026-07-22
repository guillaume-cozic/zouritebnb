export interface CreateDonationIntentPayload {
  solidarityProjectId: string;
  /** Free amount chosen by the donor, in cents (1 € to 10 000 €). */
  amountCents: number;
}

export interface DonationIntentResponse {
  paymentIntentId: string;
  clientSecret: string;
}
