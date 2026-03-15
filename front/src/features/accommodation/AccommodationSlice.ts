import { createSlice, createAsyncThunk, PayloadAction } from '@reduxjs/toolkit';
import api from '../../services/api';
import {
  Accommodation,
  CreateAccommodationPayload,
  SetLocationPayload,
  SetCapacityPayload,
  AddPhotoPayload,
  WizardStep,
} from './AccommodationTypes';

interface AccommodationState {
  current: Accommodation | null;
  wizardStep: WizardStep;
  status: 'idle' | 'loading' | 'succeeded' | 'failed';
  error: string | null;
  photoUploadStatus: 'idle' | 'loading' | 'succeeded' | 'failed';
}

const initialState: AccommodationState = {
  current: null,
  wizardStep: 'description',
  status: 'idle',
  error: null,
  photoUploadStatus: 'idle',
};

export const createAccommodation = createAsyncThunk(
  'accommodation/create',
  async (payload: CreateAccommodationPayload, { rejectWithValue }) => {
    try {
      const response = await api.post('/api/accommodations', payload, {
        headers: { 'Content-Type': 'application/ld+json' },
      });
      return { ...payload, id: response.data.id } as Accommodation;
    } catch (err: any) {
      return rejectWithValue(
        err.response?.data?.detail || 'Erreur lors de la création'
      );
    }
  }
);

export const setLocation = createAsyncThunk(
  'accommodation/setLocation',
  async (
    { id, street, city, zipCode, country, latitude, longitude }: SetLocationPayload,
    { rejectWithValue }
  ) => {
    try {
      await api.put(
        `/api/accommodations/${id}/address`,
        { street, city, zipCode, country },
        { headers: { 'Content-Type': 'application/ld+json' } }
      );

      if (latitude !== undefined && longitude !== undefined) {
        await api.put(
          `/api/accommodations/${id}/geolocation`,
          { latitude, longitude },
          { headers: { 'Content-Type': 'application/ld+json' } }
        );
      }

      return { street, city, zipCode, country, latitude, longitude };
    } catch (err: any) {
      return rejectWithValue(
        err.response?.data?.detail || "Erreur lors de la mise à jour de la localisation"
      );
    }
  }
);

export const setCapacity = createAsyncThunk(
  'accommodation/setCapacity',
  async (
    { id, bedrooms, bathrooms, maxGuests, singleBeds, doubleBeds }: SetCapacityPayload,
    { rejectWithValue }
  ) => {
    try {
      await api.put(
        `/api/accommodations/${id}/capacity`,
        { bedrooms, bathrooms, maxGuests, singleBeds, doubleBeds },
        { headers: { 'Content-Type': 'application/ld+json' } }
      );
      return { bedrooms, bathrooms, maxGuests, singleBeds, doubleBeds };
    } catch (err: any) {
      return rejectWithValue(
        err.response?.data?.detail || 'Erreur lors de la mise à jour de la capacité'
      );
    }
  }
);

export const addPhoto = createAsyncThunk(
  'accommodation/addPhoto',
  async ({ id, file }: AddPhotoPayload, { rejectWithValue }) => {
    try {
      const formData = new FormData();
      formData.append('file', file);
      await api.post(`/api/accommodations/${id}/photos`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      return file.name;
    } catch (err: any) {
      return rejectWithValue(
        err.response?.data?.detail || "Erreur lors de l'upload de la photo"
      );
    }
  }
);

const accommodationSlice = createSlice({
  name: 'accommodation',
  initialState,
  reducers: {
    goToStep(state, action: PayloadAction<WizardStep>) {
      state.wizardStep = action.payload;
      state.error = null;
    },
    resetWizard() {
      return initialState;
    },
  },
  extraReducers: (builder) => {
    builder
      // Create
      .addCase(createAccommodation.pending, (state) => {
        state.status = 'loading';
        state.error = null;
      })
      .addCase(createAccommodation.fulfilled, (state, action) => {
        state.status = 'succeeded';
        state.current = action.payload;
        state.wizardStep = 'capacity';
      })
      .addCase(createAccommodation.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload as string;
      })
      // Location (address + geolocation combined)
      .addCase(setLocation.pending, (state) => {
        state.status = 'loading';
        state.error = null;
      })
      .addCase(setLocation.fulfilled, (state, action) => {
        state.status = 'succeeded';
        if (state.current) {
          Object.assign(state.current, action.payload);
        }
        state.wizardStep = 'photos';
      })
      .addCase(setLocation.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload as string;
      })
      // Capacity
      .addCase(setCapacity.pending, (state) => {
        state.status = 'loading';
        state.error = null;
      })
      .addCase(setCapacity.fulfilled, (state, action) => {
        state.status = 'succeeded';
        if (state.current) {
          Object.assign(state.current, action.payload);
        }
        state.wizardStep = 'address';
      })
      .addCase(setCapacity.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload as string;
      })
      // Photo
      .addCase(addPhoto.pending, (state) => {
        state.photoUploadStatus = 'loading';
      })
      .addCase(addPhoto.fulfilled, (state) => {
        state.photoUploadStatus = 'succeeded';
      })
      .addCase(addPhoto.rejected, (state, action) => {
        state.photoUploadStatus = 'failed';
        state.error = action.payload as string;
      });
  },
});

export const { goToStep, resetWizard } = accommodationSlice.actions;
export default accommodationSlice.reducer;
