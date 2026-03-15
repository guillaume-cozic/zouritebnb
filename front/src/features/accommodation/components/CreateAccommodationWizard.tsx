import React from 'react';
import { useAppSelector } from '../../../store/hooks';
import { selectWizardStep } from '../AccommodationSelectors';
import StepIndicator from './StepIndicator';
import DescriptionStep from './DescriptionStep';
import AddressStep from './AddressStep';
import PhotosStep from './PhotosStep';
import SuccessStep from './SuccessStep';

const stepComponents: Record<string, React.FC> = {
  description: DescriptionStep,
  address: AddressStep,
  photos: PhotosStep,
  success: SuccessStep,
};

function CreateAccommodationWizard() {
  const currentStep = useAppSelector(selectWizardStep);
  const StepComponent = stepComponents[currentStep];

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-50 via-white to-purple-50">
      {/* Header */}
      <div className="border-b border-gray-100 bg-white/80 backdrop-blur-sm sticky top-0 z-10">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 py-4 flex items-center gap-3">
          <div className="w-9 h-9 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center shadow-md shadow-purple-200">
            <svg className="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={1.5}>
              <path strokeLinecap="round" strokeLinejoin="round" d="M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5V10.75M2.25 21h1.5m18 0h-18M2.25 9l4.5-1.636M18.75 3l-1.5.545m0 6.205l3 1m1.5.5l-1.5-.5M6.75 7.364V3h-3v18m3-13.636l10.5-3.819" />
            </svg>
          </div>
          <div>
            <h1 className="text-lg font-bold text-gray-900">Nouvel hébergement</h1>
            <p className="text-xs text-gray-400">Créez votre annonce en quelques étapes</p>
          </div>
        </div>
      </div>

      {/* Content */}
      <div className="max-w-4xl mx-auto px-4 sm:px-6 py-10">
        <StepIndicator />

        <div className="bg-white rounded-2xl shadow-sm shadow-gray-200/50 border border-gray-100 p-6 sm:p-8">
          <StepComponent />
        </div>
      </div>
    </div>
  );
}

export default CreateAccommodationWizard;
