import { createSlice, createAsyncThunk, createAction, isAnyOf, PayloadAction } from '@reduxjs/toolkit';
import api from '../../services/api';
import { extractErrorMessage } from '../../services/errors';
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
  UpdateDynamicPricingPayload,
  UpdatePricePeriodsPayload,
  PricePeriod,
  UpdateCancellationPolicyPayload,
  UpdateInstantBookingPayload,
  UpdateTypePayload,
  UpdateStayConstraintsPayload,
  UpdateHouseRulesPayload,
  CancellationPolicy,
  AccommodationType,
  SetCheckInOutPayload,
  ReorderPhotosPayload,
  UpdateDescriptionPayload,
  FormDrafts,
  WizardStep,
  AddressDraft,
} from './AccommodationTypes';

export type AutoSaveStatus = 'idle' | 'saving' | 'saved' | 'error';

/** Sections of the edit page, used to display a per-section auto-save badge. */
export type EditSection = 'description' | 'price' | 'capacity' | 'amenities' | 'location' | 'checkinout' | 'cancellation';

/**
 * Single business intent dispatched by the edit page when the user modifies a
 * field. A listener debounces and runs the matching update thunk — the
 * component never orchestrates the save.
 */
export type AccommodationFieldEditedPayload =
  | { field: 'description'; id: string; title: string; description: string }
  | { field: 'price'; id: string; price: number }
  | { field: 'weeklyPromotion'; id: string; weeklyPromotionPercentage: number | null }
  | { field: 'dynamicPricing'; id: string; weekendSurchargePercentage: number | null; lastMinuteDiscountPercentage: number | null; lastMinuteDays: number | null }
  | { field: 'pricePeriods'; id: string; pricePeriods: PricePeriod[] }
  | { field: 'cancellationPolicy'; id: string; cancellationPolicy: CancellationPolicy }
  | { field: 'instantBooking'; id: string; instantBooking: boolean }
  | { field: 'type'; id: string; type: AccommodationType | null }
  | { field: 'stayConstraints'; id: string; minNights: number | null; maxNights: number | null }
  | { field: 'houseRules'; id: string; smokingAllowed: boolean; petsAllowed: boolean; partiesAllowed: boolean; houseRulesNotes: string | null }
  | { field: 'capacity'; id: string; bedrooms: number; bathrooms: number; maxGuests: number; singleBeds: number; doubleBeds: number }
  | { field: 'amenities'; id: string; codes: string[] }
  | { field: 'checkInOut'; id: string; checkIn: string; checkOut: string }
  | { field: 'location'; id: string; street: string; city: string; zipCode: string; country: string; latitude?: number; longitude?: number };

export type AccommodationEditField = AccommodationFieldEditedPayload['field'];

export const accommodationFieldEdited = createAction<AccommodationFieldEditedPayload>(
  'accommodation/fieldEdited'
);

/** Intent: the edit page was opened, the listener loads the accommodation. */
export const editPageOpened = createAction<{ id: string }>('accommodation/editPageOpened');

/** Badge section displaying the save status of a given edited field. */
export const editSectionForField = (field: AccommodationEditField): EditSection => {
  switch (field) {
    case 'weeklyPromotion':
    case 'dynamicPricing':
    case 'pricePeriods':
      return 'price';
    case 'checkInOut':
    case 'houseRules':
      return 'checkinout';
    case 'cancellationPolicy':
    case 'instantBooking':
    case 'stayConstraints':
      return 'cancellation';
    case 'type':
      return 'description';
    default:
      return field;
  }
};

interface AccommodationState {
  current: Accommodation | null;
  wizardStep: WizardStep;
  status: 'idle' | 'loading' | 'succeeded' | 'failed';
  error: string | null;
  photoUploadStatus: 'idle' | 'loading' | 'succeeded' | 'failed';
  formDrafts: FormDrafts;
  /** Per-section auto-save badge status on the edit page. */
  editSaveStatus: Partial<Record<EditSection, AutoSaveStatus>>;
}

const initialState: AccommodationState = {
  current: null,
  wizardStep: 'description',
  status: 'idle',
  error: null,
  photoUploadStatus: 'idle',
  formDrafts: {},
  editSaveStatus: {},
};

