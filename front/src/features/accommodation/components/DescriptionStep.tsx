import React from 'react';
import { useTranslation } from 'react-i18next';
import { TFunction } from 'i18next';
import WizardNavigation from '../../../components/WizardNavigation';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { createAccommodation } from '../AccommodationSlice';
import { selectAccommodationStatus, selectAccommodationError } from '../AccommodationSelectors';
import { PLATFORM_COMMISSION_RATE, SOLIDARITY_RATE } from '../../../constants/pricing';

const getSchema = (t: TFunction) => z.object({
  title: z
    .string()
    .min(3, t('descriptionStep.titleMinLength'))
    .max(100, t('descriptionStep.titleMaxLength')),
  description: z
    .string()
    .min(10, t('descriptionStep.descriptionMinLength')),
  price: z
    .number({ invalid_type_error: t('descriptionStep.priceRequired') })
    .positive(t('descriptionStep.pricePositive')),
});

type FormData = z.infer<ReturnType<typeof getSchema>>;

function DescriptionStep() {
  const { t } = useTranslation();
  const dispatch = useAppDispatch();
  const status = useAppSelector(selectAccommodationStatus);
  const apiError = useAppSelector(selectAccommodationError);
  const isLoading = status === 'loading';

  const schema = getSchema(t);

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
        {t('descriptionStep.intro')}
      </p>

      <div className="space-y-5">
        <div>
          <label htmlFor="title" className="block text-sm font-semibold text-gray-700 mb-1.5">
            {t('descriptionStep.titleLabel')}
          </label>
          <input
            id="title"
            type="text"
            {...register('title')}
            placeholder={t('descriptionStep.titlePlaceholder')}
            className={`block w-full rounded-xl border-0 bg-gray-50 px-4 py-3.5 text-gray-900 ring-1 ring-inset placeholder:text-gray-400 focus:bg-white focus:ring-2 transition-all duration-200 outline-none ${
              errors.title ? 'ring-red-300 focus:ring-red-500' : 'ring-gray-200 focus:ring-primary-500'
            }`}
          />
          {errors.title && (
            <p className="mt-1.5 text-sm text-red-600">{errors.title.message}</p>
          )}
        </div>

        <div>
          <label htmlFor="description" className="block text-sm font-semibold text-gray-700 mb-1.5">
            {t('descriptionStep.descriptionLabel')}
          </label>
          <textarea
            id="description"
            rows={10}
            {...register('description')}
            placeholder={t('descriptionStep.descriptionPlaceholder')}
            className={`block w-full rounded-xl border-0 bg-gray-50 px-4 py-3.5 text-gray-900 ring-1 ring-inset placeholder:text-gray-400 focus:bg-white focus:ring-2 transition-all duration-200 resize-none outline-none ${
              errors.description ? 'ring-red-300 focus:ring-red-500' : 'ring-gray-200 focus:ring-primary-500'
            }`}
          />
          {errors.description && (
            <p className="mt-1.5 text-sm text-red-600">{errors.description.message}</p>
          )}
          <div className="mt-2 flex items-start gap-2 rounded-lg bg-primary-50 border border-primary-100 px-3 py-2.5">
            <svg className="w-4 h-4 text-primary-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clipRule="evenodd" />
            </svg>
            <p className="text-xs text-primary-700">
              {t('descriptionStep.descriptionHint')}
            </p>
          </div>
        </div>

        <div>
          <label htmlFor="price" className="block text-sm font-semibold text-gray-700 mb-1.5">
            {t('descriptionStep.priceLabel')}
          </label>
          <div className="relative">
            <input
              id="price"
              type="number"
              step="0.01"
              {...register('price', { valueAsNumber: true })}
              placeholder="0.00"
              className={`block w-full rounded-xl border-0 bg-gray-50 pl-4 pr-20 py-3.5 text-gray-900 ring-1 ring-inset placeholder:text-gray-400 focus:bg-white focus:ring-2 transition-all duration-200 outline-none ${
                errors.price ? 'ring-red-300 focus:ring-red-500' : 'ring-gray-200 focus:ring-primary-500'
              }`}
            />
            <div className="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
              <span className="text-gray-400 font-medium text-sm">{t('descriptionStep.priceUnit')}</span>
            </div>
          </div>
          {errors.price && (
            <p className="mt-1.5 text-sm text-red-600">{errors.price.message}</p>
          )}
          <div className="mt-2 flex items-start gap-2 rounded-lg bg-primary-50 border border-primary-100 px-3 py-2.5">
            <svg className="w-4 h-4 text-primary-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clipRule="evenodd" />
            </svg>
            <p className="text-xs text-primary-700">
              {t('descriptionStep.priceHint')}
            </p>
          </div>
        </div>
      </div>

      <div className="rounded-xl border border-primary-100 bg-primary-50 p-4 sm:p-5">
        <div className="flex items-start gap-3">
          <svg className="w-5 h-5 text-primary-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clipRule="evenodd" />
          </svg>
          <div className="space-y-2">
            <p className="text-sm font-semibold text-primary-800">
              {t('descriptionStep.billingInfoTitle')}
            </p>
            <p className="text-xs text-primary-700">
              {t('descriptionStep.billingInfoHost')}
            </p>
            <ul className="space-y-1 text-xs text-primary-700 list-disc pl-4">
              <li>
                {t('descriptionStep.billingInfoCommission', {
                  rate: Math.round(PLATFORM_COMMISSION_RATE * 100),
                })}
              </li>
              <li>
                {t('descriptionStep.billingInfoDonation', {
                  rate: Math.round(SOLIDARITY_RATE * 100),
                })}
              </li>
            </ul>
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

      <WizardNavigation submitLabel={t('descriptionStep.submit')} isLoading={isLoading} />
    </form>
  );
}

export default DescriptionStep;
