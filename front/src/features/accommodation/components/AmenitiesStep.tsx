import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import WizardNavigation from '../../../components/WizardNavigation';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { setAmenities, goToStep, saveDraft } from '../AccommodationSlice';
import {
  selectCurrentAccommodation,
  selectAccommodationStatus,
  selectAccommodationError,
  selectFormDrafts,
} from '../AccommodationSelectors';
import { AMENITY_CATEGORIES } from '../AmenityData';

function AmenitiesStep() {
  const { t } = useTranslation();
  const dispatch = useAppDispatch();
  const accommodation = useAppSelector(selectCurrentAccommodation);
  const status = useAppSelector(selectAccommodationStatus);
  const apiError = useAppSelector(selectAccommodationError);
  const drafts = useAppSelector(selectFormDrafts);
  const isLoading = status === 'loading';

  const initialCodes = drafts.amenities ?? accommodation?.amenities ?? [];
  const [selectedCodes, setSelectedCodes] = useState<Set<string>>(new Set(initialCodes));

  const toggle = (code: string) => {
    setSelectedCodes((prev) => {
      const next = new Set(prev);
      if (next.has(code)) {
        next.delete(code);
      } else {
        next.add(code);
      }
      return next;
    });
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!accommodation?.id) return;
    dispatch(setAmenities({ id: accommodation.id, codes: Array.from(selectedCodes) }));
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-8">
      <div className="flex items-center justify-between">
        <p className="text-sm text-gray-500">
          {t('amenitiesStep.intro')}
        </p>
        <span className="text-sm font-semibold text-blue-600 whitespace-nowrap ml-4">
          {t('amenitiesStep.selected', { count: selectedCodes.size })}
        </span>
      </div>

      <div className="space-y-6">
        {AMENITY_CATEGORIES.map((category) => (
          <div key={category.key}>
            <h3 className="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
              <span className="w-1.5 h-1.5 rounded-full bg-blue-400" />
              {t('amenityCategories.' + category.key)}
            </h3>
            <div className="flex flex-wrap gap-2">
              {category.items.map((item) => {
                const isSelected = selectedCodes.has(item.code);
                return (
                  <button
                    key={item.code}
                    type="button"
                    onClick={() => toggle(item.code)}
                    className={`
                      inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-sm font-medium
                      transition-all duration-200 border
                      ${
                        isSelected
                          ? 'bg-gradient-to-r from-blue-500 to-blue-600 text-white border-transparent shadow-md shadow-blue-200'
                          : 'bg-gray-100 text-gray-700 border-gray-200 hover:border-blue-300 hover:bg-blue-50'
                      }
                    `}
                  >
                    {isSelected && (
                      <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={2.5}>
                        <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                      </svg>
                    )}
                    {t('amenities.' + item.code)}
                  </button>
                );
              })}
            </div>
          </div>
        ))}
      </div>

      {apiError && (
        <div className="flex items-center gap-3 rounded-xl bg-red-50 border border-red-100 p-4">
          <svg className="w-5 h-5 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clipRule="evenodd" />
          </svg>
          <p className="text-sm text-red-700">{apiError}</p>
        </div>
      )}

      <WizardNavigation
        onBack={() => { dispatch(saveDraft({ amenities: Array.from(selectedCodes) })); dispatch(goToStep('capacity')); }}
        onSkip={() => { dispatch(saveDraft({ amenities: Array.from(selectedCodes) })); dispatch(goToStep('address')); }}
        isLoading={isLoading}
      />
    </form>
  );
}

export default AmenitiesStep;
