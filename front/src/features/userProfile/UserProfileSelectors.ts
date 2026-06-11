import { RootState } from '../../store';

export const selectVerificationStatus = (state: RootState) =>
  state.userProfile.verificationStatus;

export const selectVerificationDocumentType = (state: RootState) =>
  state.userProfile.documentType;

export const selectVerificationVerifiedAt = (state: RootState) =>
  state.userProfile.verifiedAt;

export const selectVerificationOperationStatus = (state: RootState) =>
  state.userProfile.status;

export const selectVerificationUploadProgress = (state: RootState) =>
  state.userProfile.uploadProgress;

export const selectVerificationError = (state: RootState) =>
  state.userProfile.error;
