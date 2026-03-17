import React, { useEffect, useState, useRef, useCallback } from 'react';
import { useParams, Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { TFunction } from 'i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import {
  fetchAccommodation,
  updatePrice,
  updateDescription,
  setLocation,
  setCapacity,
  setAmenities,
  setCheckInOut,
} from '../AccommodationSlice';
import { selectCurrentAccommodation, selectAccommodationStatus, selectAccommodationError } from '../AccommodationSelectors';
import { AMENITY_CATEGORIES } from '../AmenityData';
import MapSelector from '../../../components/MapSelector';
import EditLayout, { SECTIONS, EditSection } from './EditLayout';

const API_BASE = process.env.REACT_APP_API_URL || 'http://localhost:8080';

type AutoSaveStatus = 'idle' | 'saving' | 'saved' | 'error';

const AUTOSAVE_DELAY = 1200;

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

// --- Hook: useDebounce ---
function useDebounce<T extends (...args: any[]) => any>(fn: T, delay: number): T {
  const timerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const fnRef = useRef(fn);
  fnRef.current = fn;

  useEffect(() => () => { if (timerRef.current) clearTimeout(timerRef.current); }, []);

  return useCallback((...args: any[]) => {
    if (timerRef.current) clearTimeout(timerRef.current);
    timerRef.current = setTimeout(() => fnRef.current(...args), delay);
  }, [delay]) as unknown as T;
}

const EditAccommodationPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const { t } = useTranslation();
  const dispatch = useAppDispatch();
  const accommodation = useAppSelector(selectCurrentAccommodation);
  const status = useAppSelector(selectAccommodationStatus);
  const error = useAppSelector(selectAccommodationError);

  const [activeSection, setActiveSection] = useState<EditSection>('description');
  const sectionRefs = useRef<Record<string, HTMLElement | null>>({});

  // Per-section auto-save status
  const [sectionStatus, setSectionStatus] = useState<Record<string, AutoSaveStatus>>({});
  const statusTimers = useRef<Record<string, ReturnType<typeof setTimeout>>>({});

  // Track whether initial data has loaded (to avoid auto-saving on mount)
  const initialLoadDone = useRef(false);
  const amenitiesInitialized = useRef(false);

  // Amenities local state
  const [selectedAmenities, setSelectedAmenities] = useState<Set<string>>(new Set());

  // Price local state
  const [price, setPrice] = useState<number>(0);
  const priceInitialized = useRef(false);

  // Capacity local state
  const [capacityValues, setCapacityValues] = useState({
    bedrooms: 0, bathrooms: 0, maxGuests: 0, singleBeds: 0, doubleBeds: 0,
  });
  const capacityInitialized = useRef(false);

  // Title & description local state
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const titleInitialized = useRef(false);
  const descriptionInitialized = useRef(false);

  // CheckInOut local state
  const [checkInOut, setCheckInOutState] = useState({ checkIn: '16:00', checkOut: '12:00' });
  const checkInOutInitialized = useRef(false);

  // Location form
  const locationForm = useForm({
    resolver: zodResolver(getLocationSchema(t)),
  });
  const locationInitialized = useRef(false);

  const setSectionSaveStatus = useCallback((section: string, s: AutoSaveStatus) => {
    setSectionStatus((prev) => ({ ...prev, [section]: s }));
    if (statusTimers.current[section]) clearTimeout(statusTimers.current[section]);
    if (s === 'saved') {
      statusTimers.current[section] = setTimeout(() => {
        setSectionStatus((prev) => ({ ...prev, [section]: 'idle' }));
      }, 2500);
    }
  }, []);

  useEffect(() => {
    if (id) dispatch(fetchAccommodation(id));
  }, [dispatch, id]);

  // Populate all local state from accommodation
  useEffect(() => {
    if (!accommodation) return;
    if (!titleInitialized.current) {
      setTitle(accommodation.title ?? '');
      titleInitialized.current = true;
    }
    if (!descriptionInitialized.current) {
      setDescription(accommodation.description ?? '');
      descriptionInitialized.current = true;
    }
    if (!priceInitialized.current) {
      setPrice(accommodation.price ?? 0);
      priceInitialized.current = true;
    }
    if (!capacityInitialized.current) {
      setCapacityValues({
        bedrooms: accommodation.bedrooms ?? 0,
        bathrooms: accommodation.bathrooms ?? 0,
        maxGuests: accommodation.maxGuests ?? 0,
        singleBeds: accommodation.singleBeds ?? 0,
        doubleBeds: accommodation.doubleBeds ?? 0,
      });
      capacityInitialized.current = true;
    }
    if (!checkInOutInitialized.current) {
      setCheckInOutState({
        checkIn: accommodation.checkIn ?? '16:00',
        checkOut: accommodation.checkOut ?? '12:00',
      });
      checkInOutInitialized.current = true;
    }
    if (!amenitiesInitialized.current && accommodation.amenities) {
      setSelectedAmenities(new Set(accommodation.amenities));
      amenitiesInitialized.current = true;
    }
    if (!locationInitialized.current) {
      locationForm.reset({
        street: accommodation.street ?? '',
        city: accommodation.city ?? '',
        zipCode: accommodation.zipCode ?? '',
        country: accommodation.country ?? '',
        latitude: accommodation.latitude,
        longitude: accommodation.longitude,
      });
      locationInitialized.current = true;
    }
    // Mark initial load as done after a tick
    if (!initialLoadDone.current) {
      setTimeout(() => { initialLoadDone.current = true; }, 100);
    }
  }, [accommodation]); // eslint-disable-line react-hooks/exhaustive-deps

  const scrollTo = (key: EditSection) => {
    setActiveSection(key);
    sectionRefs.current[key]?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  // --- Auto-save handlers ---
  const autoSaveDescription = useDebounce(async (t: string, d: string) => {
    if (!accommodation?.id || !initialLoadDone.current) return;
    if (!t.trim() || !d.trim()) return;
    setSectionSaveStatus('description', 'saving');
    try {
      await dispatch(updateDescription({ id: accommodation.id, title: t, description: d })).unwrap();
      setSectionSaveStatus('description', 'saved');
    } catch {
      setSectionSaveStatus('description', 'error');
    }
  }, AUTOSAVE_DELAY);

  const autoSavePrice = useDebounce(async (value: number) => {
    if (!accommodation?.id || !initialLoadDone.current || value <= 0) return;
    setSectionSaveStatus('price', 'saving');
    try {
      await dispatch(updatePrice({ id: accommodation.id, price: value })).unwrap();
      setSectionSaveStatus('price', 'saved');
    } catch {
      setSectionSaveStatus('price', 'error');
    }
  }, AUTOSAVE_DELAY);

  const autoSaveCapacity = useDebounce(async (vals: typeof capacityValues) => {
    if (!accommodation?.id || !initialLoadDone.current) return;
    setSectionSaveStatus('capacity', 'saving');
    try {
      await dispatch(setCapacity({ id: accommodation.id, ...vals })).unwrap();
      setSectionSaveStatus('capacity', 'saved');
    } catch {
      setSectionSaveStatus('capacity', 'error');
    }
  }, AUTOSAVE_DELAY);

  const autoSaveAmenities = useDebounce(async (codes: string[]) => {
    if (!accommodation?.id || !initialLoadDone.current) return;
    setSectionSaveStatus('amenities', 'saving');
    try {
      await dispatch(setAmenities({ id: accommodation.id, codes })).unwrap();
      setSectionSaveStatus('amenities', 'saved');
    } catch {
      setSectionSaveStatus('amenities', 'error');
    }
  }, AUTOSAVE_DELAY);

  const autoSaveCheckInOut = useDebounce(async (vals: { checkIn: string; checkOut: string }) => {
    if (!accommodation?.id || !initialLoadDone.current) return;
    setSectionSaveStatus('checkinout', 'saving');
    try {
      await dispatch(setCheckInOut({ id: accommodation.id, ...vals })).unwrap();
      setSectionSaveStatus('checkinout', 'saved');
    } catch {
      setSectionSaveStatus('checkinout', 'error');
    }
  }, AUTOSAVE_DELAY);

  const autoSaveLocation = useDebounce(async () => {
    if (!accommodation?.id || !initialLoadDone.current) return;
    const valid = await locationForm.trigger();
    if (!valid) return;
    const data = locationForm.getValues();
    setSectionSaveStatus('location', 'saving');
    try {
      await dispatch(setLocation({
        id: accommodation.id,
        street: data.street,
        city: data.city,
        zipCode: data.zipCode ?? '',
        country: data.country,
        latitude: data.latitude,
        longitude: data.longitude,
      })).unwrap();
      setSectionSaveStatus('location', 'saved');
    } catch {
      setSectionSaveStatus('location', 'error');
    }
  }, AUTOSAVE_DELAY);

  // --- Change handlers that trigger auto-save ---
  const handleTitleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const v = e.target.value;
    setTitle(v);
    autoSaveDescription(v, description);
  };

  const handleDescriptionChange = (e: React.ChangeEvent<HTMLTextAreaElement>) => {
    const v = e.target.value;
    setDescription(v);
    autoSaveDescription(title, v);
  };

  const handlePriceChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const v = Number(e.target.value);
    setPrice(v);
    autoSavePrice(v);
  };

  const handleCapacityChange = (field: string, value: number) => {
    setCapacityValues((prev) => {
      const next = { ...prev, [field]: value };
      autoSaveCapacity(next);
      return next;
    });
  };

  const toggleAmenity = (code: string) => {
    setSelectedAmenities((prev) => {
      const next = new Set(prev);
      next.has(code) ? next.delete(code) : next.add(code);
      autoSaveAmenities(Array.from(next));
      return next;
    });
  };

  const handleCheckInOutChange = (field: 'checkIn' | 'checkOut', value: string) => {
    setCheckInOutState((prev) => {
      const next = { ...prev, [field]: value };
      autoSaveCheckInOut(next);
      return next;
    });
  };

  const handleLocationFieldChange = () => {
    autoSaveLocation();
  };

  const handleMapSelect = (lat: number, lng: number) => {
    locationForm.setValue('latitude', lat, { shouldValidate: true });
    locationForm.setValue('longitude', lng, { shouldValidate: true });
    autoSaveLocation();
  };

  // Loading
  if (status === 'loading' && !accommodation) {
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

  if (!accommodation) {
    return (
      <main className="min-h-screen py-8">
        <div className="max-w-7xl mx-auto px-4 text-center py-20">
          <p className="text-red-500 mb-4">{error || t('edit.notFound')}</p>
          <Link to="/" className="text-blue-600 hover:underline">{t('detail.backToHome')}</Link>
        </div>
      </main>
    );
  }

  const inputClass = 'w-full h-11 rounded-xl border border-gray-200 bg-gray-50 px-4 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 focus:bg-white transition-all';

  return (
    <EditLayout
      accommodationId={accommodation.id!}
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
              <input type="text" {...locationForm.register('street', { onChange: handleLocationFieldChange })} className={inputClass} />
              {locationForm.formState.errors.street && <p className="mt-1 text-sm text-red-600">{String(locationForm.formState.errors.street.message)}</p>}
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1.5">{t('addressStep.city')}</label>
                <input type="text" {...locationForm.register('city', { onChange: handleLocationFieldChange })} className={inputClass} />
                {locationForm.formState.errors.city && <p className="mt-1 text-sm text-red-600">{String(locationForm.formState.errors.city.message)}</p>}
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1.5">{t('addressStep.zipCode')}</label>
                <input type="text" {...locationForm.register('zipCode', { onChange: handleLocationFieldChange })} className={inputClass} />
              </div>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1.5">{t('addressStep.country')}</label>
              <input type="text" {...locationForm.register('country', { onChange: handleLocationFieldChange })} className={inputClass} />
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
                <input type="number" step="any" {...locationForm.register('latitude', { onChange: handleLocationFieldChange })} className={inputClass} />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1.5">{t('addressStep.longitude')}</label>
                <input type="number" step="any" {...locationForm.register('longitude', { onChange: handleLocationFieldChange })} className={inputClass} />
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
          to={`/accommodations/${accommodation.id}/photos`}
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

export default EditAccommodationPage;
