import React, { useCallback } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { setLocation } from '../AccommodationSlice';
import {
  selectCurrentAccommodation,
  selectAccommodationStatus,
  selectAccommodationError,
} from '../AccommodationSelectors';
import MapSelector from '../../../components/MapSelector';

const optionalCoord = (min: number, max: number) =>
  z.preprocess(
    (val) => (val === '' || val === undefined || val === null || Number.isNaN(Number(val)) ? undefined : Number(val)),
    z.number().min(min).max(max).optional()
  );

const schema = z.object({
  street: z.string().min(1, 'La rue est obligatoire'),
  city: z.string().min(1, 'La ville est obligatoire'),
  zipCode: z.string().optional().default(''),
  country: z.string().min(1, 'Le pays est obligatoire'),
  latitude: optionalCoord(-90, 90),
  longitude: optionalCoord(-180, 180),
});

type FormData = z.infer<typeof schema>;

function AddressStep() {
  const dispatch = useAppDispatch();
  const accommodation = useAppSelector(selectCurrentAccommodation);
  const status = useAppSelector(selectAccommodationStatus);
  const apiError = useAppSelector(selectAccommodationError);
  const isLoading = status === 'loading';

  const {
    register,
    handleSubmit,
    setValue,
    watch,
    formState: { errors },
  } = useForm<FormData>({
    resolver: zodResolver(schema),
  });

  const lat = watch('latitude');
  const lng = watch('longitude');

  const handleMapSelect = useCallback(
    (newLat: number, newLng: number) => {
      setValue('latitude', newLat, { shouldValidate: true });
      setValue('longitude', newLng, { shouldValidate: true });
    },
    [setValue]
  );

  const onSubmit = (data: FormData) => {
    if (!accommodation?.id) return;
    dispatch(
      setLocation({
        id: accommodation.id,
        street: data.street,
        city: data.city,
        zipCode: data.zipCode,
        country: data.country,
        latitude: data.latitude,
        longitude: data.longitude,
      })
    );
  };

  const inputClass = (hasError: boolean) =>
    `block w-full rounded-xl border-0 bg-gray-50 px-4 py-3.5 text-gray-900 ring-1 ring-inset placeholder:text-gray-400 focus:bg-white focus:ring-2 transition-all duration-200 outline-none ${
      hasError ? 'ring-red-300 focus:ring-red-500' : 'ring-gray-200 focus:ring-purple-500'
    }`;

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-8">
      <p className="text-sm text-gray-500">
        Indiquez l'adresse de votre bien pour que les voyageurs puissent le localiser.
      </p>

      {/* Address */}
      <div className="space-y-5">
        <div className="flex items-center gap-2 text-gray-700">
          <svg className="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={1.5}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
          </svg>
          <h3 className="text-base font-semibold">Adresse</h3>
        </div>

        <div>
          <label htmlFor="street" className="block text-sm font-semibold text-gray-700 mb-1.5">
            Rue
          </label>
          <input
            id="street"
            type="text"
            {...register('street')}
            placeholder="12 rue de la Paix"
            className={inputClass(!!errors.street)}
          />
          {errors.street && (
            <p className="mt-1.5 text-sm text-red-600">{errors.street.message}</p>
          )}
        </div>

        <div className="grid grid-cols-2 gap-4">
          <div>
            <label htmlFor="city" className="block text-sm font-semibold text-gray-700 mb-1.5">
              Ville
            </label>
            <input
              id="city"
              type="text"
              {...register('city')}
              placeholder="Paris"
              className={inputClass(!!errors.city)}
            />
            {errors.city && (
              <p className="mt-1.5 text-sm text-red-600">{errors.city.message}</p>
            )}
          </div>
          <div>
            <label htmlFor="zipCode" className="block text-sm font-semibold text-gray-700 mb-1.5">
              Code postal
            </label>
            <input
              id="zipCode"
              type="text"
              {...register('zipCode')}
              placeholder="75002"
              className={inputClass(!!errors.zipCode)}
            />
            {errors.zipCode && (
              <p className="mt-1.5 text-sm text-red-600">{errors.zipCode.message}</p>
            )}
          </div>
        </div>

        <div>
          <label htmlFor="country" className="block text-sm font-semibold text-gray-700 mb-1.5">
            Pays
          </label>
          <input
            id="country"
            type="text"
            {...register('country')}
            placeholder="France"
            className={inputClass(!!errors.country)}
          />
          {errors.country && (
            <p className="mt-1.5 text-sm text-red-600">{errors.country.message}</p>
          )}
        </div>
      </div>

      {/* Map + Geolocation */}
      <div className="space-y-5">
        <div className="flex items-center gap-2 text-gray-700">
          <svg className="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={1.5}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
          </svg>
          <h3 className="text-base font-semibold">Localisation sur la carte</h3>
          <span className="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">Optionnel</span>
        </div>

        <p className="text-sm text-gray-500">
          Cliquez sur la carte pour positionner votre hébergement. Les coordonnées se mettront à jour automatiquement.
        </p>

        <MapSelector
          latitude={lat}
          longitude={lng}
          onSelect={handleMapSelect}
        />

        {/* Privacy info */}
        <div className="flex items-start gap-3 rounded-xl bg-amber-50 border border-amber-100 p-4">
          <svg className="w-5 h-5 text-amber-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={1.5}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
          </svg>
          <div>
            <p className="text-sm font-semibold text-amber-800">Confidentialité de la localisation</p>
            <p className="text-xs text-amber-700 mt-1">
              Les coordonnées GPS exactes ne sont pas communiquées aux voyageurs qui n'ont pas de réservation confirmée.
              Sur la carte publique, votre hébergement sera affiché dans un rayon approximatif pour protéger votre vie privée.
            </p>
          </div>
        </div>

        {/* Coordinate inputs */}
        <div className="grid grid-cols-2 gap-4">
          <div>
            <label htmlFor="latitude" className="block text-sm font-semibold text-gray-700 mb-1.5">
              Latitude
            </label>
            <input
              id="latitude"
              type="number"
              step="any"
              {...register('latitude')}
              placeholder="48.8566"
              className={inputClass(!!errors.latitude)}
            />
            {errors.latitude && (
              <p className="mt-1.5 text-sm text-red-600">{errors.latitude.message}</p>
            )}
          </div>
          <div>
            <label htmlFor="longitude" className="block text-sm font-semibold text-gray-700 mb-1.5">
              Longitude
            </label>
            <input
              id="longitude"
              type="number"
              step="any"
              {...register('longitude')}
              placeholder="2.3522"
              className={inputClass(!!errors.longitude)}
            />
            {errors.longitude && (
              <p className="mt-1.5 text-sm text-red-600">{errors.longitude.message}</p>
            )}
          </div>
        </div>
      </div>

      {apiError && (
        <div className="flex items-center gap-3 rounded-xl bg-red-50 border border-red-100 p-4">
          <svg className="w-5 h-5 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clipRule="evenodd" />
          </svg>
          <p className="text-sm text-red-700">{apiError}</p>
        </div>
      )}

      <button
        type="submit"
        disabled={isLoading}
        className="w-full py-4 px-6 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-violet-500 to-purple-600 hover:from-violet-600 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-purple-200 hover:shadow-xl hover:shadow-purple-300 transform hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200"
      >
        {isLoading ? (
          <span className="flex items-center justify-center gap-2">
            <svg className="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
              <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
              <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            Enregistrement...
          </span>
        ) : (
          <span className="flex items-center justify-center gap-2">
            Continuer
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={2}>
              <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
            </svg>
          </span>
        )}
      </button>
    </form>
  );
}

export default AddressStep;
