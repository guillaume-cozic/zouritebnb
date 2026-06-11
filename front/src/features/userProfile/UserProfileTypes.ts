export type VerificationStatus =
  | 'not_started'
  | 'pending'
  | 'verified'
  | 'rejected';

export type IdentityDocumentType = 'passport' | 'id_card' | 'driving_license';

/** Result returned by the identity-verification endpoints. */
export interface VerificationResult {
  status: VerificationStatus;
  documentType: IdentityDocumentType | null;
  verifiedAt: string | null;
}

export interface UserProfileState {
  /** Current known verification status of the logged-in user. */
  verificationStatus: VerificationStatus;
  documentType: IdentityDocumentType | null;
  verifiedAt: string | null;
  /** Lifecycle of the submit/fetch operation. */
  status: 'idle' | 'loading' | 'succeeded' | 'failed';
  uploadProgress: number;
  error: string | null;
}
