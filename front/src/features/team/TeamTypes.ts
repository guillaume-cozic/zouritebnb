export interface Team {
  id: string;
  favoriteSolidarityProjectId: string | null;
  iban: string | null;
  bic: string | null;
  bankAccountHolderName: string | null;
}

export interface BankAccountPayload {
  iban: string | null;
  bic: string | null;
  holderName: string | null;
}

export interface TeamInvitation {
  id: string;
  email: string;
  status: 'pending' | 'accepted' | 'cancelled';
  createdAt: string;
}

export const DEFAULT_TEAM_ID = '00000000-0000-4000-8000-000000000001';
