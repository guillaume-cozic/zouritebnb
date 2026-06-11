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
  AutoSaveStatus,
} from '../AccommodationSlice';
import {
  selectCurrentAccommodation,
  selectAccommodationStatus,
  selectAccommodationError,
  selectEditSaveStatus,
} from '../AccommodationSelectors';
import { Accommodation } from '../AccommodationTypes';
import { AMENITY_CATEGORIES } from '../AmenityData';
import MapSelector from '../../../components/MapSelector';
import EditLayout, { SECTIONS, EditSection } from './EditLayout';

const API_BASE = process.env.REACT_APP_API_URL || 'http://localhost:8080';

// --- Sub-form schemas ---
const getLocationSchema = (t: TFunction) => z.object({
  street: z.string().min(1, t('addressStep.streetRequired')),
  city: z.string().min(1, t('addressStep.cityRequired')),
  zipCode: z.string().optional().default(''),
  country: z.string().min(1, t('addressStep.countryRequired')),
  latitude: z.preprocess((v) => (v === '' || v === undefined || v === null ? undefined : Number(v)), z.number().min(-90).max(90).optional()),
  longitude: z.preprocess((v) => (v === '' || v === undefined || v === null ? undefined : Number(v)), z.number().min(-180).max(180).optional()),
});

// --- Auto-save status badge ---
const SaveStatusBadge: React.FC<{ status: AutoSaveStatus; t: (key: string) => string }> = ({ status, t }) => {
  if (status === 'idle') return null;

  const config = {
    saving: {
      className: 'bg-blue-50 text-blue-600 border-blue-100',
      icon: (
        <svg className="animate-spin" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M21 12a9 9 0 11-6.219-8.56" /></svg>
      ),
      label: t('edit.autoSaving'),
    },
    saved: {
      className: 'bg-emerald-50 text-emerald-600 border-emerald-100',
      icon: (
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><path d="M20 6L9 17l-5-5" /></svg>
      ),
      label: t('edit.autoSaved'),
    },
    error: {
      className: 'bg-red-50 text-red-600 border-red-100',
      icon: (
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><circle cx="12" cy="12" r="10" /><path d="m15 9-6 6" /><path d="m9 9 6 6" /></svg>
      ),
      label: t('edit.autoError'),
    },
  }[status];

  return (
    <span className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border transition-all ${config.className}`}>
      {config.icon}
      {config.label}
    </span>
  );
};

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

  const inputClass = 'w-full h-11 rounded-xl border border-gray-200 bg-gray-50 px-4 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 focus:bg-white transition-all';

  return (
    <EditLayout
      accommodationId={id}
      accommodationTitle={accommodation.title ?? ''}
      activeSection={activeSection}
      error={error}
      onScrollTo={scrollTo}
      headerRight={
        <span className="hidden sm:inline-flex items-center gap-1.5 text-xs text-gray-400">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6" /></svg>
          {t('edit.autoSaveHint')}
        </span>
      }
    >
      <div className="space-y-6">
        {/* Description */}
        <div ref={(el) => { sectionRefs.current.description = el; }} className="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow p-6 sm:p-8">
          <div className="flex items-center justify-between mb-5">
            <div className="flex items-center gap-3">
              <div className="flex items-center justify-center w-9 h-9 rounded-xl bg-gray-100 text-gray-600">
                {SECTIONS[0].icon}
              </div>
              <h2 className="text-lg font-semibold">{t('edit.section.description')}</h2>
            </div>
            <SaveStatusBadge status={sectionStatus.description ?? 'idle'} t={t} />
          </div>
          <div className="space-y-4">
            {accommodation.photos && accommodation.photos.length > 0 && (
              <div className="w-24 h-24 rounded-lg overflow-hidden bg-gray-100 flex-shrink-0">
                <img
                  src={`${API_BASE}${accommodation.photos[0].url}`}
                  alt={accommodation.title}
                  className="w-full h-full object-cover"
                />
              </div>
            )}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1.5">{t('descriptionStep.titleLabel')}</label>
              <input type="text" value={title} onChange={handleTitleChange} className={inputClass} />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1.5">{t('descriptionStep.descriptionLabel')}</label>
              <textarea value={description} onChange={handleDescriptionChange} rows={5} className={`${inputClass} h-auto py-3`} />
            </div>
          </div>
        </div>

        {/* Price */}
        <div ref={(el) => { sectionRefs.current.price = el; }} className="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow p-6 sm:p-8">
          <div className="flex items-center justify-between mb-5">
            <div className="flex items-center gap-3">
              <div className="flex items-center justify-center w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600">
                {SECTIONS[1].icon}
              </div>
              <h2 className="text-lg font-semibold">{t('edit.section.price')}</h2>
            </div>
            <SaveStatusBadge status={sectionStatus.price ?? 'idle'} t={t} />
          </div>
          <div className="space-y-5">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1.5">{t('descriptionStep.priceLabel')}</label>
              <div className="relative max-w-xs">
                <input
                  type="number"
                  step="0.01"
                  value={price}
                  onChange={handlePriceChange}
                  className={`${inputClass} pr-20`}
                />
                <div className="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                  <span className="text-gray-400 font-medium text-sm">{t('descriptionStep.priceUnit')}</span>
                </div>
              </div>
            </div>
            <div className="pt-4 border-t border-gray-100">
              <label className="block text-sm font-medium text-gray-700 mb-1.5">{t('descriptionStep.weeklyPromotionLabel')}</label>
              <div className="relative max-w-xs">
                <input
                  type="number"
                  step="1"
                  min={0}
                  max={100}
                  placeholder="0"
                  value={weeklyPromotion}
                  onChange={handleWeeklyPromotionChange}
                  className={`${inputClass} pr-12`}
                />
                <div className="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                  <span className="text-gray-400 font-medium text-sm">{t('descriptionStep.weeklyPromotionUnit')}</span>
                </div>
              </div>
              <p className="mt-2 text-xs text-gray-500">{t('descriptionStep.weeklyPromotionHint')}</p>
              {(() => {
                const promo = weeklyPromotion === '' ? null : Number(weeklyPromotion);
                if (promo === null || !Number.isFinite(promo) || promo <= 0 || promo > 100 || price <= 0) return null;
                const discounted = price * (1 - promo / 100);
                const weekTotal = discounted * 7;
                const fmt = (n: number) => n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                return (
                  <div className="mt-3 inline-flex flex-wrap items-center gap-x-4 gap-y-1 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-2.5 text-sm">
                    <div className="flex items-center gap-2">
                      <span className="text-gray-500">{t('descriptionStep.discountedNightlyLabel')}</span>
                      <span className="text-gray-400 line-through">{fmt(price)} €</span>
                      <span className="font-semibold text-emerald-700">{fmt(discounted)} €</span>
                    </div>
                    <div className="flex items-center gap-2">
                      <span className="text-gray-500">{t('descriptionStep.weeklyTotalLabel')}</span>
                      <span className="font-semibold text-emerald-700">{fmt(weekTotal)} €</span>
                    </div>
                  </div>
                );
              })()}
            </div>
          </div>
        </div>

        {/* Capacity */}
        <div ref={(el) => { sectionRefs.current.capacity = el; }} className="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow p-6 sm:p-8">
          <div className="flex items-center justify-between mb-5">
            <div className="flex items-center gap-3">
              <div className="flex items-center justify-center w-9 h-9 rounded-xl bg-violet-50 text-violet-600">
                {SECTIONS[2].icon}
              </div>
              <h2 className="text-lg font-semibold">{t('edit.section.capacity')}</h2>
            </div>
            <SaveStatusBadge status={sectionStatus.capacity ?? 'idle'} t={t} />
          </div>
          <div className="grid grid-cols-2 sm:grid-cols-3 gap-4">
            {([
              { name: 'bedrooms', label: t('capacityStep.bedrooms') },
              { name: 'bathrooms', label: t('capacityStep.bathrooms') },
              { name: 'maxGuests', label: t('capacityStep.maxGuests') },
              { name: 'singleBeds', label: t('capacityStep.singleBeds') },
              { name: 'doubleBeds', label: t('capacityStep.doubleBeds') },
            ] as const).map((f) => (
              <div key={f.name}>
                <label className="block text-sm font-medium text-gray-700 mb-1.5">{f.label}</label>
                <input
                  type="number"
                  min={0}
                  value={capacityValues[f.name]}
                  onChange={(e) => handleCapacityChange(f.name, Number(e.target.value))}
                  className={inputClass}
                />
              </div>
            ))}
          </div>
        </div>

        {/* Amenities */}
        <div ref={(el) => { sectionRefs.current.amenities = el; }} className="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow p-6 sm:p-8">
          <div className="flex items-center justify-between mb-5">
            <div className="flex items-center gap-3">
              <div className="flex items-center justify-center w-9 h-9 rounded-xl bg-amber-50 text-amber-600">
                {SECTIONS[3].icon}
              </div>
              <h2 className="text-lg font-semibold">{t('edit.section.amenities')}</h2>
            </div>
            <div className="flex items-center gap-2">
              <SaveStatusBadge status={sectionStatus.amenities ?? 'idle'} t={t} />
              <span className="text-sm text-gray-500 bg-gray-100 rounded-full px-3 py-1 font-medium">{selectedAmenities.size} {t('edit.amenitiesSelected')}</span>
            </div>
          </div>
          <div className="space-y-6">
            {AMENITY_CATEGORIES.map((cat) => (
              <div key={cat.key}>
                <h3 className="text-sm font-semibold text-gray-700 mb-2.5">{t(`amenityCategories.${cat.key}`)}</h3>
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
                            ? 'bg-blue-600 text-white border-transparent shadow-sm shadow-blue-500/25'
                            : 'bg-white text-gray-700 border-gray-200 hover:border-blue-300 hover:bg-blue-50'
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
        </div>

        {/* Location */}
        <div ref={(el) => { sectionRefs.current.location = el; }} className="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow p-6 sm:p-8">
          <div className="flex items-center justify-between mb-5">
            <div className="flex items-center gap-3">
              <div className="flex items-center justify-center w-9 h-9 rounded-xl bg-rose-50 text-rose-600">
                {SECTIONS[4].icon}
              </div>
              <h2 className="text-lg font-semibold">{t('edit.section.location')}</h2>
            </div>
            <SaveStatusBadge status={sectionStatus.location ?? 'idle'} t={t} />
          </div>
          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1.5">{t('addressStep.street')}</label>
              <input type="text" {...locationForm.register('street', { onChange: dispatchLocationEdited })} className={inputClass} />
              {locationForm.formState.errors.street && <p className="mt-1 text-sm text-red-600">{String(locationForm.formState.errors.street.message)}</p>}
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1.5">{t('addressStep.city')}</label>
                <input type="text" {...locationForm.register('city', { onChange: dispatchLocationEdited })} className={inputClass} />
                {locationForm.formState.errors.city && <p className="mt-1 text-sm text-red-600">{String(locationForm.formState.errors.city.message)}</p>}
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1.5">{t('addressStep.zipCode')}</label>
                <input type="text" {...locationForm.register('zipCode', { onChange: dispatchLocationEdited })} className={inputClass} />
              </div>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1.5">{t('addressStep.country')}</label>
              <input type="text" {...locationForm.register('country', { onChange: dispatchLocationEdited })} className={inputClass} />
              {locationForm.formState.errors.country && <p className="mt-1 text-sm text-red-600">{String(locationForm.formState.errors.country.message)}</p>}
            </div>
            <div className="rounded-xl overflow-hidden border border-gray-100">
              <label className="block text-sm font-medium text-gray-700 mb-1.5 px-1">{t('addressStep.mapTitle')}</label>
              <MapSelector
                latitude={locationForm.watch('latitude')}
                longitude={locationForm.watch('longitude')}
                onSelect={handleMapSelect}
              />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1.5">{t('addressStep.latitude')}</label>
                <input type="number" step="any" {...locationForm.register('latitude', { onChange: dispatchLocationEdited })} className={inputClass} />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1.5">{t('addressStep.longitude')}</label>
                <input type="number" step="any" {...locationForm.register('longitude', { onChange: dispatchLocationEdited })} className={inputClass} />
              </div>
            </div>
          </div>
        </div>

        {/* Check-in / Check-out */}
        <div ref={(el) => { sectionRefs.current.checkinout = el; }} className="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow p-6 sm:p-8">
          <div className="flex items-center justify-between mb-5">
            <div className="flex items-center gap-3">
              <div className="flex items-center justify-center w-9 h-9 rounded-xl bg-sky-50 text-sky-600">
                {SECTIONS[5].icon}
              </div>
              <h2 className="text-lg font-semibold">{t('edit.section.checkinout')}</h2>
            </div>
            <SaveStatusBadge status={sectionStatus.checkinout ?? 'idle'} t={t} />
          </div>
          <div className="grid grid-cols-2 gap-4 max-w-md">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1.5">{t('detail.checkIn')}</label>
              <input
                type="time"
                value={checkInOut.checkIn}
                onChange={(e) => handleCheckInOutChange('checkIn', e.target.value)}
                className={inputClass}
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1.5">{t('detail.checkOut')}</label>
              <input
                type="time"
                value={checkInOut.checkOut}
                onChange={(e) => handleCheckInOutChange('checkOut', e.target.value)}
                className={inputClass}
              />
            </div>
          </div>
        </div>

        {/* Photos — clickable card linking to dedicated page */}
        <Link
          to={`/accommodations/${id}/photos`}
          ref={(el) => { sectionRefs.current.photos = el; }}
          className="block bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md hover:border-blue-200 transition-all p-6 sm:p-8 group cursor-pointer"
        >
          <div className="flex items-center justify-between mb-5">
            <div className="flex items-center gap-3">
              <div className="flex items-center justify-center w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600">
                {SECTIONS[6].icon}
              </div>
              <h2 className="text-lg font-semibold">{t('edit.section.photos')}</h2>
            </div>
            <span className="inline-flex items-center gap-1.5 text-sm text-blue-600 font-medium group-hover:gap-2.5 transition-all">
              {t('edit.managePhotos')}
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M5 12h14" /><path d="m12 5 7 7-7 7" /></svg>
            </span>
          </div>

          {accommodation.photos && accommodation.photos.length > 0 ? (
            <div className="relative w-full max-w-sm aspect-[3/2] rounded-xl overflow-hidden bg-gray-100">
              <img
                src={`${API_BASE}${accommodation.photos[0].url}`}
                alt={accommodation.title}
                className="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
              />
            </div>
          ) : (
            <div className="flex items-center gap-3 p-4 rounded-xl bg-gray-50 border border-dashed border-gray-200">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" className="text-gray-300 flex-shrink-0"><rect width="18" height="18" x="3" y="3" rx="2" ry="2" /><circle cx="9" cy="9" r="2" /><path d="m21 15-3.086-3.086a2 2 0 00-2.828 0L6 21" /></svg>
              <p className="text-sm text-gray-400">{t('edit.noPhotos')}</p>
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
              <div className="h-8 bg-gray-200 rounded-lg w-1/3" />
              <div className="h-64 bg-gray-100 rounded-lg" />
            </div>
          </div>
        </main>
      );
    }
    return (
      <main className="min-h-screen py-8">
        <div className="max-w-7xl mx-auto px-4 text-center py-20">
          <p className="text-red-500 mb-4">{error || t('edit.notFound')}</p>
          <Link to="/" className="text-blue-600 hover:underline">{t('detail.backToHome')}</Link>
        </div>
      </main>
    );
  }

  return <EditAccommodationForm key={accommodation.id} accommodation={accommodation} />;
};

export default EditAccommodationPage;
