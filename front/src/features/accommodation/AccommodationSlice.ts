import { createSlice, createAsyncThunk, PayloadAction } from '@reduxjs/toolkit';
import api from '../../services/api';
import {
  Accommodation,
  CreateAccommodationPayload,
  SetLocationPayload,
  SetCapacityPayload,
  SetAmenitiesPayload,
  AddPhotoPayload,
  DeletePhotoPayload,
  UpdatePricePayload,
  UpdateWeeklyPromotionPayload,
  SetCheckInOutPayload,
  ReorderPhotosPayload,
  UpdateDescriptionPayload,
  FormDrafts,
  WizardStep,
  AddressDraft,
} from './AccommodationTypes';

interface AccommodationState {
  current: Accommodation | null;
  wizardStep: WizardStep;
  status: 'idle' | 'loading' | 'succeeded' | 'failed';
  error: string | null;
  photoUploadStatus: 'idle' | 'loading' | 'succeeded' | 'failed';
  formDrafts: FormDrafts;
}

const initialState: AccommodationState = {
  current: null,
  wizardStep: 'description',
  status: 'idle',
  error: null,
  photoUploadStatus: 'idle',
  formDrafts: {},
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

export const setAmenities = createAsyncThunk(
  'accommodation/setAmenities',
  async ({ id, codes }: SetAmenitiesPayload, { rejectWithValue }) => {
    try {
      await api.put(
        `/api/accommodations/${id}/amenities`,
        { codes },
        { headers: { 'Content-Type': 'application/ld+json' } }
      );
      return { codes };
    } catch (err: any) {
      return rejectWithValue(
        err.response?.data?.detail || 'Erreur lors de la mise à jour des équipements'
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
      const baseURL = process.env.REACT_APP_API_URL || 'http://localhost:8080';
      const response = await fetch(`${baseURL}/api/accommodations/${id}/photos`, {
        method: 'POST',
        body: formData,
      });
      if (!response.ok) {
        const data = await response.json().catch(() => ({}));
        throw new Error(data.detail || `Upload failed (${response.status})`);
      }
      return file.name;
    } catch (err: any) {
      return rejectWithValue(err.message || "Erreur lors de l'upload de la photo");
    }
  }
);

export const deletePhoto = createAsyncThunk(
  'accommodation/deletePhoto',
  async ({ id, photoId }: DeletePhotoPayload, { rejectWithValue }) => {
    try {
      await api.delete(`/api/accommodations/${id}/photos/${photoId}`);
      return { photoId };
    } catch (err: any) {
      return rejectWithValue(
        err.response?.data?.detail || 'Erreur lors de la suppression de la photo'
      );
    }
  }
);

export const reorderPhotos = createAsyncThunk(
  'accommodation/reorderPhotos',
  async ({ id, photoIds }: ReorderPhotosPayload, { rejectWithValue }) => {
    try {
      await api.put(
        `/api/accommodations/${id}/photos/reorder`,
        { photoIds },
        { headers: { 'Content-Type': 'application/ld+json' } }
      );
      return { photoIds };
    } catch (err: any) {
      return rejectWithValue(
        err.response?.data?.detail || 'Erreur lors du réordonnancement des photos'
      );
    }
  }
);

export const updatePrice = createAsyncThunk(
  'accommodation/updatePrice',
  async ({ id, price }: UpdatePricePayload, { rejectWithValue }) => {
    try {
      await api.patch(
        `/api/accommodations/${id}/price`,
        { price },
        { headers: { 'Content-Type': 'application/merge-patch+json' } }
      );
      return { price };
    } catch (err: any) {
      return rejectWithValue(
        err.response?.data?.detail || 'Erreur lors de la mise à jour du prix'
      );
    }
  }
);

export const updateWeeklyPromotion = createAsyncThunk(
  'accommodation/updateWeeklyPromotion',
  async ({ id, weeklyPromotionPercentage }: UpdateWeeklyPromotionPayload, { rejectWithValue }) => {
    try {
      await api.patch(
        `/api/accommodations/${id}/weekly-promotion`,
        { weeklyPromotionPercentage },
        { headers: { 'Content-Type': 'application/merge-patch+json' } }
      );
      return { weeklyPromotionPercentage };
    } catch (err: any) {
      return rejectWithValue(
        err.response?.data?.detail || 'Erreur lors de la mise à jour de la promotion'
      );
    }
  }
);

export const setCheckInOut = createAsyncThunk(
  'accommodation/setCheckInOut',
  async ({ id, checkIn, checkOut }: SetCheckInOutPayload, { rejectWithValue }) => {
    try {
      await api.put(
        `/api/accommodations/${id}/check-in-out`,
        { checkIn, checkOut },
        { headers: { 'Content-Type': 'application/ld+json' } }
      );
      return { checkIn, checkOut };
    } catch (err: any) {
      return rejectWithValue(
        err.response?.data?.detail || 'Erreur lors de la mise à jour des horaires'
      );
    }
  }
);

export const updateDescription = createAsyncThunk(
  'accommodation/updateDescription',
  async ({ id, title, description }: UpdateDescriptionPayload, { rejectWithValue }) => {
    try {
      await api.put(
        `/api/accommodations/${id}/description`,
        { title, description },
        { headers: { 'Content-Type': 'application/ld+json' } }
      );
      return { title, description };
    } catch (err: any) {
      return rejectWithValue(
        err.response?.data?.detail || 'Erreur lors de la mise à jour de la description'
      );
    }
  }
);

export const fetchAccommodation = createAsyncThunk(
  'accommodation/fetchOne',
  async (id: string, { rejectWithValue }) => {
    try {
      const response = await api.get(`/api/accommodations/${id}`);
      return response.data as Accommodation;
    } catch (err: any) {
      return rejectWithValue(
        err.response?.data?.detail || 'Erreur lors du chargement'
      );
    }
  }
);

export const uploadPhotos = createAsyncThunk(
  'accommodation/uploadPhotos',
  async ({ id, files }: { id: string; files: File[] }, { dispatch, rejectWithValue }) => {
    try {
      for (const file of files) {
        await dispatch(addPhoto({ id, file })).unwrap();
      }
      await dispatch(fetchAccommodation(id));
      return { count: files.length };
    } catch (err: any) {
      return rejectWithValue(typeof err === 'string' ? err : err?.message || "Erreur lors de l'upload");
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
    saveDraft(state, action: PayloadAction<Partial<FormDrafts>>) {
      Object.assign(state.formDrafts, action.payload);
    },
    resetWizard() {
      return initialState;
    },
    wizardStepLeft(
      state,
      action: PayloadAction<{ draft?: Partial<FormDrafts>; target: WizardStep }>
    ) {
      if (action.payload.draft) {
        Object.assign(state.formDrafts, action.payload.draft);
      }
      state.wizardStep = action.payload.target;
      state.error = null;
    },
    addressSubmitted(
      state,
      action: PayloadAction<{ id: string; address: AddressDraft }>
    ) {
      state.formDrafts.address = action.payload.address;
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
        state.formDrafts.address = {
          street: action.payload.street,
          city: action.payload.city,
          zipCode: action.payload.zipCode,
          country: action.payload.country,
          latitude: action.payload.latitude,
          longitude: action.payload.longitude,
        };
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
        state.formDrafts.capacity = action.payload;
        state.wizardStep = 'amenities';
      })
      .addCase(setCapacity.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload as string;
      })
      // Amenities
      .addCase(setAmenities.pending, (state) => {
        state.status = 'loading';
        state.error = null;
      })
      .addCase(setAmenities.fulfilled, (state, action) => {
        state.status = 'succeeded';
        if (state.current) {
          state.current.amenities = action.payload.codes;
        }
        state.formDrafts.amenities = action.payload.codes;
        state.wizardStep = 'address';
      })
      .addCase(setAmenities.rejected, (state, action) => {
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
      })
      // Delete photo
      .addCase(deletePhoto.pending, (state) => {
        state.error = null;
      })
      .addCase(deletePhoto.fulfilled, (state, action) => {
        if (state.current?.photos) {
          state.current.photos = state.current.photos.filter(
            (p) => p.id !== action.payload.photoId
          );
        }
      })
      .addCase(deletePhoto.rejected, (state, action) => {
        state.error = action.payload as string;
      })
      // Update price
      .addCase(updatePrice.pending, (state) => {
        state.status = 'loading';
        state.error = null;
      })
      .addCase(updatePrice.fulfilled, (state, action) => {
        state.status = 'succeeded';
        if (state.current) {
          state.current.price = action.payload.price;
        }
      })
      .addCase(updatePrice.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload as string;
      })
      // Update weekly promotion
      .addCase(updateWeeklyPromotion.pending, (state) => {
        state.status = 'loading';
        state.error = null;
      })
      .addCase(updateWeeklyPromotion.fulfilled, (state, action) => {
        state.status = 'succeeded';
        if (state.current) {
          state.current.weeklyPromotionPercentage = action.payload.weeklyPromotionPercentage;
        }
      })
      .addCase(updateWeeklyPromotion.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload as string;
      })
      // Check-in/out
      .addCase(setCheckInOut.pending, (state) => {
        state.status = 'loading';
        state.error = null;
      })
      .addCase(setCheckInOut.fulfilled, (state, action) => {
        state.status = 'succeeded';
        if (state.current) {
          state.current.checkIn = action.payload.checkIn;
          state.current.checkOut = action.payload.checkOut;
        }
      })
      .addCase(setCheckInOut.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload as string;
      })
      // Update description
      .addCase(updateDescription.pending, (state) => {
        state.status = 'loading';
        state.error = null;
      })
      .addCase(updateDescription.fulfilled, (state, action) => {
        state.status = 'succeeded';
        if (state.current) {
          state.current.title = action.payload.title;
          state.current.description = action.payload.description;
        }
      })
      .addCase(updateDescription.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload as string;
      })
      // Fetch one
      .addCase(fetchAccommodation.pending, (state) => {
        state.status = 'loading';
        state.error = null;
      })
      .addCase(fetchAccommodation.fulfilled, (state, action) => {
        state.status = 'succeeded';
        state.current = action.payload;
      })
      .addCase(fetchAccommodation.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload as string;
      });
  },
});

export const {
  goToStep,
  saveDraft,
  resetWizard,
  wizardStepLeft,
  addressSubmitted,
} = accommodationSlice.actions;
export default accommodationSlice.reducer;
