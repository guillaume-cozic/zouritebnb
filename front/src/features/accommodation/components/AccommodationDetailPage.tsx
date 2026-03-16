import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { fetchAccommodation } from '../AccommodationSlice';
import { selectCurrentAccommodation, selectAccommodationStatus, selectAccommodationError } from '../AccommodationSelectors';

const API_BASE = process.env.REACT_APP_API_URL || 'http://localhost:8080';

const AccommodationDetailPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const { t } = useTranslation();
  const dispatch = useAppDispatch();
  const accommodation = useAppSelector(selectCurrentAccommodation);
  const status = useAppSelector(selectAccommodationStatus);
  const error = useAppSelector(selectAccommodationError);
  const [activePhoto, setActivePhoto] = useState(0);

  useEffect(() => {
    if (id) {
      dispatch(fetchAccommodation(id));
    }
  }, [dispatch, id]);

  // Loading
  if (status === 'loading') {
    return (
      <main className="min-h-screen py-8">
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
      </main>
    );
  }

  // Error
  if (status === 'failed') {
    return (
      <main className="min-h-screen py-8">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center py-20">
          <div className="w-16 h-16 mx-auto mb-4 rounded-full bg-red-50 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-red-400"><circle cx="12" cy="12" r="10" /><path d="m15 9-6 6" /><path d="m9 9 6 6" /></svg>
          </div>
          <p className="text-red-500 mb-4">{error}</p>
          <Link to="/" className="text-blue-600 hover:underline">{t('detail.backToHome')}</Link>
        </div>
      </main>
    );
  }

  if (!accommodation) return null;

  const thumbnailSrc = accommodation.thumbnailUrl
    ? `${API_BASE}${accommodation.thumbnailUrl}`
    : null;

  const photos = thumbnailSrc ? [thumbnailSrc] : [];

  return (
    <main className="min-h-screen py-8">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Header */}
        <div className="flex justify-between items-start mb-6">
          <div>
            <h1 className="text-3xl font-bold mb-2">{accommodation.title}</h1>
            <div className="flex items-center gap-4 text-gray-500">
              <div className="flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="mr-1">
                  <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0" />
                  <circle cx="12" cy="10" r="3" />
                </svg>
                {[accommodation.city, accommodation.country].filter(Boolean).join(', ')}
              </div>
            </div>
          </div>
          <button className="flex items-center gap-2 border border-gray-200 bg-white hover:bg-gray-50 h-10 px-4 py-2 rounded-md text-sm font-medium transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
              <path d="M2 9.5a5.5 5.5 0 0 1 9.591-3.676.56.56 0 0 0 .818 0A5.49 5.49 0 0 1 22 9.5c0 2.29-1.5 4-3 5.5l-5.492 5.313a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5" />
            </svg>
            {t('detail.save')}
          </button>
        </div>

        {/* Two-column layout */}
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
          {/* Left column */}
          <div className="lg:col-span-8">
            {/* Photo carousel */}
            <div className="mb-6">
              <div className="aspect-[2/1] relative rounded-2xl overflow-hidden bg-gray-100">
                {photos.length > 0 ? (
                  <img
                    src={photos[activePhoto]}
                    alt={`${accommodation.title} - Photo ${activePhoto + 1}`}
                    className="absolute inset-0 h-full w-full object-cover"
                  />
                ) : (
                  <div className="absolute inset-0 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-20 w-20 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                  </div>
                )}
                {photos.length > 1 && (
                  <>
                    <button
                      onClick={() => setActivePhoto((p) => Math.max(0, p - 1))}
                      disabled={activePhoto === 0}
                      className="absolute left-4 top-1/2 -translate-y-1/2 h-8 w-8 rounded-full border border-gray-200 bg-white hover:bg-gray-50 flex items-center justify-center disabled:opacity-50"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m12 19-7-7 7-7" /><path d="M19 12H5" /></svg>
                    </button>
                    <button
                      onClick={() => setActivePhoto((p) => Math.min(photos.length - 1, p + 1))}
                      disabled={activePhoto === photos.length - 1}
                      className="absolute right-4 top-1/2 -translate-y-1/2 h-8 w-8 rounded-full border border-gray-200 bg-white hover:bg-gray-50 flex items-center justify-center disabled:opacity-50"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M5 12h14" /><path d="m12 5 7 7-7 7" /></svg>
                    </button>
                  </>
                )}
              </div>
            </div>

            {/* Capacity */}
            <div className="border-b pb-8 mb-8">
              <div className="grid grid-cols-3 gap-4 mb-6">
                <div>
                  <div className="text-lg font-semibold">{accommodation.maxGuests ?? 0} {t('detail.guests')}</div>
                  <div className="text-gray-500">{t('detail.capacity')}</div>
                </div>
                <div>
                  <div className="text-lg font-semibold">{accommodation.bedrooms ?? 0} {t('detail.bedrooms')}</div>
                  <div className="text-gray-500">{t('detail.withBathroom')}</div>
                </div>
                <div>
                  <div className="text-lg font-semibold">{accommodation.bathrooms ?? 0} {t('detail.bathrooms')}</div>
                  <div className="text-gray-500">{t('detail.private')}</div>
                </div>
              </div>

              {/* Check-in / Check-out */}
              <div className="flex items-center gap-8 mb-6">
                <div className="flex items-center gap-2">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-gray-400"><circle cx="12" cy="12" r="10" /><path d="M12 6v6l4 2" /></svg>
                  <div><span className="font-medium">{t('detail.checkIn')} :</span><span className="ml-1">16:00</span></div>
                </div>
                <div className="flex items-center gap-2">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-gray-400"><circle cx="12" cy="12" r="10" /><path d="M12 6v6l4 2" /></svg>
                  <div><span className="font-medium">{t('detail.checkOut')} :</span><span className="ml-1">12:00</span></div>
                </div>
              </div>

              {/* Description */}
              <p className="text-lg whitespace-pre-line">{accommodation.description}</p>
            </div>

            {/* Amenities */}
            {accommodation.amenities && accommodation.amenities.length > 0 && (
              <div className="border-b pb-8 mb-8">
                <h2 className="text-2xl font-semibold mb-6">{t('detail.amenitiesTitle')}</h2>
                <div className="grid grid-cols-2 gap-4">
                  {accommodation.amenities.map((code) => (
                    <div key={code} className="flex items-center gap-3">
                      <div className="w-2 h-2 rounded-full bg-blue-600" />
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
              </div>
            )}
          </div>

          {/* Right column - Booking card */}
          <div className="lg:col-span-4">
            <div className="rounded-2xl border border-gray-100 bg-white shadow-lg p-6 sticky top-24">
              {/* Price + rating */}
              <div className="flex items-center justify-between mb-6">
                <div className="text-2xl font-bold">
                  {accommodation.price}{'\u00A0'}€ <span className="text-base font-normal">/ {t('detail.night')}</span>
                </div>
              </div>

              {/* Dates */}
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium mb-2">{t('detail.datesLabel')}</label>
                  <button className="inline-flex items-center w-full rounded-xl text-sm border border-gray-200 bg-gray-50 hover:bg-white h-11 px-4 text-left font-normal text-gray-500 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="mr-2 text-gray-400">
                      <path d="M8 2v4" /><path d="M16 2v4" /><rect width="18" height="18" x="3" y="4" rx="2" /><path d="M3 10h18" />
                    </svg>
                    <span>{t('detail.selectDates')}</span>
                  </button>
                </div>

                {/* Guests */}
                <div>
                  <label className="block text-sm font-medium mb-2">{t('detail.guestsLabel')}</label>
                  <div className="relative">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                      <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                      <circle cx="9" cy="7" r="4" />
                    </svg>
                    <input
                      type="number"
                      min={1}
                      max={accommodation.maxGuests ?? 99}
                      defaultValue={1}
                      className="w-full h-11 rounded-xl border border-gray-200 bg-gray-50 pl-10 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 focus:bg-white transition-all"
                    />
                  </div>
                </div>

                {/* Reserve button */}
                <button className="w-full inline-flex items-center justify-center h-11 rounded-xl px-8 text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg shadow-blue-500/25 hover:shadow-blue-500/40">
                  {t('detail.reserve')}
                </button>
                <p className="text-center text-sm text-gray-500">{t('detail.noCharge')}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  );
};

export default AccommodationDetailPage;
