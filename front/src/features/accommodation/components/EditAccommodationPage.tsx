import React, { useEffect, useState, useRef } from 'react';
import { useParams, Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { TFunction } from 'i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import {
  accommodationFieldEdited,
  editPageOpened,
} from '../AccommodationSlice';
import {
  selectCurrentAccommodation,
  selectAccommodationStatus,
  selectAccommodationError,
  selectEditSaveStatus,
} from '../AccommodationSelectors';
import { Accommodation, CancellationPolicy, AccommodationType, ACCOMMODATION_TYPES, PricePeriod } from '../AccommodationTypes';
import { AMENITY_CATEGORIES } from '../AmenityData';
import MapSelector from '../../../components/MapSelector';
import { Button, Card, Field, Input, SaveIndicator, Select, Textarea } from '../../../components/ui';
import EditLayout, { SECTIONS, EditSection } from './EditLayout';

const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:8080';

const SECTION_CARD_CLASS = 'sm:p-8 hover:shadow-md transition-shadow';

// --- Sub-form schemas ---
const getLocationSchema = (t: TFunction) => z.object({
  street: z.string().min(1, t('addressStep.streetRequired')),
  city: z.string().min(1, t('addressStep.cityRequired')),
  zipCode: z.string().optional().default(''),
  country: z.string().min(1, t('addressStep.countryRequired')),
  latitude: z.preprocess((v) => (v === '' || v === undefined || v === null ? undefined : Number(v)), z.number().min(-90).max(90).optional()),
  longitude: z.preprocess((v) => (v === '' || v === undefined || v === null ? undefined : Number(v)), z.number().min(-180).max(180).optional()),
});

/**
 * Inner form, mounted once the accommodation is loaded (keyed by its id), so
 * the local input states hydrate from props in their initializers. Each change
 * handler updates its controlled input and dispatches a single
 * `accommodationFieldEdited` intent — the listener debounces and saves.
 */
