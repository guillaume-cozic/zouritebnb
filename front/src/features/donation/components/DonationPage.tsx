import React, { useEffect, useMemo, useState } from 'react';
import { Link, useParams, useSearchParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { Elements, PaymentElement, useElements, useStripe } from '@stripe/react-stripe-js';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import stripePromise from '../../../services/stripe';
import { fetchSolidarityProjectById } from '../../solidarityProject/SolidarityProjectSlice';
import {
  selectCurrentSolidarityProject,
  selectCurrentSolidarityProjectStatus,
} from '../../solidarityProject/SolidarityProjectSelectors';
import { SolidarityProject } from '../../solidarityProject/SolidarityProjectTypes';
import { createDonationIntent, resetDonationStatus } from '../DonationSlice';
import Footer from '../../../components/Footer';

const MIN_CENTS = 100;
const MAX_CENTS = 1_000_000;
const PRESET_EUROS = [10, 25, 50, 100];

const formatEuros = (cents: number, locale: string): string =>
  `${(cents / 100).toLocaleString(locale, { maximumFractionDigits: 2 })} €`;

interface AmountStepProps {
  amountCents: number | null;
  onSelect: (cents: number | null) => void;
  onContinue: () => void;
}

const AmountStep: React.FC<AmountStepProps> = ({ amountCents, onSelect, onContinue }) => {
  const { t, i18n } = useTranslation();
  const [custom, setCustom] = useState('');

  const presetActive = (euros: number) =>
    custom === '' && amountCents === euros * 100;

  const handlePreset = (euros: number) => {
    setCustom('');
    onSelect(euros * 100);
  };

  const handleCustom = (raw: string) => {
    setCustom(raw);
    const euros = Number(raw.replace(',', '.'));
    onSelect(Number.isFinite(euros) && euros > 0 ? Math.round(euros * 100) : null);
  };

  const valid =
    amountCents !== null && amountCents >= MIN_CENTS && amountCents <= MAX_CENTS;

  return (
    <div>
      <fieldset>
        <legend className="text-sm font-semibold text-gray-900">
          {t('donation.amountLegend')}
        </legend>
        <div className="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-3">
          {PRESET_EUROS.map((euros) => (
            <button
              key={euros}
              type="button"
              onClick={() => handlePreset(euros)}
              aria-pressed={presetActive(euros)}
              className={`h-14 rounded-xl border text-lg font-bold transition-all ${
                presetActive(euros)
                  ? 'border-primary-600 bg-primary-50 text-primary-700 ring-2 ring-primary-200'
                  : 'border-gray-200 bg-white text-gray-700 hover:border-primary-300 hover:bg-primary-50/50'
              }`}
            >
              {euros} €
            </button>
          ))}
        </div>
        <label className="block mt-4">
          <span className="text-sm font-medium text-gray-700">
            {t('donation.customLabel')}
          </span>
          <div className="relative mt-1.5">
            <input
              type="number"
              inputMode="decimal"
              min={1}
              max={10000}
              step="1"
              value={custom}
              onChange={(e) => handleCustom(e.target.value)}
              placeholder={t('donation.customPlaceholder')}
              className="w-full h-12 rounded-xl border border-gray-200 px-4 pr-10 text-base focus:border-primary-400 focus:ring-2 focus:ring-primary-100 outline-none"
            />
            <span className="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 font-semibold">
              €
            </span>
          </div>
        </label>
        <p className="mt-2 text-xs text-gray-500">{t('donation.amountHint')}</p>
      </fieldset>

      <button
        type="button"
        disabled={!valid}
        onClick={onContinue}
        className="mt-6 w-full inline-flex items-center justify-center gap-2 rounded-xl text-sm font-semibold text-white bg-primary-600 hover:bg-primary-700 h-12 px-7 transition-all disabled:opacity-40 disabled:cursor-not-allowed"
      >
        {valid
          ? t('donation.continueWithAmount', {
              amount: formatEuros(amountCents!, i18n.language),
            })
          : t('donation.continue')}
      </button>
    </div>
  );
};

interface PaymentStepProps {
  project: SolidarityProject;
  amountCents: number;
  onBack: () => void;
  onSuccess: () => void;
}

const PaymentStep: React.FC<PaymentStepProps> = ({
  project,
  amountCents,
  onBack,
  onSuccess,
}) => {
  const { t, i18n } = useTranslation();
  const dispatch = useAppDispatch();
  const stripe = useStripe();
  const elements = useElements();
  const [submitting, setSubmitting] = useState(false);
  const [paymentError, setPaymentError] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!stripe || !elements) return;

    setPaymentError(null);
    setSubmitting(true);

    const { error: submitError } = await elements.submit();
    if (submitError) {
      setPaymentError(submitError.message ?? 'Validation error');
      setSubmitting(false);
      return;
    }

    const intentResult = await dispatch(
      createDonationIntent({ solidarityProjectId: project.id, amountCents })
    );

    if (!createDonationIntent.fulfilled.match(intentResult)) {
      setPaymentError(
        (intentResult.payload as string) || 'Donation intent creation failed'
      );
      setSubmitting(false);
      return;
    }

    const confirmResult = await stripe.confirmPayment({
      elements,
      clientSecret: intentResult.payload.clientSecret,
      confirmParams: {
        return_url: `${window.location.origin}/solidarity-projects/${project.id}/donate`,
      },
      redirect: 'if_required',
    });

    if (confirmResult.error) {
      setPaymentError(confirmResult.error.message ?? 'Payment confirmation failed');
      setSubmitting(false);
      return;
    }

    setSubmitting(false);
    onSuccess();
  };

  // Dev-only bypass: `import.meta.env.DEV` is a build-time constant, so this
  // handler and its button are dead-code-eliminated from production bundles.
  // It only short-circuits Stripe on the client for local UI work.
  const handleDevAccepted = () => {
    onSuccess();
  };

  return (
    <form onSubmit={handleSubmit}>
      <div className="flex items-center justify-between gap-4 mb-4">
        <p className="text-sm text-gray-600">
          {t('donation.paymentFor', {
            amount: formatEuros(amountCents, i18n.language),
          })}
        </p>
        <button
          type="button"
          onClick={onBack}
          className="text-sm font-semibold text-primary-600 hover:text-primary-700 whitespace-nowrap"
        >
          {t('donation.changeAmount')}
        </button>
      </div>

      <PaymentElement />

      {paymentError && (
        <div
          role="alert"
          className="mt-4 rounded-xl bg-danger-50 border border-danger-200 px-4 py-3 text-sm text-danger-700"
        >
          {paymentError}
        </div>
      )}

      <button
        type="submit"
        disabled={!stripe || !elements || submitting}
        className="mt-6 w-full inline-flex items-center justify-center gap-2 rounded-xl text-sm font-semibold text-white bg-primary-600 hover:bg-primary-700 h-12 px-7 transition-all disabled:opacity-40 disabled:cursor-not-allowed"
      >
        {submitting
          ? t('donation.processing')
          : t('donation.payLabel', {
              amount: formatEuros(amountCents, i18n.language),
            })}
      </button>

      <p className="mt-3 text-xs text-gray-400 text-center">
        {t('donation.secure')}
      </p>

      {import.meta.env.DEV && (
        <button
          type="button"
          onClick={handleDevAccepted}
          className="mt-4 w-full h-10 rounded-xl border border-dashed border-gray-300 text-xs text-gray-500 hover:bg-gray-50"
        >
          {t('donation.devAccepted')}
        </button>
      )}
    </form>
  );
};

