import { createSlice, createAsyncThunk, PayloadAction } from '@reduxjs/toolkit';
import api from '../../services/api';
import {
  IdentityDocumentType,
  UserProfileState,
  VerificationResult,
} from './UserProfileTypes';

const initialState: UserProfileState = {
  verificationStatus: 'not_started',
  documentType: null,
  verifiedAt: null,
  status: 'idle',
  uploadProgress: 0,
  error: null,
};

interface SubmitPayload {
  userId: string;
  documentType: IdentityDocumentType;
  documentFile: File;
  selfieFile: File;
}

export const submitIdentityVerification = createAsyncThunk(
  'userProfile/submitVerification',
  async (payload: SubmitPayload, { dispatch, rejectWithValue }) => {
    try {
      const formData = new FormData();
      formData.append('documentType', payload.documentType);
      formData.append('document', payload.documentFile);
      formData.append('selfie', payload.selfieFile);

      const response = await api.post(
        `/api/users/${payload.userId}/identity-verification`,
        formData,
        {
          headers: { 'Content-Type': 'multipart/form-data' },
          onUploadProgress: (e) => {
            const progress = e.total ? Math.round((e.loaded / e.total) * 100) : 0;
            dispatch(setUploadProgress(progress));
          },
        }
      );
      return response.data as VerificationResult;
    } catch (err: any) {
      return rejectWithValue(
        err.response?.data?.detail || 'Erreur lors de la vérification d\'identité'
      );
    }
  }
);

export const fetchVerificationStatus = createAsyncThunk(
  'userProfile/fetchStatus',
  async (userId: string, { rejectWithValue }) => {
    try {
      const response = await api.get(`/api/users/${userId}/identity-verification`);
      return response.data as VerificationResult;
    } catch (err: any) {
      return rejectWithValue(
        err.response?.data?.detail || 'Erreur lors du chargement du statut'
      );
    }
  }
);

const userProfileSlice = createSlice({
  name: 'userProfile',
  initialState,
  reducers: {
    setUploadProgress(state, action: PayloadAction<number>) {
      state.uploadProgress = action.payload;
    },
    resetVerification(state) {
      state.status = 'idle';
      state.uploadProgress = 0;
      state.error = null;
    },
  },
  extraReducers: (builder) => {
    const storeResult = (state: UserProfileState, action: PayloadAction<VerificationResult>) => {
      state.status = 'succeeded';
      state.verificationStatus = action.payload.status;
      state.documentType = action.payload.documentType;
      state.verifiedAt = action.payload.verifiedAt;
    };

    builder
      .addCase(submitIdentityVerification.pending, (state) => {
        state.status = 'loading';
        state.uploadProgress = 0;
        state.error = null;
      })
      .addCase(submitIdentityVerification.fulfilled, storeResult)
      .addCase(submitIdentityVerification.rejected, (state, action) => {
        state.status = 'failed';
        state.error = (action.payload as string) ?? 'Erreur';
      })
      .addCase(fetchVerificationStatus.fulfilled, storeResult)
      .addCase(fetchVerificationStatus.rejected, (state, action) => {
        state.status = 'failed';
        state.error = (action.payload as string) ?? 'Erreur';
      });
  },
});

export const { setUploadProgress, resetVerification } = userProfileSlice.actions;
export default userProfileSlice.reducer;
