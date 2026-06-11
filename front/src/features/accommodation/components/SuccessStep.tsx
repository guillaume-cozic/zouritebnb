import React from 'react';
import { useTranslation } from 'react-i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { resetWizard } from '../AccommodationSlice';
import { selectCurrentAccommodation } from '../AccommodationSelectors';

function SuccessStep() {
  const { t } = useTranslation();
  const dispatch = useAppDispatch();
  const accommodation = useAppSelector(selectCurrentAccommodation);

  return (
    <div className="text-center py-6">
      {/* Animated check */}
      <div className="relative mx-auto w-20 h-20 mb-8">
        <div className="absolute inset-0 rounded-full bg-emerald-100 animate-ping opacity-25" />
        <div className="relative w-20 h-20 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center shadow-lg shadow-emerald-200">
          <svg className="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={2.5}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
          </svg>
        </div>
      </div>

      <h2 className="text-2xl font-bold text-gray-900 mb-2">
        {t('successStep.title')}
      </h2>
      <p className="text-sm text-gray-500 mb-8">
        {t('successStep.subtitle')}
      </p>

      {accommodation && (
        <div className="bg-gradient-to-br from-gray-50 to-white rounded-2xl border border-gray-100 p-6 text-left max-w-sm mx-auto shadow-sm">
          <div className="space-y-4">
            <div>
              <p className="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">{t('successStep.titleLabel')}</p>
              <p className="text-base font-semibold text-gray-900">{accommodation.title}</p>
            </div>
            <div className="flex items-baseline gap-1">
              <p className="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1 w-full">{t('successStep.priceLabel')}</p>
            </div>
            <div className="flex items-baseline gap-1 -mt-3">
              <span className="text-2xl font-bold text-primary-600">{accommodation.price}</span>
              <span className="text-sm text-gray-400">{t('successStep.priceUnit')}</span>
            </div>
            {accommodation.city && (
              <div>
                <p className="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">{t('successStep.addressLabel')}</p>
                <p className="text-sm text-gray-700">
                  {accommodation.street}<br />
                  {accommodation.zipCode} {accommodation.city}, {accommodation.country}
                </p>
              </div>
            )}
            <div>
              <p className="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">{t('successStep.statusLabel')}</p>
              <span className="inline-flex items-center gap-1.5 text-xs font-medium bg-amber-50 text-amber-700 px-2.5 py-1 rounded-full border border-amber-100">
                <span className="w-1.5 h-1.5 rounded-full bg-amber-400" />
                {t('successStep.draft')}
              </span>
            </div>
          </div>
        </div>
      )}

      <button
        type="button"
        onClick={() => dispatch(resetWizard())}
        className="
          mt-10 inline-flex items-center gap-2 py-3.5 px-8 rounded-xl text-sm font-semibold text-white
          bg-gradient-to-r from-primary-500 to-primary-600
          hover:from-primary-600 hover:to-primary-700
          focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500
          shadow-lg shadow-primary-200 hover:shadow-xl hover:shadow-primary-300
          transform hover:-translate-y-0.5 active:translate-y-0
          transition-all duration-200
        "
      >
        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={2}>
          <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        {t('successStep.createAnother')}
      </button>
    </div>
  );
}

export default SuccessStep;
