import React from 'react';
import { useTranslation } from 'react-i18next';

interface WizardNavigationProps {
  onBack?: () => void;
  onSkip?: () => void;
  onClick?: () => void;
  skipLabel?: string;
  submitLabel?: string;
  isLoading?: boolean;
  isSubmit?: boolean;
}

function WizardNavigation({
  onBack,
  onSkip,
  onClick,
  skipLabel,
  submitLabel,
  isLoading = false,
  isSubmit = true,
}: WizardNavigationProps) {
  const { t } = useTranslation();
  const resolvedSkipLabel = skipLabel ?? t('wizard.skip');
  const resolvedSubmitLabel = submitLabel ?? t('wizard.continue');

  return (
    <div className="flex items-center gap-3 pt-2">
      {onBack && (
        <button
          type="button"
          onClick={onBack}
          className="flex items-center gap-1.5 py-3.5 px-5 rounded-xl text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-300 transition-all duration-200"
        >
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={2}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
          </svg>
          {t('wizard.previous')}
        </button>
      )}

      <div className="flex-1" />

      {onSkip && (
        <button
          type="button"
          onClick={onSkip}
          className="py-3.5 px-5 rounded-xl text-sm font-semibold text-gray-500 hover:text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-300 transition-all duration-200"
        >
          {resolvedSkipLabel}
        </button>
      )}

      <button
        type={isSubmit ? 'submit' : 'button'}
        disabled={isLoading}
        onClick={onClick}
        className="flex items-center gap-2 py-3.5 px-8 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-primary-500 to-primary-600 hover:from-primary-600 hover:to-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-primary-200 hover:shadow-xl hover:shadow-primary-300 transform hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200"
      >
        {isLoading ? (
          <>
            <svg className="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
              <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
              <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            {t('wizard.saving')}
          </>
        ) : (
          <>
            {resolvedSubmitLabel}
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={2}>
              <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
            </svg>
          </>
        )}
      </button>
    </div>
  );
}

export default WizardNavigation;
