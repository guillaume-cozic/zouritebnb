import React from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { createAccommodation } from '../AccommodationSlice';
import { selectAccommodationStatus, selectAccommodationError } from '../AccommodationSelectors';

const schema = z.object({
  title: z
    .string()
    .min(3, 'Le titre doit contenir au moins 3 caractères')
    .max(100, 'Le titre ne peut pas dépasser 100 caractères'),
  description: z
    .string()
    .min(10, 'La description doit contenir au moins 10 caractères'),
  price: z
    .number({ invalid_type_error: 'Le prix est requis' })
    .positive('Le prix doit être supérieur à 0'),
});

type FormData = z.infer<typeof schema>;

function DescriptionStep() {
  const dispatch = useAppDispatch();
  const status = useAppSelector(selectAccommodationStatus);
  const apiError = useAppSelector(selectAccommodationError);
  const isLoading = status === 'loading';

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<FormData>({
    resolver: zodResolver(schema),
  });

  const onSubmit = (data: FormData) => {
    dispatch(createAccommodation(data));
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-8">
      <p className="text-sm text-gray-500">
        Commencez par donner envie aux voyageurs avec un titre accrocheur et une description de votre bien.
      </p>

      <div className="space-y-5">
        <div>
          <label htmlFor="title" className="block text-sm font-semibold text-gray-700 mb-1.5">
            Nom de l'hébergement
          </label>
          <input
            id="title"
            type="text"
            {...register('title')}
            placeholder="Ex : Villa avec vue mer, Loft design centre-ville..."
            className={`block w-full rounded-xl border-0 bg-gray-50 px-4 py-3.5 text-gray-900 ring-1 ring-inset placeholder:text-gray-400 focus:bg-white focus:ring-2 transition-all duration-200 outline-none ${
              errors.title ? 'ring-red-300 focus:ring-red-500' : 'ring-gray-200 focus:ring-purple-500'
            }`}
          />
          {errors.title && (
            <p className="mt-1.5 text-sm text-red-600">{errors.title.message}</p>
          )}
        </div>

        <div>
          <label htmlFor="description" className="block text-sm font-semibold text-gray-700 mb-1.5">
            Description
          </label>
          <textarea
            id="description"
            rows={5}
            {...register('description')}
            placeholder="Décrivez votre hébergement : type de logement, nombre de chambres, équipements, ambiance, points forts du quartier, commodités à proximité (restaurant, transports, commerces), accès (à pied, voiture nécessaire, parking)..."
            className={`block w-full rounded-xl border-0 bg-gray-50 px-4 py-3.5 text-gray-900 ring-1 ring-inset placeholder:text-gray-400 focus:bg-white focus:ring-2 transition-all duration-200 resize-none outline-none ${
              errors.description ? 'ring-red-300 focus:ring-red-500' : 'ring-gray-200 focus:ring-purple-500'
            }`}
          />
          {errors.description && (
            <p className="mt-1.5 text-sm text-red-600">{errors.description.message}</p>
          )}
          <div className="mt-2 flex items-start gap-2 rounded-lg bg-blue-50 border border-blue-100 px-3 py-2.5">
            <svg className="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clipRule="evenodd" />
            </svg>
            <p className="text-xs text-blue-700">
              Décrivez votre hébergement en détail : type de logement, équipements, accès, commodités à proximité. Une bonne description attire plus de voyageurs.
            </p>
          </div>
        </div>

        <div>
          <label htmlFor="price" className="block text-sm font-semibold text-gray-700 mb-1.5">
            Prix par nuit
          </label>
          <div className="relative">
            <input
              id="price"
              type="number"
              step="0.01"
              {...register('price', { valueAsNumber: true })}
              placeholder="0.00"
              className={`block w-full rounded-xl border-0 bg-gray-50 pl-4 pr-20 py-3.5 text-gray-900 ring-1 ring-inset placeholder:text-gray-400 focus:bg-white focus:ring-2 transition-all duration-200 outline-none ${
                errors.price ? 'ring-red-300 focus:ring-red-500' : 'ring-gray-200 focus:ring-purple-500'
              }`}
            />
            <div className="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
              <span className="text-gray-400 font-medium text-sm">EUR / nuit</span>
            </div>
          </div>
          {errors.price && (
            <p className="mt-1.5 text-sm text-red-600">{errors.price.message}</p>
          )}
          <div className="mt-2 flex items-start gap-2 rounded-lg bg-blue-50 border border-blue-100 px-3 py-2.5">
            <svg className="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clipRule="evenodd" />
            </svg>
            <p className="text-xs text-blue-700">
              Il s'agit du prix par défaut. Vous pourrez le modifier ultérieurement et définir des tarifs saisonniers.
            </p>
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
            Création en cours...
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

export default DescriptionStep;
