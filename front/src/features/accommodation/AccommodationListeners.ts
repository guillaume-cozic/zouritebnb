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
  updateCancellationPolicy,
  updateDescription,
  updateDynamicPricing,
  updateHouseRules,
  updateInstantBooking,
  updatePricePeriods,
  updateExtraServices,
  updateStayConstraints,
  updateType,
  updatePrice,
  updateWeeklyPromotion,
} from './AccommodationSlice';
import type { ExtraService, PricePeriod } from './AccommodationTypes';

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

/** Both null is valid; each set bound must be a positive integer and min ≤ max. */
const isValidStayConstraints = (minNights: number | null, maxNights: number | null): boolean => {
  const valid = (n: number | null) => n === null || (Number.isInteger(n) && n >= 1);
  if (!valid(minNights) || !valid(maxNights)) return false;
  if (minNights !== null && maxNights !== null && minNights > maxNights) return false;
  return true;
};

const isValidPercentage = (value: number | null, max: number): boolean =>
  value === null || (Number.isFinite(value) && value > 0 && value <= max);

/** Last-minute discount and its day window go together; each set value must be in range. */
const isValidLastMinute = (discount: number | null, days: number | null): boolean => {
  if ((discount === null) !== (days === null)) return false;
  if (discount === null) return true;
  return isValidPercentage(discount, 100) && Number.isInteger(days) && (days ?? 0) >= 1;
};

/** Every period must have a valid inclusive range and a strictly positive nightly price. */
const areValidPricePeriods = (periods: PricePeriod[]): boolean =>
  periods.every(
    (p) =>
      /^\d{4}-\d{2}-\d{2}$/.test(p.startDate) &&
      /^\d{4}-\d{2}-\d{2}$/.test(p.endDate) &&
      p.startDate <= p.endDate &&
      Number.isFinite(p.pricePerNight) &&
      p.pricePerNight > 0
  );

/** Every service must have a non-empty name (max 100 chars) and a strictly positive price. */
const areValidExtraServices = (services: ExtraService[]): boolean =>
  services.every(
    (s) =>
      s.name.trim() !== '' &&
      s.name.trim().length <= 100 &&
      Number.isFinite(s.price) &&
      s.price > 0
  );

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
    case 'dynamicPricing':
      return isValidPercentage(p.weekendSurchargePercentage, 500) && isValidLastMinute(p.lastMinuteDiscountPercentage, p.lastMinuteDays);
    case 'pricePeriods':
      return areValidPricePeriods(p.pricePeriods);
    case 'extraServices':
      return areValidExtraServices(p.extraServices);
    case 'cancellationPolicy':
      return p.cancellationPolicy === 'flexible' || p.cancellationPolicy === 'moderate';
    case 'instantBooking':
      return typeof p.instantBooking === 'boolean';
    case 'type':
      return true;
    case 'stayConstraints':
      return isValidStayConstraints(p.minNights, p.maxNights);
    case 'houseRules':
      return p.houseRulesNotes === null || p.houseRulesNotes.length <= 1000;
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
    case 'dynamicPricing':
      return dispatch(updateDynamicPricing({
        id: p.id,
        weekendSurchargePercentage: p.weekendSurchargePercentage,
        lastMinuteDiscountPercentage: p.lastMinuteDiscountPercentage,
        lastMinuteDays: p.lastMinuteDays,
      }));
    case 'pricePeriods':
      return dispatch(updatePricePeriods({ id: p.id, pricePeriods: p.pricePeriods }));
    case 'extraServices':
      return dispatch(updateExtraServices({ id: p.id, extraServices: p.extraServices }));
    case 'cancellationPolicy':
      return dispatch(updateCancellationPolicy({ id: p.id, cancellationPolicy: p.cancellationPolicy }));
    case 'instantBooking':
      return dispatch(updateInstantBooking({ id: p.id, instantBooking: p.instantBooking }));
    case 'type':
      return dispatch(updateType({ id: p.id, type: p.type }));
    case 'stayConstraints':
      return dispatch(updateStayConstraints({ id: p.id, minNights: p.minNights, maxNights: p.maxNights }));
    case 'houseRules':
      return dispatch(updateHouseRules({
        id: p.id,
        smokingAllowed: p.smokingAllowed,
        petsAllowed: p.petsAllowed,
        partiesAllowed: p.partiesAllowed,
        houseRulesNotes: p.houseRulesNotes,
      }));
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
  'dynamicPricing',
  'pricePeriods',
  'extraServices',
  'cancellationPolicy',
  'instantBooking',
  'type',
  'stayConstraints',
  'houseRules',
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
