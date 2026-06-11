import { startAppListening } from '../../store/listenerMiddleware';
import type { AppDispatch } from '../../store';
import {
  accommodationFieldEdited,
  AccommodationFieldEditedPayload,
  AccommodationEditField,
  addressSubmitted,
  editPageOpened,
  editSaveStatusCleared,
  editSectionForField,
  fetchAccommodation,
  setAmenities,
  setCapacity,
  setCheckInOut,
  setLocation,
  updateDescription,
  updatePrice,
  updateWeeklyPromotion,
} from './AccommodationSlice';

const AUTOSAVE_DELAY = 1200;
const SAVED_BADGE_DELAY = 2500;

startAppListening({
  actionCreator: addressSubmitted,
  effect: (action, api) => {
    const { id, address } = action.payload;
    api.dispatch(setLocation({ id, ...address }));
  },
});

startAppListening({
  actionCreator: editPageOpened,
  effect: (action, api) => {
    api.dispatch(fetchAccommodation(action.payload.id));
  },
});

/** A payload is saved only when it satisfies the same business rules the forms enforce. */
const isSavable = (p: AccommodationFieldEditedPayload): boolean => {
  switch (p.field) {
    case 'description':
      return p.title.trim() !== '' && p.description.trim() !== '';
    case 'price':
      return Number.isFinite(p.price) && p.price > 0;
    case 'weeklyPromotion':
      return (
        p.weeklyPromotionPercentage === null ||
        (Number.isFinite(p.weeklyPromotionPercentage) &&
          p.weeklyPromotionPercentage > 0 &&
          p.weeklyPromotionPercentage <= 100)
      );
    case 'location':
      return (
        p.street.trim() !== '' &&
        p.city.trim() !== '' &&
        p.country.trim() !== '' &&
        (p.latitude === undefined || (p.latitude >= -90 && p.latitude <= 90)) &&
        (p.longitude === undefined || (p.longitude >= -180 && p.longitude <= 180))
      );
    default:
      return true;
  }
};

const dispatchSave = (p: AccommodationFieldEditedPayload, dispatch: AppDispatch) => {
  switch (p.field) {
    case 'description':
      return dispatch(updateDescription({ id: p.id, title: p.title, description: p.description }));
    case 'price':
      return dispatch(updatePrice({ id: p.id, price: p.price }));
    case 'weeklyPromotion':
      return dispatch(updateWeeklyPromotion({ id: p.id, weeklyPromotionPercentage: p.weeklyPromotionPercentage }));
    case 'capacity':
      return dispatch(setCapacity({
        id: p.id,
        bedrooms: p.bedrooms,
        bathrooms: p.bathrooms,
        maxGuests: p.maxGuests,
        singleBeds: p.singleBeds,
        doubleBeds: p.doubleBeds,
      }));
    case 'amenities':
      return dispatch(setAmenities({ id: p.id, codes: p.codes }));
    case 'checkInOut':
      return dispatch(setCheckInOut({ id: p.id, checkIn: p.checkIn, checkOut: p.checkOut }));
    case 'location':
      return dispatch(setLocation({
        id: p.id,
        street: p.street,
        city: p.city,
        zipCode: p.zipCode,
        country: p.country,
        latitude: p.latitude,
        longitude: p.longitude,
      }));
  }
};

// One listener per field so that `cancelActiveListeners` debounces a field
// without cancelling the pending save of another one.
const EDIT_FIELDS: AccommodationEditField[] = [
  'description',
  'price',
  'weeklyPromotion',
  'capacity',
  'amenities',
  'checkInOut',
  'location',
];

EDIT_FIELDS.forEach((field) => {
  startAppListening({
    actionCreator: accommodationFieldEdited,
    effect: async (action, api) => {
      const payload = action.payload;
      if (payload.field !== field) return;
      // Debounce: only the latest edit of this field survives. Instances of
      // this entry that passed the guard above are necessarily the same field.
      api.cancelActiveListeners();
      await api.delay(AUTOSAVE_DELAY);
      if (!isSavable(payload)) return;
      const result = await dispatchSave(payload, api.dispatch);
      if (result.meta.requestStatus === 'fulfilled') {
        await api.delay(SAVED_BADGE_DELAY);
        api.dispatch(editSaveStatusCleared({ section: editSectionForField(field) }));
      }
    },
  });
});