const EditAccommodationForm: React.FC<{ accommodation: Accommodation }> = ({ accommodation }) => {
  const { t } = useTranslation();
  const dispatch = useAppDispatch();
  const error = useAppSelector(selectAccommodationError);
  const sectionStatus = useAppSelector(selectEditSaveStatus);
  const id = accommodation.id!;

  const [activeSection, setActiveSection] = useState<EditSection>('description');
  const sectionRefs = useRef<Record<string, HTMLElement | null>>({});

  // Controlled inputs, hydrated once from the loaded accommodation.
  const [title, setTitle] = useState(accommodation.title ?? '');
  const [description, setDescription] = useState(accommodation.description ?? '');
  const [price, setPrice] = useState<number>(accommodation.price ?? 0);
  const [weeklyPromotion, setWeeklyPromotion] = useState<string>(
    accommodation.weeklyPromotionPercentage != null
      ? String(accommodation.weeklyPromotionPercentage)
      : ''
  );
  const numToStr = (n: number | null | undefined): string => (n != null ? String(n) : '');
  const [weekendSurcharge, setWeekendSurcharge] = useState<string>(numToStr(accommodation.weekendSurchargePercentage));
  const [lastMinuteDiscount, setLastMinuteDiscount] = useState<string>(numToStr(accommodation.lastMinuteDiscountPercentage));
  const [lastMinuteDays, setLastMinuteDays] = useState<string>(numToStr(accommodation.lastMinuteDays));
  const [pricePeriods, setPricePeriods] = useState<PricePeriod[]>(accommodation.pricePeriods ?? []);
  const [capacityValues, setCapacityValues] = useState({
    bedrooms: accommodation.bedrooms ?? 0,
    bathrooms: accommodation.bathrooms ?? 0,
    maxGuests: accommodation.maxGuests ?? 0,
    singleBeds: accommodation.singleBeds ?? 0,
    doubleBeds: accommodation.doubleBeds ?? 0,
  });
  const [selectedAmenities, setSelectedAmenities] = useState<Set<string>>(
    () => new Set(accommodation.amenities ?? [])
  );
  const [checkInOut, setCheckInOutState] = useState({
    checkIn: accommodation.checkIn ?? '16:00',
    checkOut: accommodation.checkOut ?? '12:00',
  });
  const [cancellationPolicy, setCancellationPolicy] = useState<CancellationPolicy>(
    accommodation.cancellationPolicy ?? 'flexible'
  );
  const [instantBooking, setInstantBooking] = useState<boolean>(
    accommodation.instantBooking ?? false
  );
  const [type, setType] = useState<AccommodationType | ''>(accommodation.type ?? '');
  const [minNights, setMinNights] = useState<string>(
    accommodation.minNights != null ? String(accommodation.minNights) : ''
  );
  const [maxNights, setMaxNights] = useState<string>(
    accommodation.maxNights != null ? String(accommodation.maxNights) : ''
  );

  const locationForm = useForm({
    resolver: zodResolver(getLocationSchema(t)),
    mode: 'onChange',
    defaultValues: {
      street: accommodation.street ?? '',
      city: accommodation.city ?? '',
      zipCode: accommodation.zipCode ?? '',
      country: accommodation.country ?? '',
      latitude: accommodation.latitude,
      longitude: accommodation.longitude,
    },
  });

  const scrollTo = (key: EditSection) => {
    setActiveSection(key);
    sectionRefs.current[key]?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  // --- Change handlers: update the controlled input, declare the intent ---
  const handleTitleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const v = e.target.value;
    setTitle(v);
    dispatch(accommodationFieldEdited({ field: 'description', id, title: v, description }));
  };

  const handleDescriptionChange = (e: React.ChangeEvent<HTMLTextAreaElement>) => {
    const v = e.target.value;
    setDescription(v);
    dispatch(accommodationFieldEdited({ field: 'description', id, title, description: v }));
  };

  const handlePriceChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const v = Number(e.target.value);
    setPrice(v);
    dispatch(accommodationFieldEdited({ field: 'price', id, price: v }));
  };

  const handleWeeklyPromotionChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const v = e.target.value;
    setWeeklyPromotion(v);
    dispatch(accommodationFieldEdited({
      field: 'weeklyPromotion',
      id,
      weeklyPromotionPercentage: v === '' ? null : Number(v),
    }));
  };

  const dispatchDynamicPricing = (weekend: string, discount: string, days: string) => {
    dispatch(accommodationFieldEdited({
      field: 'dynamicPricing',
      id,
      weekendSurchargePercentage: weekend === '' ? null : Number(weekend),
      lastMinuteDiscountPercentage: discount === '' ? null : Number(discount),
      lastMinuteDays: days === '' ? null : Number(days),
    }));
  };

  const handleWeekendSurchargeChange = (v: string) => {
    setWeekendSurcharge(v);
    dispatchDynamicPricing(v, lastMinuteDiscount, lastMinuteDays);
  };
  const handleLastMinuteDiscountChange = (v: string) => {
    setLastMinuteDiscount(v);
    dispatchDynamicPricing(weekendSurcharge, v, lastMinuteDays);
  };
  const handleLastMinuteDaysChange = (v: string) => {
    setLastMinuteDays(v);
    dispatchDynamicPricing(weekendSurcharge, lastMinuteDiscount, v);
  };

  const commitPricePeriods = (next: PricePeriod[]) => {
    setPricePeriods(next);
    dispatch(accommodationFieldEdited({ field: 'pricePeriods', id, pricePeriods: next }));
  };
  const handleAddPricePeriod = () => {
    setPricePeriods((prev) => [...prev, { startDate: '', endDate: '', pricePerNight: 0 }]);
  };
  const handleRemovePricePeriod = (index: number) => {
    commitPricePeriods(pricePeriods.filter((_, i) => i !== index));
  };
  const handlePricePeriodChange = (index: number, field: keyof PricePeriod, value: string) => {
    const next = pricePeriods.map((p, i) =>
      i === index ? { ...p, [field]: field === 'pricePerNight' ? Number(value) : value } : p
    );
    commitPricePeriods(next);
  };

  const handleCapacityChange = (field: string, value: number) => {
    setCapacityValues((prev) => {
      const next = { ...prev, [field]: value };
      dispatch(accommodationFieldEdited({ field: 'capacity', id, ...next }));
      return next;
    });
  };

  const toggleAmenity = (code: string) => {
    setSelectedAmenities((prev) => {
      const next = new Set(prev);
      next.has(code) ? next.delete(code) : next.add(code);
      dispatch(accommodationFieldEdited({ field: 'amenities', id, codes: Array.from(next) }));
      return next;
    });
  };

  const handleCheckInOutChange = (field: 'checkIn' | 'checkOut', value: string) => {
    setCheckInOutState((prev) => {
      const next = { ...prev, [field]: value };
      dispatch(accommodationFieldEdited({ field: 'checkInOut', id, ...next }));
      return next;
    });
  };

  const handleCancellationPolicyChange = (policy: CancellationPolicy) => {
    setCancellationPolicy(policy);
    dispatch(accommodationFieldEdited({ field: 'cancellationPolicy', id, cancellationPolicy: policy }));
  };

  const handleInstantBookingChange = (value: boolean) => {
    setInstantBooking(value);
    dispatch(accommodationFieldEdited({ field: 'instantBooking', id, instantBooking: value }));
  };

  const handleTypeChange = (value: string) => {
    const next = (value || null) as AccommodationType | null;
    setType(next ?? '');
    dispatch(accommodationFieldEdited({ field: 'type', id, type: next }));
  };

  const handleStayConstraintsChange = (nextMin: string, nextMax: string) => {
    setMinNights(nextMin);
    setMaxNights(nextMax);
    dispatch(accommodationFieldEdited({
      field: 'stayConstraints',
      id,
      minNights: nextMin === '' ? null : Number(nextMin),
      maxNights: nextMax === '' ? null : Number(nextMax),
    }));
  };

  const dispatchLocationEdited = () => {
    const v = locationForm.getValues();
    const num = (x: unknown): number | undefined =>
      x === '' || x === undefined || x === null ? undefined : Number(x);
    dispatch(accommodationFieldEdited({
      field: 'location',
      id,
      street: v.street ?? '',
      city: v.city ?? '',
      zipCode: v.zipCode ?? '',
      country: v.country ?? '',
      latitude: num(v.latitude),
      longitude: num(v.longitude),
    }));
  };

  const handleMapSelect = (lat: number, lng: number) => {
    locationForm.setValue('latitude', lat, { shouldValidate: true });
    locationForm.setValue('longitude', lng, { shouldValidate: true });
    dispatchLocationEdited();
  };

  return (
    <EditLayout
      accommodationId={id}
      accommodationTitle={accommodation.title ?? ''}
      activeSection={activeSection}
      error={error}
      onScrollTo={scrollTo}
      headerRight={
        <span className="hidden sm:inline-flex items-center gap-1.5 text-xs text-surface-400">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6" /></svg>
          {t('edit.autoSaveHint')}
        </span>
      }
    >
      <div className="space-y-6">
        {/* Description */}
        <div ref={(el) => { sectionRefs.current.description = el; }}>
          <Card
            title={t('edit.section.description')}
            icon={SECTIONS[0].icon}
            action={<SaveIndicator status={sectionStatus.description ?? 'idle'} />}
            className={SECTION_CARD_CLASS}
          >
            <div className="space-y-4">
              {accommodation.photos && accommodation.photos.length > 0 && (
                <div className="w-24 h-24 rounded-lg overflow-hidden bg-surface-100 flex-shrink-0">
                  <img
                    src={`${API_BASE}${accommodation.photos[0].url}`}
                    alt={accommodation.title}
                    className="w-full h-full object-cover"
                  />
                </div>
              )}
              <Field label={t('descriptionStep.titleLabel')}>
                <Input type="text" value={title} onChange={handleTitleChange} />
              </Field>
              <Field label={t('descriptionStep.descriptionLabel')}>
                <Textarea value={description} onChange={handleDescriptionChange} rows={5} />
              </Field>
              <Field label={t('accommodationType.label')}>
                <Select value={type} onChange={(e) => handleTypeChange(e.target.value)}>
                  <option value="">{t('accommodationType.unspecified')}</option>
                  {ACCOMMODATION_TYPES.map((code) => (
                    <option key={code} value={code}>
                      {t(`accommodationType.${code}`)}
                    </option>
                  ))}
                </Select>
              </Field>
            </div>
          </Card>
        </div>

        {/* Price */}
        <div ref={(el) => { sectionRefs.current.price = el; }}>
          <Card
            title={t('edit.section.price')}
            icon={SECTIONS[1].icon}
            iconClassName="bg-success-50 text-success-600"
            action={<SaveIndicator status={sectionStatus.price ?? 'idle'} />}
            className={SECTION_CARD_CLASS}
          >
            <div className="space-y-5">
              <Field label={t('descriptionStep.priceLabel')}>
                <div className="relative max-w-xs">
                  <Input
                    type="number"
                    step="0.01"
                    value={price}
                    onChange={handlePriceChange}
                    className="pr-20"
                  />
                  <div className="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                    <span className="text-surface-400 font-medium text-sm">{t('descriptionStep.priceUnit')}</span>
                  </div>
                </div>
              </Field>
              <div className="pt-4 border-t border-surface-100">
                <Field label={t('descriptionStep.weeklyPromotionLabel')} hint={t('descriptionStep.weeklyPromotionHint')}>
                  <div className="relative max-w-xs">
                    <Input
                      type="number"
                      step="1"
                      min={0}
                      max={100}
                      placeholder="0"
                      value={weeklyPromotion}
                      onChange={handleWeeklyPromotionChange}
                      className="pr-12"
                    />
                    <div className="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                      <span className="text-surface-400 font-medium text-sm">{t('descriptionStep.weeklyPromotionUnit')}</span>
                    </div>
                  </div>
                </Field>
                {(() => {
                  const promo = weeklyPromotion === '' ? null : Number(weeklyPromotion);
                  if (promo === null || !Number.isFinite(promo) || promo <= 0 || promo > 100 || price <= 0) return null;
                  const discounted = price * (1 - promo / 100);
                  const weekTotal = discounted * 7;
                  const fmt = (n: number) => n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                  return (
                    <div className="mt-3 inline-flex flex-wrap items-center gap-x-4 gap-y-1 rounded-xl border border-success-100 bg-success-50 px-4 py-2.5 text-sm">
                      <div className="flex items-center gap-2">
                        <span className="text-surface-500">{t('descriptionStep.discountedNightlyLabel')}</span>
                        <span className="text-surface-400 line-through">{fmt(price)} €</span>
                        <span className="font-semibold text-success-700">{fmt(discounted)} €</span>
                      </div>
                      <div className="flex items-center gap-2">
                        <span className="text-surface-500">{t('descriptionStep.weeklyTotalLabel')}</span>
                        <span className="font-semibold text-success-700">{fmt(weekTotal)} €</span>
                      </div>
                    </div>
                  );
                })()}
              </div>

              {/* Dynamic pricing: weekend surcharge + last-minute discount */}
              <div className="pt-4 border-t border-surface-100 space-y-4">
                <h3 className="text-sm font-semibold text-surface-700">{t('dynamicPricing.title')}</h3>
                <Field label={t('dynamicPricing.weekendLabel')} hint={t('dynamicPricing.weekendHint')}>
                  <div className="relative max-w-xs">
                    <Input
                      type="number"
                      step="1"
                      min={0}
                      max={500}
                      placeholder="0"
                      value={weekendSurcharge}
                      onChange={(e) => handleWeekendSurchargeChange(e.target.value)}
                      className="pr-12"
                    />
                    <div className="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                      <span className="text-surface-400 font-medium text-sm">%</span>
                    </div>
                  </div>
                </Field>
                <div className="grid grid-cols-2 gap-4 max-w-md">
                  <Field label={t('dynamicPricing.lastMinuteDiscountLabel')}>
                    <div className="relative">
                      <Input
                        type="number"
                        step="1"
                        min={0}
                        max={100}
                        placeholder="0"
                        value={lastMinuteDiscount}
                        onChange={(e) => handleLastMinuteDiscountChange(e.target.value)}
                        className="pr-12"
                      />
                      <div className="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                        <span className="text-surface-400 font-medium text-sm">%</span>
                      </div>
                    </div>
                  </Field>
                  <Field label={t('dynamicPricing.lastMinuteDaysLabel')}>
                    <Input
                      type="number"
                      step="1"
                      min={1}
                      placeholder="0"
                      value={lastMinuteDays}
                      onChange={(e) => handleLastMinuteDaysChange(e.target.value)}
                    />
                  </Field>
                </div>
                <p className="text-xs text-surface-400">{t('dynamicPricing.lastMinuteHint')}</p>
              </div>

              {/* Seasonal / per-date price periods */}
              <div className="pt-4 border-t border-surface-100 space-y-3">
                <div className="flex items-center justify-between">
                  <h3 className="text-sm font-semibold text-surface-700">{t('dynamicPricing.periodsTitle')}</h3>
                  <Button type="button" variant="secondary" size="sm" onClick={handleAddPricePeriod}>
                    {t('dynamicPricing.addPeriod')}
                  </Button>
                </div>
                {pricePeriods.length === 0 ? (
                  <p className="text-xs text-surface-400">{t('dynamicPricing.noPeriods')}</p>
                ) : (
                  <div className="space-y-2">
                    {pricePeriods.map((period, index) => (
                      <div key={index} className="flex flex-wrap items-end gap-2">
                        <Field label={t('dynamicPricing.from')}>
                          <Input type="date" value={period.startDate} onChange={(e) => handlePricePeriodChange(index, 'startDate', e.target.value)} />
                        </Field>
                        <Field label={t('dynamicPricing.to')}>
                          <Input type="date" value={period.endDate} onChange={(e) => handlePricePeriodChange(index, 'endDate', e.target.value)} />
                        </Field>
                        <Field label={t('dynamicPricing.nightlyPrice')}>
                          <Input
                            type="number"
                            step="0.01"
                            min={0}
                            value={period.pricePerNight || ''}
                            onChange={(e) => handlePricePeriodChange(index, 'pricePerNight', e.target.value)}
                          />
                        </Field>
                        <Button type="button" variant="ghost" size="sm" onClick={() => handleRemovePricePeriod(index)}>
                          {t('dynamicPricing.remove')}
                        </Button>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            </div>
          </Card>
        </div>

        {/* Capacity */}
        <div ref={(el) => { sectionRefs.current.capacity = el; }}>
          <Card
            title={t('edit.section.capacity')}
            icon={SECTIONS[2].icon}
            iconClassName="bg-violet-50 text-violet-600"
            action={<SaveIndicator status={sectionStatus.capacity ?? 'idle'} />}
            className={SECTION_CARD_CLASS}
          >
            <div className="grid grid-cols-2 sm:grid-cols-3 gap-4">
              {([
                { name: 'bedrooms', label: t('capacityStep.bedrooms') },
                { name: 'bathrooms', label: t('capacityStep.bathrooms') },
                { name: 'maxGuests', label: t('capacityStep.maxGuests') },
                { name: 'singleBeds', label: t('capacityStep.singleBeds') },
                { name: 'doubleBeds', label: t('capacityStep.doubleBeds') },
              ] as const).map((f) => (
                <Field key={f.name} label={f.label}>
                  <Input
                    type="number"
                    min={0}
                    value={capacityValues[f.name]}
                    onChange={(e) => handleCapacityChange(f.name, Number(e.target.value))}
                  />
                </Field>
              ))}
            </div>
          </Card>
        </div>

        {/* Amenities */}
        <div ref={(el) => { sectionRefs.current.amenities = el; }}>
          <Card
            title={t('edit.section.amenities')}
            icon={SECTIONS[3].icon}
            iconClassName="bg-warning-50 text-warning-600"
            action={
              <div className="flex items-center gap-2">
                <SaveIndicator status={sectionStatus.amenities ?? 'idle'} />
                <span className="text-sm text-surface-500 bg-surface-100 rounded-full px-3 py-1 font-medium">{selectedAmenities.size} {t('edit.amenitiesSelected')}</span>
              </div>
            }
            className={SECTION_CARD_CLASS}
          >
            <div className="space-y-6">
              {AMENITY_CATEGORIES.map((cat) => (
                <div key={cat.key}>
                  <h3 className="text-sm font-semibold text-surface-700 mb-2.5">{t(`amenityCategories.${cat.key}`)}</h3>
                  <div className="flex flex-wrap gap-2">
                    {cat.items.map((item) => {
                      const selected = selectedAmenities.has(item.code);
                      return (
                        <button
                          key={item.code}
                          type="button"
                          onClick={() => toggleAmenity(item.code)}
                          className={`inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-sm font-medium border transition-all ${
                            selected
                              ? 'bg-primary-600 text-white border-transparent shadow-sm shadow-primary-500/25'
                              : 'bg-white text-surface-700 border-surface-200 hover:border-primary-300 hover:bg-primary-50'
                          }`}
                        >
                          {selected && (
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><path d="M20 6L9 17l-5-5" /></svg>
                          )}
                          {t(`amenities.${item.code}`)}
                        </button>
                      );
                    })}
                  </div>
                </div>
              ))}
            </div>
          </Card>
        </div>

        {/* Location */}
        <div ref={(el) => { sectionRefs.current.location = el; }}>
          <Card
            title={t('edit.section.location')}
            icon={SECTIONS[4].icon}
            iconClassName="bg-rose-50 text-rose-600"
            action={<SaveIndicator status={sectionStatus.location ?? 'idle'} />}
            className={SECTION_CARD_CLASS}
          >
            <div className="space-y-4">
              <Field
                label={t('addressStep.street')}
                error={locationForm.formState.errors.street && String(locationForm.formState.errors.street.message)}
              >
                <Input type="text" {...locationForm.register('street', { onChange: dispatchLocationEdited })} />
              </Field>
              <div className="grid grid-cols-2 gap-4">
                <Field
                  label={t('addressStep.city')}
                  error={locationForm.formState.errors.city && String(locationForm.formState.errors.city.message)}
                >
                  <Input type="text" {...locationForm.register('city', { onChange: dispatchLocationEdited })} />
                </Field>
                <Field label={t('addressStep.zipCode')}>
                  <Input type="text" {...locationForm.register('zipCode', { onChange: dispatchLocationEdited })} />
                </Field>
              </div>
              <Field
                label={t('addressStep.country')}
                error={locationForm.formState.errors.country && String(locationForm.formState.errors.country.message)}
              >
                <Input type="text" {...locationForm.register('country', { onChange: dispatchLocationEdited })} />
              </Field>
              <div className="rounded-xl overflow-hidden border border-surface-100">
                <label className="block text-sm font-medium text-surface-700 mb-1.5 px-1">{t('addressStep.mapTitle')}</label>
                <MapSelector
                  latitude={locationForm.watch('latitude')}
                  longitude={locationForm.watch('longitude')}
                  onSelect={handleMapSelect}
                />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <Field label={t('addressStep.latitude')}>
                  <Input type="number" step="any" {...locationForm.register('latitude', { onChange: dispatchLocationEdited })} />
                </Field>
                <Field label={t('addressStep.longitude')}>
                  <Input type="number" step="any" {...locationForm.register('longitude', { onChange: dispatchLocationEdited })} />
                </Field>
              </div>
            </div>
          </Card>
        </div>

        {/* Check-in / Check-out */}
        <div ref={(el) => { sectionRefs.current.checkinout = el; }}>
          <Card
            title={t('edit.section.checkinout')}
            icon={SECTIONS[5].icon}
            iconClassName="bg-sky-50 text-sky-600"
            action={<SaveIndicator status={sectionStatus.checkinout ?? 'idle'} />}
            className={SECTION_CARD_CLASS}
          >
            <div className="grid grid-cols-2 gap-4 max-w-md">
              <Field label={t('detail.checkIn')}>
                <Input
                  type="time"
                  value={checkInOut.checkIn}
                  onChange={(e) => handleCheckInOutChange('checkIn', e.target.value)}
                />
              </Field>
              <Field label={t('detail.checkOut')}>
                <Input
                  type="time"
                  value={checkInOut.checkOut}
                  onChange={(e) => handleCheckInOutChange('checkOut', e.target.value)}
                />
              </Field>
            </div>
          </Card>
        </div>

        {/* Cancellation policy */}
        <div ref={(el) => { sectionRefs.current.cancellation = el; }}>
          <Card
            title={t('edit.section.cancellation')}
            icon={SECTIONS[6].icon}
            iconClassName="bg-amber-50 text-amber-600"
            action={<SaveIndicator status={sectionStatus.cancellation ?? 'idle'} />}
            className={SECTION_CARD_CLASS}
          >
            <p className="text-sm text-surface-500 mb-4">{t('cancellationStep.intro')}</p>
            <div className="grid gap-3 sm:grid-cols-2">
              {(['flexible', 'moderate'] as const).map((policy) => {
                const selected = cancellationPolicy === policy;
                return (
                  <button
                    key={policy}
                    type="button"
                    onClick={() => handleCancellationPolicyChange(policy)}
                    className={`text-left p-4 rounded-xl border transition-all ${
                      selected
                        ? 'border-primary-500 bg-primary-50 ring-1 ring-primary-500'
                        : 'border-surface-200 bg-white hover:border-primary-300 hover:bg-primary-50/40'
                    }`}
                  >
                    <div className="flex items-center justify-between gap-2">
                      <span className="font-semibold text-surface-800">{t(`cancellationStep.${policy}.title`)}</span>
                      <span className={`flex items-center justify-center w-5 h-5 rounded-full border-2 flex-shrink-0 ${selected ? 'border-primary-600 bg-primary-600 text-white' : 'border-surface-300'}`}>
                        {selected && (
                          <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round"><path d="M20 6L9 17l-5-5" /></svg>
                        )}
                      </span>
                    </div>
                    <p className="text-sm text-surface-500 mt-1.5">{t(`cancellationStep.${policy}.description`)}</p>
                  </button>
                );
              })}
            </div>

            <div className="mt-5 pt-5 border-t border-surface-200 flex items-start justify-between gap-4">
              <div>
                <p className="font-semibold text-surface-800">{t('instantBooking.title')}</p>
                <p className="text-sm text-surface-500 mt-1">{t('instantBooking.description')}</p>
              </div>
              <button
                type="button"
                role="switch"
                aria-checked={instantBooking}
                aria-label={t('instantBooking.title')}
                onClick={() => handleInstantBookingChange(!instantBooking)}
                className={`relative inline-flex h-6 w-11 flex-shrink-0 items-center rounded-full transition-colors ${
                  instantBooking ? 'bg-primary-600' : 'bg-surface-300'
                }`}
              >
                <span
                  className={`inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform ${
                    instantBooking ? 'translate-x-5' : 'translate-x-0.5'
                  }`}
                />
              </button>
            </div>

            <div className="mt-5 pt-5 border-t border-surface-200">
              <p className="font-semibold text-surface-800">{t('stayConstraints.title')}</p>
              <p className="text-sm text-surface-500 mt-1 mb-4">{t('stayConstraints.description')}</p>
              <div className="grid gap-3 sm:grid-cols-2">
                <Field label={t('stayConstraints.minNights')}>
                  <Input
                    type="number"
                    min={1}
                    value={minNights}
                    onChange={(e) => handleStayConstraintsChange(e.target.value, maxNights)}
                    placeholder={t('stayConstraints.noLimit')}
                  />
                </Field>
                <Field label={t('stayConstraints.maxNights')}>
                  <Input
                    type="number"
                    min={1}
                    value={maxNights}
                    onChange={(e) => handleStayConstraintsChange(minNights, e.target.value)}
                    placeholder={t('stayConstraints.noLimit')}
                  />
                </Field>
              </div>
            </div>
          </Card>
        </div>

        {/* Photos — clickable card linking to dedicated page */}
        <Link
          to={`/accommodations/${id}/photos`}
          ref={(el) => { sectionRefs.current.photos = el; }}
          className="block bg-white rounded-2xl border border-surface-200 shadow-sm hover:shadow-md hover:border-primary-200 transition-all p-6 sm:p-8 group cursor-pointer"
        >
          <div className="flex items-center justify-between mb-5">
            <div className="flex items-center gap-3">
              <div className="flex items-center justify-center w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600">
                {SECTIONS[7].icon}
              </div>
              <h2 className="text-lg font-semibold">{t('edit.section.photos')}</h2>
            </div>
            <span className="inline-flex items-center gap-1.5 text-sm text-primary-600 font-medium group-hover:gap-2.5 transition-all">
              {t('edit.managePhotos')}
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M5 12h14" /><path d="m12 5 7 7-7 7" /></svg>
            </span>
          </div>

          {accommodation.photos && accommodation.photos.length > 0 ? (
            <div className="relative w-full max-w-sm aspect-[3/2] rounded-xl overflow-hidden bg-surface-100">
              <img
                src={`${API_BASE}${accommodation.photos[0].url}`}
                alt={accommodation.title}
                className="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
              />
            </div>
          ) : (
            <div className="flex items-center gap-3 p-4 rounded-xl bg-surface-50 border border-dashed border-surface-200">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" className="text-surface-300 flex-shrink-0"><rect width="18" height="18" x="3" y="3" rx="2" ry="2" /><circle cx="9" cy="9" r="2" /><path d="m21 15-3.086-3.086a2 2 0 00-2.828 0L6 21" /></svg>
              <p className="text-sm text-surface-400">{t('edit.noPhotos')}</p>
            </div>
          )}
        </Link>
      </div>
    </EditLayout>
  );
};

const EditAccommodationPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const { t } = useTranslation();
  const dispatch = useAppDispatch();
  const accommodation = useAppSelector(selectCurrentAccommodation);
  const status = useAppSelector(selectAccommodationStatus);
  const error = useAppSelector(selectAccommodationError);

  useEffect(() => {
    if (id) dispatch(editPageOpened({ id }));
  }, [dispatch, id]);

  if (!accommodation || accommodation.id !== id) {
    if (status === 'loading' || status === 'idle') {
      return (
        <main className="min-h-screen py-8">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="animate-pulse space-y-6">
              <div className="h-8 bg-surface-200 rounded-lg w-1/3" />
              <div className="h-64 bg-surface-100 rounded-lg" />
            </div>
          </div>
        </main>
      );
    }
    return (
      <main className="min-h-screen py-8">
        <div className="max-w-7xl mx-auto px-4 text-center py-20">
          <p className="text-danger-500 mb-4">{error || t('edit.notFound')}</p>
          <Link to="/" className="text-primary-600 hover:underline">{t('detail.backToHome')}</Link>
        </div>
      </main>
    );
  }

  return <EditAccommodationForm key={accommodation.id} accommodation={accommodation} />;
};

export default EditAccommodationPage;
