import { RootState } from '../../store';

export const selectCurrentAccommodation = (state: RootState) =>
  state.accommodation.current;

export const selectWizardStep = (state: RootState) =>
  state.accommodation.wizardStep;

export const selectAccommodationStatus = (state: RootState) =>
  state.accommodation.status;

export const selectAccommodationError = (state: RootState) =>
  state.accommodation.error;

export const selectPhotoUploadStatus = (state: RootState) =>
  state.accommodation.photoUploadStatus;