interface SuccessStepProps {
  project: SolidarityProject;
  amountCents: number | null;
}

const SuccessStep: React.FC<SuccessStepProps> = ({ project, amountCents }) => {
  const { t, i18n } = useTranslation();

  return (
    <div className="text-center py-4">
      <span className="inline-flex w-16 h-16 rounded-full bg-success-100 text-success-600 items-center justify-center">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          width="32"
          height="32"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          strokeWidth="2.5"
          strokeLinecap="round"
          strokeLinejoin="round"
        >
          <path d="M20 6 9 17l-5-5" />
        </svg>
      </span>
      <h2 className="mt-5 text-2xl font-bold text-gray-900">
        {t('donation.success.title')}
      </h2>
      <p className="mt-2 text-gray-600">
        {amountCents
          ? t('donation.success.message', {
              amount: formatEuros(amountCents, i18n.language),
              project: project.title,
            })
          : t('donation.success.messageNoAmount', { project: project.title })}
      </p>
      <div className="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
        <Link
          to={`/solidarity-projects/${project.id}`}
          className="inline-flex items-center justify-center rounded-xl text-sm font-semibold text-white bg-primary-600 hover:bg-primary-700 h-11 px-6 transition-all"
        >
          {t('donation.success.backToProject')}
        </Link>
        <Link
          to="/solidarity-projects"
          className="inline-flex items-center justify-center rounded-xl text-sm font-semibold text-primary-700 bg-primary-50 hover:bg-primary-100 h-11 px-6 transition-all"
        >
          {t('donation.success.allProjects')}
        </Link>
      </div>
    </div>
  );
};

const DonationPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const [searchParams] = useSearchParams();
  const { t, i18n } = useTranslation();
  const dispatch = useAppDispatch();
  const project = useAppSelector(selectCurrentSolidarityProject);
  const projectStatus = useAppSelector(selectCurrentSolidarityProjectStatus);

  // Stripe redirect-based payment methods land back here with this param.
  const redirectedSuccess = searchParams.get('redirect_status') === 'succeeded';

  const [amountCents, setAmountCents] = useState<number | null>(null);
  const [step, setStep] = useState<'amount' | 'payment' | 'success'>(
    redirectedSuccess ? 'success' : 'amount'
  );

  useEffect(() => {
    if (id) {
      dispatch(fetchSolidarityProjectById(id));
    }
  }, [dispatch, id, i18n.language]);

  useEffect(() => {
    window.scrollTo({ top: 0, behavior: 'auto' });
    return () => {
      dispatch(resetDonationStatus());
    };
  }, [dispatch, id]);

  const elementsOptions = useMemo(
    () => ({
      mode: 'payment' as const,
      amount: amountCents ?? MIN_CENTS,
      currency: 'eur',
    }),
    [amountCents]
  );

  const loaded = projectStatus === 'succeeded' && project && project.id === id;
  const closed = loaded && project.status !== 'active';

  return (
    <div className="min-h-screen flex flex-col bg-gradient-to-b from-primary-50/30 via-white to-white -mt-16 pt-16">
      <div className="flex-1 w-full max-w-xl mx-auto px-4 sm:px-6 py-10">
        <Link
          to={id ? `/solidarity-projects/${id}` : '/solidarity-projects'}
          className="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-primary-700 mb-6"
        >
          <svg
            width="14"
            height="14"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2.5"
            strokeLinecap="round"
            strokeLinejoin="round"
          >
            <path d="m15 18-6-6 6-6" />
          </svg>
          {t('donation.back')}
        </Link>

        <header className="mb-8">
          <div className="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-primary-700">
            <span className="w-1.5 h-1.5 rounded-full bg-primary-500" />
            {t('donation.eyebrow')}
          </div>
          <h1 className="mt-2 text-3xl font-bold text-gray-900 tracking-tight">
            {t('donation.title')}
          </h1>
          {loaded && (
            <p className="text-gray-500 mt-1">
              {t('donation.subtitle', { project: project.title })}
            </p>
          )}
        </header>

        <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sm:p-8">
          {projectStatus === 'loading' && (
            <div className="animate-pulse space-y-4">
              <div className="h-5 bg-gray-200 rounded w-40" />
              <div className="h-14 bg-gray-100 rounded-xl" />
              <div className="h-12 bg-gray-100 rounded-xl" />
            </div>
          )}

          {projectStatus === 'failed' && (
            <p className="text-sm text-danger-600">{t('donation.loadError')}</p>
          )}

          {loaded && closed && step !== 'success' && (
            <p className="text-sm text-gray-600">{t('donation.closed')}</p>
          )}

          {loaded && !closed && step === 'amount' && (
            <AmountStep
              amountCents={amountCents}
              onSelect={setAmountCents}
              onContinue={() => setStep('payment')}
            />
          )}

          {loaded && !closed && step === 'payment' && amountCents !== null && (
            <Elements
              key={amountCents}
              stripe={stripePromise}
              options={elementsOptions}
            >
              <PaymentStep
                project={project}
                amountCents={amountCents}
                onBack={() => setStep('amount')}
                onSuccess={() => setStep('success')}
              />
            </Elements>
          )}

          {loaded && step === 'success' && (
            <SuccessStep project={project} amountCents={amountCents} />
          )}
        </div>
      </div>
      <Footer />
    </div>
  );
};

export default DonationPage;