export const createAccommodation = createAsyncThunk(
  'accommodation/create',
  async (payload: CreateAccommodationPayload, { rejectWithValue }) => {
    try {
      const response = await api.post('/api/accommodations', payload, {
        headers: { 'Content-Type': 'application/ld+json' },
      });
      return { ...payload, id: response.data.id } as Accommodation;
    } catch (err) {
      return rejectWithValue(
        extractErrorMessage(err, 'Erreur lors de la création')
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
    } catch (err) {
      return rejectWithValue(
        extractErrorMessage(err, "Erreur lors de la mise à jour de la localisation")
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
    } catch (err) {
      return rejectWithValue(
        extractErrorMessage(err, 'Erreur lors de la mise à jour de la capacité')
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
    } catch (err) {
      return rejectWithValue(
        extractErrorMessage(err, 'Erreur lors de la mise à jour des équipements')
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
      const baseURL = import.meta.env.VITE_API_URL || 'http://localhost:8080';
      const response = await fetch(`${baseURL}/api/accommodations/${id}/photos`, {
        method: 'POST',
        body: formData,
      });
      if (!response.ok) {
        const data = await response.json().catch(() => ({}));
        throw new Error(data.detail || `Upload failed (${response.status})`);
      }
      return file.name;
    } catch (err) {
      return rejectWithValue(extractErrorMessage(err, "Erreur lors de l'upload de la photo"));
    }
  }
);

export const deletePhoto = createAsyncThunk(
  'accommodation/deletePhoto',
  async ({ id, photoId }: DeletePhotoPayload, { rejectWithValue }) => {
    try {
      await api.delete(`/api/accommodations/${id}/photos/${photoId}`);
      return { photoId };
    } catch (err) {
      return rejectWithValue(
        extractErrorMessage(err, 'Erreur lors de la suppression de la photo')
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
    } catch (err) {
      return rejectWithValue(
        extractErrorMessage(err, 'Erreur lors du réordonnancement des photos')
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
    } catch (err) {
      return rejectWithValue(
        extractErrorMessage(err, 'Erreur lors de la mise à jour du prix')
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
    } catch (err) {
      return rejectWithValue(
        extractErrorMessage(err, 'Erreur lors de la mise à jour de la promotion')
      );
    }
  }
);

export const updateDynamicPricing = createAsyncThunk(
  'accommodation/updateDynamicPricing',
  async (
    { id, weekendSurchargePercentage, lastMinuteDiscountPercentage, lastMinuteDays }: UpdateDynamicPricingPayload,
    { rejectWithValue }
  ) => {
    try {
      await api.patch(
        `/api/accommodations/${id}/dynamic-pricing`,
        { weekendSurchargePercentage, lastMinuteDiscountPercentage, lastMinuteDays },
        { headers: { 'Content-Type': 'application/merge-patch+json' } }
      );
      return { weekendSurchargePercentage, lastMinuteDiscountPercentage, lastMinuteDays };
    } catch (err) {
      return rejectWithValue(
        extractErrorMessage(err, 'Erreur lors de la mise à jour de la tarification dynamique')
      );
    }
  }
);

export const updatePricePeriods = createAsyncThunk(
  'accommodation/updatePricePeriods',
  async ({ id, pricePeriods }: UpdatePricePeriodsPayload, { rejectWithValue }) => {
    try {
      await api.put(
        `/api/accommodations/${id}/price-periods`,
        { pricePeriods },
        { headers: { 'Content-Type': 'application/ld+json' } }
      );
      return { pricePeriods };
    } catch (err) {
      return rejectWithValue(
        extractErrorMessage(err, 'Erreur lors de la mise à jour des tarifs par période')
      );
    }
  }
);

export const updateCancellationPolicy = createAsyncThunk(
  'accommodation/updateCancellationPolicy',
  async ({ id, cancellationPolicy }: UpdateCancellationPolicyPayload, { rejectWithValue }) => {
    try {
      await api.patch(
        `/api/accommodations/${id}/cancellation-policy`,
        { cancellationPolicy },
        { headers: { 'Content-Type': 'application/merge-patch+json' } }
      );
      return { cancellationPolicy };
    } catch (err) {
      return rejectWithValue(
        extractErrorMessage(err, "Erreur lors de la mise à jour de la politique d'annulation")
      );
    }
  }
);

export const updateInstantBooking = createAsyncThunk(
  'accommodation/updateInstantBooking',
  async ({ id, instantBooking }: UpdateInstantBookingPayload, { rejectWithValue }) => {
    try {
      await api.patch(
        `/api/accommodations/${id}/instant-booking`,
        { instantBooking },
        { headers: { 'Content-Type': 'application/merge-patch+json' } }
      );
      return { instantBooking };
    } catch (err) {
      return rejectWithValue(
        extractErrorMessage(err, 'Erreur lors de la mise à jour de la réservation instantanée')
      );
    }
  }
);

export const updateType = createAsyncThunk(
  'accommodation/updateType',
  async ({ id, type }: UpdateTypePayload, { rejectWithValue }) => {
    try {
      await api.patch(
        `/api/accommodations/${id}/type`,
        { type },
        { headers: { 'Content-Type': 'application/merge-patch+json' } }
      );
      return { type };
    } catch (err) {
      return rejectWithValue(
        extractErrorMessage(err, 'Erreur lors de la mise à jour du type de logement')
      );
    }
  }
);

export const updateStayConstraints = createAsyncThunk(
  'accommodation/updateStayConstraints',
  async ({ id, minNights, maxNights }: UpdateStayConstraintsPayload, { rejectWithValue }) => {
    try {
      await api.patch(
        `/api/accommodations/${id}/stay-constraints`,
        { minNights, maxNights },
        { headers: { 'Content-Type': 'application/merge-patch+json' } }
      );
      return { minNights, maxNights };
    } catch (err) {
      return rejectWithValue(
        extractErrorMessage(err, 'Erreur lors de la mise à jour des contraintes de séjour')
      );
    }
  }
);

export const updateHouseRules = createAsyncThunk(
  'accommodation/updateHouseRules',
  async (
    { id, smokingAllowed, petsAllowed, partiesAllowed, houseRulesNotes }: UpdateHouseRulesPayload,
    { rejectWithValue }
  ) => {
    try {
      await api.patch(
        `/api/accommodations/${id}/house-rules`,
        { smokingAllowed, petsAllowed, partiesAllowed, houseRulesNotes },
        { headers: { 'Content-Type': 'application/merge-patch+json' } }
      );
      return { smokingAllowed, petsAllowed, partiesAllowed, houseRulesNotes };
    } catch (err) {
      return rejectWithValue(
        extractErrorMessage(err, 'Erreur lors de la mise à jour du règlement intérieur')
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
    } catch (err) {
      return rejectWithValue(
        extractErrorMessage(err, 'Erreur lors de la mise à jour des horaires')
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
    } catch (err) {
      return rejectWithValue(
        extractErrorMessage(err, 'Erreur lors de la mise à jour de la description')
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
    } catch (err) {
      return rejectWithValue(
        extractErrorMessage(err, 'Erreur lors du chargement')
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
    } catch (err) {
      return rejectWithValue(typeof err === 'string' ? err : extractErrorMessage(err, "Erreur lors de l'upload"));
    }
  }
);

/** Update thunks dispatched by the edit page auto-save, with their badge section. */
const EDIT_SECTION_BY_THUNK_PREFIX: Record<string, EditSection> = {
  [updateDescription.typePrefix]: 'description',
  [updatePrice.typePrefix]: 'price',
  [updateWeeklyPromotion.typePrefix]: 'price',
  [updateDynamicPricing.typePrefix]: 'price',
  [updatePricePeriods.typePrefix]: 'price',
  [updateCancellationPolicy.typePrefix]: 'cancellation',
  [updateInstantBooking.typePrefix]: 'cancellation',
  [updateStayConstraints.typePrefix]: 'cancellation',
  [updateType.typePrefix]: 'description',
  [setCapacity.typePrefix]: 'capacity',
  [setAmenities.typePrefix]: 'amenities',
  [setCheckInOut.typePrefix]: 'checkinout',
  [updateHouseRules.typePrefix]: 'checkinout',
  [setLocation.typePrefix]: 'location',
};

const EDIT_THUNKS = [
  updateDescription,
  updatePrice,
  updateWeeklyPromotion,
  updateDynamicPricing,
  updatePricePeriods,
  updateCancellationPolicy,
  updateInstantBooking,
  updateType,
  updateStayConstraints,
  setCapacity,
  setAmenities,
  setCheckInOut,
  updateHouseRules,
  setLocation,
];

const editSectionForThunkAction = (actionType: string): EditSection | undefined =>
  EDIT_SECTION_BY_THUNK_PREFIX[actionType.replace(/\/(pending|fulfilled|rejected)$/, '')];

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
    editSaveStatusCleared(state, action: PayloadAction<{ section: EditSection }>) {
      state.editSaveStatus[action.payload.section] = 'idle';
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
      // Update dynamic pricing (weekend surcharge + last-minute)
      .addCase(updateDynamicPricing.pending, (state) => {
        state.status = 'loading';
        state.error = null;
      })
      .addCase(updateDynamicPricing.fulfilled, (state, action) => {
        state.status = 'succeeded';
        if (state.current) {
          state.current.weekendSurchargePercentage = action.payload.weekendSurchargePercentage;
          state.current.lastMinuteDiscountPercentage = action.payload.lastMinuteDiscountPercentage;
          state.current.lastMinuteDays = action.payload.lastMinuteDays;
        }
      })
      .addCase(updateDynamicPricing.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload as string;
      })
      // Update price periods (seasonal / per-date)
      .addCase(updatePricePeriods.pending, (state) => {
        state.status = 'loading';
        state.error = null;
      })
      .addCase(updatePricePeriods.fulfilled, (state, action) => {
        state.status = 'succeeded';
        if (state.current) {
          state.current.pricePeriods = action.payload.pricePeriods;
        }
      })
      .addCase(updatePricePeriods.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload as string;
      })
      // Update cancellation policy
      .addCase(updateCancellationPolicy.pending, (state) => {
        state.status = 'loading';
        state.error = null;
      })
      .addCase(updateCancellationPolicy.fulfilled, (state, action) => {
        state.status = 'succeeded';
        if (state.current) {
          state.current.cancellationPolicy = action.payload.cancellationPolicy;
        }
      })
      .addCase(updateCancellationPolicy.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload as string;
      })
      // Update instant booking
      .addCase(updateInstantBooking.pending, (state) => {
        state.status = 'loading';
        state.error = null;
      })
      .addCase(updateInstantBooking.fulfilled, (state, action) => {
        state.status = 'succeeded';
        if (state.current) {
          state.current.instantBooking = action.payload.instantBooking;
        }
      })
      .addCase(updateInstantBooking.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload as string;
      })
      // Update type
      .addCase(updateType.fulfilled, (state, action) => {
        state.status = 'succeeded';
        if (state.current) {
          state.current.type = action.payload.type;
        }
      })
      .addCase(updateType.rejected, (state, action) => {
        state.status = 'failed';
        state.error = action.payload as string;
      })
      // Update stay constraints
      .addCase(updateStayConstraints.fulfilled, (state, action) => {
        state.status = 'succeeded';
        if (state.current) {
          state.current.minNights = action.payload.minNights;
          state.current.maxNights = action.payload.maxNights;
        }
      })
      .addCase(updateStayConstraints.rejected, (state, action) => {
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
      // House rules
      .addCase(updateHouseRules.fulfilled, (state, action) => {
        state.status = 'succeeded';
        if (state.current) {
          state.current.smokingAllowed = action.payload.smokingAllowed;
          state.current.petsAllowed = action.payload.petsAllowed;
          state.current.partiesAllowed = action.payload.partiesAllowed;
          state.current.houseRulesNotes = action.payload.houseRulesNotes;
        }
      })
      .addCase(updateHouseRules.rejected, (state, action) => {
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
      })
      // Edit page auto-save badges: every update thunk reports its lifecycle
      // on the section it belongs to.
      .addCase(editPageOpened, (state) => {
        state.editSaveStatus = {};
      })
      .addMatcher(isAnyOf(...EDIT_THUNKS.map((t) => t.pending)), (state, action) => {
        const section = editSectionForThunkAction(action.type);
        if (section) state.editSaveStatus[section] = 'saving';
      })
      .addMatcher(isAnyOf(...EDIT_THUNKS.map((t) => t.fulfilled)), (state, action) => {
        const section = editSectionForThunkAction(action.type);
        if (section) state.editSaveStatus[section] = 'saved';
      })
      .addMatcher(isAnyOf(...EDIT_THUNKS.map((t) => t.rejected)), (state, action) => {
        const section = editSectionForThunkAction(action.type);
        if (section) state.editSaveStatus[section] = 'error';
      });
  },
});

export const {
  goToStep,
  saveDraft,
  resetWizard,
  wizardStepLeft,
  addressSubmitted,
  editSaveStatusCleared,
} = accommodationSlice.actions;
export default accommodationSlice.reducer;
