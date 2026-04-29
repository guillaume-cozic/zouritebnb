import React from 'react';
import { useTranslation } from 'react-i18next';
import { TFunction } from 'i18next';
import WizardNavigation from '../../../components/WizardNavigation';
import { useForm, useWatch, Control } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { setCapacity, wizardStepLeft } from '../AccommodationSlice';
import {
  selectCurrentAccommodation,
  selectAccommodationStatus,
  selectAccommodationError,
  selectFormDrafts,
} from '../AccommodationSelectors';

const getNonNegativeInt = (t: TFunction, labelKey: string) =>
  z
    .number({ invalid_type_error: t('capacityStep.required', { label: t(labelKey) }) })
    .int(t('capacityStep.integer', { label: t(labelKey) }))
    .min(0, t('capacityStep.min', { label: t(labelKey) }));

const getSchema = (t: TFunction) => z.object({
  bedrooms: getNonNegativeInt(t, 'capacityStep.bedroomsLabel'),
  bathrooms: getNonNegativeInt(t, 'capacityStep.bathroomsLabel'),
  maxGuests: getNonNegativeInt(t, 'capacityStep.maxGuestsLabel'),
  singleBeds: getNonNegativeInt(t, 'capacityStep.singleBedsLabel'),
  doubleBeds: getNonNegativeInt(t, 'capacityStep.doubleBedsLabel'),
});

type FormData = z.infer<ReturnType<typeof getSchema>>;

type FieldName = keyof FormData;

interface FieldConfig {
  name: FieldName;
  labelKey: string;
  icon: React.ReactNode;
}

const fields: FieldConfig[] = [
  {
    name: 'bedrooms',
    labelKey: 'capacityStep.bedrooms',
    icon: (
      <svg className="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={1.5}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
      </svg>
    ),
  },
  {
    name: 'bathrooms',
    labelKey: 'capacityStep.bathrooms',
    icon: (
      <svg className="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={1.5}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
      </svg>
    ),
  },
  {
    name: 'maxGuests',
    labelKey: 'capacityStep.maxGuests',
    icon: (
      <svg className="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={1.5}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
      </svg>
    ),
  },
  {
    name: 'singleBeds',
    labelKey: 'capacityStep.singleBeds',
    icon: (
      <svg className="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={1.5}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0H9m3 0h3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
      </svg>
    ),
  },
  {
    name: 'doubleBeds',
    labelKey: 'capacityStep.doubleBeds',
    icon: (
      <svg className="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={1.5}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
      </svg>
    ),
  },
];

function NumberStepper({
  name,
  labelKey,
  icon,
  control,
  error,
  setValue,
}: FieldConfig & {
  control: Control<FormData>;
  error?: string;
  setValue: (name: FieldName, value: number) => void;
}) {
  const { t } = useTranslation();
  const value = useWatch({ control, name }) as number | undefined;
  const current = typeof value === 'number' ? value : 0;

  const decrement = () => {
    if (current > 0) setValue(name, current - 1);
  };

  const increment = () => {
    setValue(name, current + 1);
  };

  return (
    <div
      className={`flex items-center justify-between rounded-xl bg-gray-50 px-4 py-3.5 ring-1 ring-inset transition-all duration-200 ${
        error ? 'ring-red-300' : 'ring-gray-200'
      }`}
    >
      <div className="flex items-center gap-3">
        {icon}
        <span className="text-sm font-semibold text-gray-700">{t(labelKey)}</span>
      </div>
      <div className="flex items-center gap-3">
        <button
          type="button"
          onClick={decrement}
          disabled={current <= 0}
          className="w-9 h-9 rounded-lg border border-gray-200 bg-white flex items-center justify-center text-gray-600 hover:border-blue-300 hover:text-blue-600 disabled:opacity-30 disabled:cursor-not-allowed transition-all duration-150"
        >
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={2}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 12h-15" />
          </svg>
        </button>
        <span className="w-8 text-center text-base font-semibold text-gray-900 tabular-nums">
          {current}
        </span>
        <button
          type="button"
          onClick={increment}
          className="w-9 h-9 rounded-lg border border-gray-200 bg-white flex items-center justify-center text-gray-600 hover:border-blue-300 hover:text-blue-600 transition-all duration-150"
        >
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={2}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
          </svg>
        </button>
      </div>
      {error && <p className="mt-1.5 text-sm text-red-600">{error}</p>}
    </div>
  );
}

function CapacityStep() {
  const { t } = useTranslation();
  const dispatch = useAppDispatch();
  const accommodation = useAppSelector(selectCurrentAccommodation);
  const status = useAppSelector(selectAccommodationStatus);
  const apiError = useAppSelector(selectAccommodationError);
  const drafts = useAppSelector(selectFormDrafts);
  const isLoading = status === 'loading';

  const saved = drafts.capacity || accommodation;

  const schema = getSchema(t);

  const {
    control,
    handleSubmit,
    setValue,
    getValues,
    formState: { errors },
  } = useForm<FormData>({
    resolver: zodResolver(schema),
    defaultValues: {
      bedrooms: saved?.bedrooms ?? 0,
      bathrooms: saved?.bathrooms ?? 0,
      maxGuests: saved?.maxGuests ?? 0,
      singleBeds: saved?.singleBeds ?? 0,
      doubleBeds: saved?.doubleBeds ?? 0,
    },
  });

  const saveAndGoBack = () => {
    dispatch(wizardStepLeft({ draft: { capacity: getValues() }, target: 'description' }));
  };

  const onSubmit = (data: FormData) => {
    if (!accommodation?.id) return;
    dispatch(
      setCapacity({
        id: accommodation.id,
        bedrooms: data.bedrooms,
        bathrooms: data.bathrooms,
        maxGuests: data.maxGuests,
        singleBeds: data.singleBeds,
        doubleBeds: data.doubleBeds,
      })
    );
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-8">
      <p className="text-sm text-gray-500">
        {t('capacityStep.intro')}
      </p>

      <div className="space-y-5">
        <div className="flex items-center gap-2 text-gray-700">
          <svg className="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={1.5}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
          </svg>
          <h3 className="text-base font-semibold">{t('capacityStep.sectionTitle')}</h3>
        </div>

        {fields.map((field) => (
          <NumberStepper
            key={field.name}
            {...field}
            control={control}
            error={errors[field.name]?.message}
            setValue={setValue}
          />
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
        onBack={saveAndGoBack}
        onSkip={() =>
          dispatch(wizardStepLeft({ draft: { capacity: getValues() }, target: 'amenities' }))
        }
        isLoading={isLoading}
      />
    </form>
  );
}

export default CapacityStep;
