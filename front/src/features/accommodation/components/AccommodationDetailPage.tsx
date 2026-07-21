import React, { useEffect, useState } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import DatePicker, { registerLocale } from 'react-datepicker';
import { fr } from 'date-fns/locale/fr';
import { enGB } from 'date-fns/locale/en-GB';
import 'react-datepicker/dist/react-datepicker.css';
import '../../../styles/datepicker-overrides.css';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { setFilters } from '../../homepage/HomepageSlice';
import Footer from '../../../components/Footer';
import { selectHomepageFilters } from '../../homepage/HomepageSelectors';
import { fetchSolidarityProjects } from '../../solidarityProject/SolidarityProjectSlice';
import { selectSolidarityProjects } from '../../solidarityProject/SolidarityProjectSelectors';
import { projectExcerpt } from '../../solidarityProject/SolidarityProjectText';
import { selectAuthUser } from '../../auth/AuthSelectors';
import LocationMap from '../../../components/LocationMap';
import PhotoLightbox from '../../../components/PhotoLightbox';
import { fetchAccommodation } from '../AccommodationSlice';
import { computeStayPrice } from '../pricing';
import { fetchAccommodationAvailability } from '../../reservation/ReservationSlice';
import { selectAccommodationAvailability } from '../../reservation/ReservationSelectors';
import RatingBadge from '../../review/components/RatingBadge';
import HostProfileCard from '../../hostProfile/components/HostProfileCard';
import AccommodationReviews from '../../review/components/AccommodationReviews';
import { fetchAccommodationReviews } from '../../review/ReviewSlice';
import { selectAccommodationReviews } from '../../review/ReviewSelectors';
import { selectCurrentAccommodation, selectAccommodationStatus, selectAccommodationError } from '../AccommodationSelectors';
import WishlistButton from '../../wishlist/components/WishlistButton';

registerLocale('fr', fr);
registerLocale('en', enGB);

const toDate = (s: string): Date | null => (s ? new Date(s) : null);
/** Parses an ISO YYYY-MM-DD string as a local-midnight Date (timezone-safe). */
const parseLocalDate = (s: string): Date => {
  const [y, m, d] = s.split('-').map(Number);
  return new Date(y, m - 1, d);
};
const toStr = (d: Date | null): string => {
  if (!d) return '';
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
};

const PLATFORM_COMMISSION_RATE = 0.08;
const SOLIDARITY_RATE = 0.07;

const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:8080';

const AccommodationDetailPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const { t, i18n } = useTranslation();
  const dispatch = useAppDispatch();
  const accommodation = useAppSelector(selectCurrentAccommodation);
  const status = useAppSelector(selectAccommodationStatus);
  const error = useAppSelector(selectAccommodationError);
  const filters = useAppSelector(selectHomepageFilters);
  const solidarityProjects = useAppSelector(selectSolidarityProjects);
  const [lightboxIndex, setLightboxIndex] = useState<number | null>(null);
  const [selectedProjectId, setSelectedProjectId] = useState<string>('');
  const user = useAppSelector(selectAuthUser);
  const reviews = useAppSelector(selectAccommodationReviews);
  const availability = useAppSelector(selectAccommodationAvailability);
  const navigate = useNavigate();

  // Booked nights → date intervals the picker disables and strikes through.
  // A range covers checkIn..(checkOut - 1): the departure day stays bookable for a new arrival.
  const excludeDateIntervals = React.useMemo(
    () =>
      availability
        .map(({ checkIn, checkOut }) => {
          const start = parseLocalDate(checkIn);
          const end = parseLocalDate(checkOut);
          end.setDate(end.getDate() - 1);
          return { start, end };
        })
        .filter(({ start, end }) => end >= start),
    [availability]
  );

  const startDate = toDate(filters.checkIn);
  const endDate = toDate(filters.checkOut);
  const guests = filters.guests ?? 1;

  const handleDateChange = (dates: [Date | null, Date | null]) => {
    const [start, end] = dates;
    if (startDate && !endDate && start && !end && start < startDate) {
      dispatch(setFilters({ checkIn: toStr(start), checkOut: toStr(startDate) }));
      return;
    }
    dispatch(setFilters({ checkIn: toStr(start), checkOut: toStr(end) }));
  };

  const pricePerNight = accommodation?.price ?? 0;
  // Mirror the backend night-by-night pricing (periods, weekend, last-minute, weekly promo).
  const stay = startDate && endDate
    ? computeStayPrice(
        {
          pricePerNight,
          weeklyPromotionPercentage: accommodation?.weeklyPromotionPercentage,
          weekendSurchargePercentage: accommodation?.weekendSurchargePercentage,
          lastMinuteDiscountPercentage: accommodation?.lastMinuteDiscountPercentage,
          lastMinuteDays: accommodation?.lastMinuteDays,
          pricePeriods: accommodation?.pricePeriods,
        },
        startDate,
        endDate,
        new Date()
      )
    : { nights: 0, subtotal: 0, appliedDiscountPercentage: null };
  const nights = stay.nights;
  const subtotal = stay.subtotal;
  const appliedDiscount = stay.appliedDiscountPercentage;
  const platformFee = subtotal * PLATFORM_COMMISSION_RATE;
  const solidarityFee = subtotal * SOLIDARITY_RATE;
  const total = subtotal + platformFee + solidarityFee;
  const formatPrice = (n: number) => `${n.toLocaleString(i18n.language, { maximumFractionDigits: 2 })}\u00A0€`;
  useEffect(() => {
    if (id) {
      dispatch(fetchAccommodation(id));
      dispatch(fetchAccommodationReviews(id));
      dispatch(fetchAccommodationAvailability(id));
    }
  }, [dispatch, id]);

  useEffect(() => {
    dispatch(fetchSolidarityProjects());
  }, [dispatch]);

  // The host's featured project id is exposed publicly on the accommodation, so the
  // page stays public and never hits the members-only team endpoint (bank details).
  const activeProjects = solidarityProjects.filter((p) => p.status === 'active');
  const favoriteProject = accommodation?.favoriteSolidarityProjectId
    ? activeProjects.find((p) => p.id === accommodation.favoriteSolidarityProjectId) ?? null
    : null;
  const platformDefaultProject = activeProjects.find((p) => p.isDefault) ?? null;
  const preselectedProject = favoriteProject ?? platformDefaultProject ?? activeProjects[0] ?? null;
  // The host's featured project, or the platform's default project when the host has none.
  const highlightedProject = favoriteProject ?? platformDefaultProject;

  useEffect(() => {
    if (!selectedProjectId && preselectedProject) {
      setSelectedProjectId(preselectedProject.id);
    }
  }, [preselectedProject, selectedProjectId]);

  // Loading
  if (status === 'loading') {
    return (
      <div className="min-h-screen py-8">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="animate-pulse space-y-6">
            <div className="h-8 bg-gray-200 rounded-lg w-1/3" />
            <div className="h-5 bg-gray-100 rounded-lg w-1/4" />
            <div className="aspect-[2/1] bg-gray-200 rounded-lg" />
            <div className="grid grid-cols-3 gap-4">
              <div className="h-20 bg-gray-100 rounded-lg" />
              <div className="h-20 bg-gray-100 rounded-lg" />
              <div className="h-20 bg-gray-100 rounded-lg" />
            </div>
            <div className="h-32 bg-gray-100 rounded-lg" />
          </div>
        </div>
      </div>
    );
  }

  // Error
  if (status === 'failed') {
    return (
      <div className="min-h-screen py-8">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center py-20">
          <div className="w-16 h-16 mx-auto mb-4 rounded-full bg-red-50 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-red-400"><circle cx="12" cy="12" r="10" /><path d="m15 9-6 6" /><path d="m9 9 6 6" /></svg>
          </div>
          <p className="text-red-500 mb-4">{error}</p>
          <Link to="/" className="text-primary-600 hover:underline">{t('detail.backToHome')}</Link>
        </div>
      </div>
    );
  }

  if (!accommodation) return null;

  const photos = (accommodation.photos ?? []).map((p) => `${API_BASE}${p.url}`);
  // Only members of the accommodation's owning team may edit it.
  const canEditAccommodation = !!(user && accommodation.teamId && user.teamId === accommodation.teamId);
  // A host cannot book an accommodation owned by their own team (mirrors the API guard).
  const isOwnAccommodation = canEditAccommodation;

  return (
    <>
    <div className="min-h-screen py-8">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Header */}
        <div className="flex justify-between items-start mb-6">
          <div>
            <div className="flex items-center gap-3 mb-2">
              <h1 className="text-3xl font-bold">{accommodation.title}</h1>
              {accommodation.type && (
                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border bg-gray-50 text-gray-700 border-gray-200">
                  {t(`accommodationType.${accommodation.type}`)}
                </span>
              )}
              {accommodation.status && accommodation.status !== 'published' && (
                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border bg-amber-50 text-amber-700 border-amber-200">
                  {accommodation.status}
                </span>
              )}
            </div>
            <div className="flex items-center gap-4 text-gray-500">
              {accommodation.averageRating != null && (accommodation.reviewCount ?? 0) > 0 && (
                <RatingBadge rating={accommodation.averageRating} count={accommodation.reviewCount} />
              )}
              <div className="flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="mr-1">
                  <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0" />
                  <circle cx="12" cy="10" r="3" />
                </svg>
                {[accommodation.city, accommodation.country].filter(Boolean).join(', ')}
              </div>
            </div>
          </div>
          <div className="flex items-center gap-2">
            {accommodation.id && (
              <WishlistButton accommodationId={accommodation.id} variant="inline" />
            )}
            {canEditAccommodation && (
              <Link
                to={`/accommodations/${accommodation.id}/edit`}
                className="flex items-center gap-2 border border-gray-200 bg-white hover:bg-gray-50 h-10 px-4 py-2 rounded-md text-sm font-medium transition-colors"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                  <path d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                </svg>
                {t('edit.title')}
              </Link>
            )}
          </div>
        </div>

        {/* Two-column layout */}
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
          {/* Left column */}
          <div className="lg:col-span-8">
            {/* Photo gallery */}
            <div className="mb-6">
              {photos.length === 0 ? (
                <div className="aspect-[2/1] relative rounded-2xl overflow-hidden bg-gray-100 flex items-center justify-center">
                  <svg xmlns="http://www.w3.org/2000/svg" className="h-20 w-20 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                </div>
              ) : (
                <div className="space-y-3">
                  <button
                    onClick={() => setLightboxIndex(0)}
                    className="block w-full aspect-[2/1] rounded-2xl overflow-hidden bg-gray-100 group focus:outline-none focus:ring-2 focus:ring-primary-500"
                  >
                    <img
                      src={photos[0]}
                      alt={`${accommodation.title} - 1`}
                      className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                    />
                  </button>
                  {photos.length > 1 && (
                    <div className="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3">
                      {photos.slice(1).map((src, idx) => (
                        <button
                          key={src}
                          onClick={() => setLightboxIndex(idx + 1)}
                          className="aspect-square rounded-xl overflow-hidden bg-gray-100 group focus:outline-none focus:ring-2 focus:ring-primary-500"
                        >
                          <img
                            src={src}
                            alt={`${accommodation.title} - ${idx + 2}`}
                            className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                          />
                        </button>
                      ))}
                    </div>
                  )}
                </div>
              )}
            </div>

            {lightboxIndex !== null && (
              <PhotoLightbox
                photos={photos}
                index={lightboxIndex}
                onClose={() => setLightboxIndex(null)}
                onChange={setLightboxIndex}
              />
            )}

            {/* Capacity */}
            <div className="border-b pb-8 mb-8">
              <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div>
                  <div className="text-lg font-semibold">{accommodation.maxGuests ?? 0} {t('detail.guests')}</div>
                  <div className="text-gray-500 text-sm">{t('detail.capacity')}</div>
                </div>
                <div>
                  <div className="text-lg font-semibold">{accommodation.bedrooms ?? 0} {t('detail.bedrooms')}</div>
                  <div className="text-gray-500 text-sm">
                    {[
                      accommodation.doubleBeds ? `${accommodation.doubleBeds} ${t('detail.doubleBeds')}` : null,
                      accommodation.singleBeds ? `${accommodation.singleBeds} ${t('detail.singleBeds')}` : null,
                    ].filter(Boolean).join(' · ') || '—'}
                  </div>
                </div>
                <div>
                  <div className="text-lg font-semibold">{accommodation.bathrooms ?? 0} {t('detail.bathrooms')}</div>
                  <div className="text-gray-500 text-sm">{t('detail.private')}</div>
                </div>
                <div>
                  <div className="text-lg font-semibold">{accommodation.price ?? 0}{'\u00A0'}€</div>
                  <div className="text-gray-500 text-sm">/ {t('detail.night')}</div>
                </div>
              </div>

              {/* Check-in / Check-out */}
              {(accommodation.checkIn || accommodation.checkOut) && (
                <div className="flex items-center gap-8 mb-6">
                  {accommodation.checkIn && (
                    <div className="flex items-center gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-gray-400"><circle cx="12" cy="12" r="10" /><path d="M12 6v6l4 2" /></svg>
                      <div><span className="font-medium">{t('detail.checkIn')} :</span> <span>{accommodation.checkIn}</span></div>
                    </div>
                  )}
                  {accommodation.checkOut && (
                    <div className="flex items-center gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-gray-400"><circle cx="12" cy="12" r="10" /><path d="M12 6v6l4 2" /></svg>
                      <div><span className="font-medium">{t('detail.checkOut')} :</span> <span>{accommodation.checkOut}</span></div>
                    </div>
                  )}
                </div>
              )}

              {/* Description */}
              <p className="text-lg whitespace-pre-line">{accommodation.description}</p>
            </div>

            {/* Cancellation policy */}
            <div className="border-b pb-8 mb-8">
              <h2 className="text-2xl font-semibold mb-4">{t('detail.cancellationTitle')}</h2>
              <div className="flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-gray-400 mt-0.5 flex-shrink-0"><circle cx="12" cy="12" r="10" /><path d="M12 8v4" /><path d="M12 16h.01" /></svg>
                <div>
                  <p className="font-medium">{t(`cancellationStep.${accommodation.cancellationPolicy ?? 'flexible'}.title`)}</p>
                  <p className="text-gray-500">{t(`cancellationStep.${accommodation.cancellationPolicy ?? 'flexible'}.description`)}</p>
                </div>
              </div>
            </div>

            {/* House rules */}
            <div className="border-b pb-8 mb-8">
              <h2 className="text-2xl font-semibold mb-6">{t('houseRules.title')}</h2>
              <div className="space-y-3">
                {([
                  ['smokingAllowed', accommodation.smokingAllowed ?? false],
                  ['petsAllowed', accommodation.petsAllowed ?? false],
                  ['partiesAllowed', accommodation.partiesAllowed ?? false],
                ] as const).map(([rule, allowed]) => (
                  <div key={rule} className="flex items-center gap-3">
                    {allowed ? (
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-primary-600 flex-shrink-0"><path d="M20 6L9 17l-5-5" /></svg>
                    ) : (
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-gray-400 flex-shrink-0"><path d="M18 6L6 18" /><path d="M6 6l12 12" /></svg>
                    )}
                    <span>{t(`houseRules.${rule}${allowed ? '' : 'Not'}`)}</span>
                  </div>
                ))}
              </div>
              {accommodation.houseRulesNotes && (
                <p className="text-gray-500 whitespace-pre-line mt-4">{accommodation.houseRulesNotes}</p>
              )}
            </div>

            {/* Amenities */}
            {accommodation.amenities && accommodation.amenities.length > 0 && (
              <div className="border-b pb-8 mb-8">
                <h2 className="text-2xl font-semibold mb-6">{t('detail.amenitiesTitle')}</h2>
                <div className="grid grid-cols-2 gap-4">
                  {accommodation.amenities.map((code) => (
                    <div key={code} className="flex items-center gap-3">
                      <div className="w-2 h-2 rounded-full bg-primary-600" />
                      <span>{t(`amenities.${code}`, code)}</span>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* Address */}
            {accommodation.street && (
              <div className="border-b pb-8 mb-8">
                <h2 className="text-2xl font-semibold mb-6">{t('detail.locationTitle')}</h2>
                <div className="flex items-start gap-3">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-gray-400 mt-0.5 flex-shrink-0">
                    <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0" />
                    <circle cx="12" cy="10" r="3" />
                  </svg>
                  <div>
                    <p className="font-medium">{accommodation.street}</p>
                    <p className="text-gray-500">{[accommodation.zipCode, accommodation.city, accommodation.country].filter(Boolean).join(', ')}</p>
                  </div>
                </div>
                {accommodation.latitude !== undefined && accommodation.longitude !== undefined && (
                  <div className="mt-6">
                    <LocationMap
                      latitude={accommodation.latitude}
                      longitude={accommodation.longitude}
                      label={accommodation.title}
                    />
                  </div>
                )}
              </div>
            )}

            {/* Host's featured / supported solidarity project */}
            {highlightedProject && (
              <div className="rounded-2xl border border-primary-100 bg-gradient-to-br from-primary-50 to-white p-6 mb-8">
                <div className="flex items-center gap-2 mb-4">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" className="text-rose-500">
                    <path d="M19.5 12.572l-7.5 7.428l-7.5-7.428a5 5 0 1 1 7.5-6.566a5 5 0 1 1 7.5 6.572" />
                  </svg>
                  <p className="text-xs font-semibold uppercase tracking-wider text-primary-700">
                    {favoriteProject ? t('detail.favoriteProjectBadge') : t('detail.defaultProjectBadge')}
                  </p>
                </div>
                <div className="flex gap-5">
                  {highlightedProject.imageUrl && (
                    <img
                      src={highlightedProject.imageUrl}
                      alt={highlightedProject.title}
                      className="w-32 h-32 rounded-xl object-cover flex-shrink-0"
                    />
                  )}
                  <div className="flex-1 min-w-0">
                    <h3 className="text-xl font-bold text-gray-900 mb-2">{highlightedProject.title}</h3>
                    <p className="text-gray-600 text-sm line-clamp-3 mb-3">{projectExcerpt(highlightedProject.description)}</p>
                    <Link
                      to={`/solidarity-projects/${highlightedProject.id}`}
                      className="inline-flex items-center gap-1.5 text-sm font-semibold text-primary-700 hover:text-primary-800"
                    >
                      {t('detail.favoriteProjectDiscover')}
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M5 12h14" /><path d="m12 5 7 7-7 7" />
                      </svg>
                    </Link>
                  </div>
                </div>
              </div>
            )}

            {/* Host presentation */}
            <HostProfileCard teamId={accommodation.teamId} variant="full" />
          </div>

          {/* Right column - Booking card */}
          <div className="lg:col-span-4">
            <div className="rounded-2xl border border-gray-100 bg-white shadow-lg p-6 sticky top-24">
              {/* Price + rating */}
              <div className="flex items-center justify-between mb-6">
                <div className="text-2xl font-bold">
                  {formatPrice(pricePerNight)} <span className="text-base font-normal">/ {t('detail.night')}</span>
                </div>
              </div>

              {accommodation.instantBooking && (
                <div className="mb-5 inline-flex items-center gap-1.5 rounded-full bg-primary-50 border border-primary-200 px-3 py-1.5 text-xs font-semibold text-primary-700">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                    <path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z" />
                  </svg>
                  {t('detail.instantBookingBadge')}
                </div>
              )}

              {/* Dates */}
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium mb-2">{t('detail.datesLabel')}</label>
                  <DatePicker
                    selectsRange
                    startDate={startDate}
                    endDate={endDate}
                    onChange={handleDateChange}
                    locale={i18n.language}
                    minDate={new Date()}
                    excludeDateIntervals={excludeDateIntervals}
                    monthsShown={2}
                    placeholderText={t('detail.selectDates')}
                    dateFormat="dd/MM/yyyy"
                    className="w-full h-11 rounded-xl border border-gray-200 bg-gray-50 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 focus:bg-white transition-all"
                    isClearable
                  />
                  {excludeDateIntervals.length > 0 && (
                    <p className="mt-2 text-xs text-gray-500">
                      {t('detail.bookedDatesHint')}
                    </p>
                  )}
                  {(accommodation.minNights != null || accommodation.maxNights != null) && (
                    <p className="mt-2 text-xs text-gray-500">
                      {accommodation.minNights != null && t('detail.minNights', { count: accommodation.minNights })}
                      {accommodation.minNights != null && accommodation.maxNights != null && ' · '}
                      {accommodation.maxNights != null && t('detail.maxNights', { count: accommodation.maxNights })}
                    </p>
                  )}
                </div>

                {/* Guests */}
                <div>
                  <label className="block text-sm font-medium mb-2">{t('detail.guestsLabel')}</label>
                  <div className="h-11 flex items-center justify-between gap-2 rounded-xl border border-gray-200 bg-gray-50 pl-3 pr-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-gray-400 flex-shrink-0">
                      <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                      <circle cx="9" cy="7" r="4" />
                      <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                      <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                    <div className="flex items-center gap-2">
                      <button
                        type="button"
                        onClick={() => dispatch(setFilters({ guests: Math.max(1, guests - 1) }))}
                        disabled={guests <= 1}
                        aria-label="decrement guests"
                        className="inline-flex items-center justify-center w-8 h-8 rounded-full border border-gray-300 bg-white text-gray-700 hover:border-primary-400 hover:bg-primary-50 hover:text-primary-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                      >
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                          <path d="M5 12h14" />
                        </svg>
                      </button>
                      <span className="min-w-[24px] text-center text-sm font-semibold text-gray-900 tabular-nums">
                        {guests}
                      </span>
                      <button
                        type="button"
                        onClick={() => {
                          const max = accommodation.maxGuests ?? 99;
                          dispatch(setFilters({ guests: Math.min(max, guests + 1) }));
                        }}
                        disabled={!!(accommodation.maxGuests && guests >= accommodation.maxGuests)}
                        aria-label="increment guests"
                        className="inline-flex items-center justify-center w-8 h-8 rounded-full border border-gray-300 bg-white text-gray-700 hover:border-primary-400 hover:bg-primary-50 hover:text-primary-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                      >
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                          <path d="M12 5v14" />
                          <path d="M5 12h14" />
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>

                {/* Solidarity project */}
                <div>
                  <label className="block text-sm font-medium mb-2">{t('detail.solidarityProjectLabel')}</label>
                  <select
                    value={selectedProjectId}
                    onChange={(e) => setSelectedProjectId(e.target.value)}
                    className="w-full h-11 rounded-xl border border-gray-200 bg-gray-50 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 focus:bg-white transition-all"
                  >
                    <option value="">{t('detail.solidarityProjectPlaceholder')}</option>
                    {activeProjects.map((p) => (
                      <option key={p.id} value={p.id}>{p.title}</option>
                    ))}
                  </select>
                  {(() => {
                    const selected = activeProjects.find((p) => p.id === selectedProjectId);
                    if (!selected) return null;
                    return (
                      <div className="mt-3 rounded-xl bg-primary-50/60 border border-primary-100 p-3">
                        <div className="flex gap-3">
                          {selected.imageUrl && (
                            <img
                              src={selected.imageUrl}
                              alt={selected.title}
                              className="w-14 h-14 rounded-lg object-cover flex-shrink-0"
                              loading="lazy"
                            />
                          )}
                          <div className="min-w-0 flex-1">
                            <p className="text-sm font-semibold text-gray-900 leading-tight">{selected.title}</p>
                            <p className="text-xs text-gray-600 mt-1 leading-relaxed line-clamp-3">
                              {selected.description}
                            </p>
                            <Link
                              to={`/solidarity-projects/${selected.id}`}
                              className="inline-flex items-center gap-1 mt-1.5 text-xs font-medium text-primary-700 hover:text-primary-800"
                            >
                              {t('detail.solidarityProjectLearnMore')}
                              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M7 17 17 7" />
                                <path d="M7 7h10v10" />
                              </svg>
                            </Link>
                          </div>
                        </div>
                      </div>
                    );
                  })()}
                </div>

                {/* Price breakdown */}
                {nights > 0 && (
                  <div className="space-y-2 pt-4 border-t border-gray-100 text-sm">
                    {appliedDiscount !== null && (
                      <div className="flex items-center justify-between rounded-lg bg-emerald-50 border border-emerald-100 px-3 py-2 text-emerald-700">
                        <span className="text-xs font-medium">{t('detail.discountBadge', { percent: appliedDiscount })}</span>
                      </div>
                    )}
                    <div className="flex justify-between text-gray-600">
                      <span>{t('detail.nights', { count: nights })}</span>
                      <span>{formatPrice(subtotal)}</span>
                    </div>
                    <div className="flex justify-between text-gray-600">
                      <span>{t('detail.platformFee', { rate: Math.round(PLATFORM_COMMISSION_RATE * 100) })}</span>
                      <span>{formatPrice(platformFee)}</span>
                    </div>
                    <div className="flex justify-between text-gray-600">
                      <span>{t('detail.solidarityFee', { rate: Math.round(SOLIDARITY_RATE * 100) })}</span>
                      <span>{formatPrice(solidarityFee)}</span>
                    </div>
                    <div className="flex justify-between font-bold text-gray-900 pt-2 border-t border-gray-100">
                      <span>{t('detail.total')}</span>
                      <span>{formatPrice(total)}</span>
                    </div>
                  </div>
                )}

                {/* Reserve button — hidden for the owning team (a host cannot book their own place) */}
                {isOwnAccommodation ? (
                  <p className="rounded-xl bg-gray-50 border border-gray-200 px-4 py-3 text-center text-sm text-gray-500">
                    {t('detail.ownAccommodationNotice')}
                  </p>
                ) : (
                  <>
                    <button
                      type="button"
                      disabled={!startDate || !endDate || !accommodation}
                      onClick={() => {
                        if (!startDate || !endDate || !accommodation) return;
                        const params = new URLSearchParams({
                          checkIn: filters.checkIn,
                          checkOut: filters.checkOut,
                          guests: String(guests),
                        });
                        const target = `/accommodations/${accommodation.id}/book?${params.toString()}`;
                        if (!user) {
                          navigate(`/login?returnTo=${encodeURIComponent(target)}`);
                          return;
                        }
                        navigate(target);
                      }}
                      className="w-full inline-flex items-center justify-center h-11 rounded-xl px-8 text-sm font-semibold text-white bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 hover:shadow-primary-500/40 disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:from-primary-600 disabled:hover:to-primary-700"
                    >
                      {accommodation.instantBooking ? t('detail.reserveInstant') : t('detail.reserve')}
                    </button>
                    <p className="text-center text-sm text-gray-500">
                      {accommodation.instantBooking ? t('detail.instantConfirmNote') : t('detail.noCharge')}
                    </p>
                  </>
                )}
              </div>
            </div>
          </div>
        </div>
        <AccommodationReviews
          reviews={reviews}
          accommodationId={id ?? ''}
          canReply={!!user && !!accommodation?.teamId && user.teamId === accommodation.teamId}
        />
      </div>

    </div>
    <Footer />
    </>
  );
};

export default AccommodationDetailPage;
