import React, { useEffect, useMemo, useState } from 'react';
import { Link, Navigate, useNavigate, useParams, useSearchParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { Elements, PaymentElement, useElements, useStripe } from '@stripe/react-stripe-js';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { fetchAccommodation } from '../../accommodation/AccommodationSlice';
import { selectCurrentAccommodation } from '../../accommodation/AccommodationSelectors';
import { requestReservation, clearMutationError } from '../ReservationSlice';
import {
  selectReservationMutationError,
  selectReservationMutationStatus,
} from '../ReservationSelectors';
import { selectAuthUser } from '../../auth/AuthSelectors';
import { createPaymentIntent } from '../../payment/PaymentSlice';
import { Accommodation } from '../../accommodation/AccommodationTypes';
import { AuthUser } from '../../auth/AuthTypes';
import Footer from '../../../components/Footer';
import { accommodationPath } from '../../accommodation/accommodationUrl';
import stripePromise from '../../../services/stripe';

const PLATFORM_COMMISSION_RATE = 0.08;
const SOLIDARITY_RATE = 0.07;

const pad = (n: number) => String(n).padStart(2, '0');
const toApiDateTime = (date: Date, time: string): string => {
  const [hh, mm] = time.split(':');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${hh}:${mm}:00`;
};

const parseDate = (s: string | null): Date | null => {
  if (!s) return null;
  const d = new Date(s);
  return isNaN(d.getTime()) ? null : d;
};

interface BookingFormProps {
  accommodation: Accommodation;
  user: AuthUser;
  accommodationId: string;
  checkInDate: Date | null;
  checkOutDate: Date | null;
  guestsCount: number;
  nights: number;
  totalCents: number;
  missingDates: boolean;
  defaultGuestName: string;
  totalLabel: string;
}

const BookingForm: React.FC<BookingFormProps> = ({
  accommodation,
  user,
  accommodationId,
  checkInDate,
  checkOutDate,
  guestsCount,
  nights,
  totalCents,
  missingDates,
  defaultGuestName,
  totalLabel,
}) => {
  const { t } = useTranslation();
  const dispatch = useAppDispatch();
  const navigate = useNavigate();
  const stripe = useStripe();
  const elements = useElements();
  const mutationStatus = useAppSelector(selectReservationMutationStatus);
  const mutationError = useAppSelector(selectReservationMutationError);

  const [guestName, setGuestName] = useState(defaultGuestName);
  const [checkInTime, setCheckInTime] = useState('15:00');
  const [checkOutTime, setCheckOutTime] = useState('11:00');
  const [note, setNote] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [paymentError, setPaymentError] = useState<string | null>(null);

  useEffect(() => {
    if (defaultGuestName) setGuestName((prev) => prev || defaultGuestName);
  }, [defaultGuestName]);

  // Creates the reservation and, on success, navigates to the confirmation page.
  // `paymentIntentId` is optional: the real flow passes the authorized Stripe intent,
  // the dev bypass passes none.
  const submitReservation = async (paymentIntentId?: string) => {
    if (!user || !checkInDate || !checkOutDate || !guestName.trim()) return;

    const reservationResult = await dispatch(
      requestReservation({
        accommodationId,
        checkIn: toApiDateTime(checkInDate, checkInTime),
        checkOut: toApiDateTime(checkOutDate, checkOutTime),
        guestName: guestName.trim(),
        guestCount: guestsCount,
        note: note.trim() || undefined,
        paymentIntentId,
      })
    );

    if (requestReservation.fulfilled.match(reservationResult)) {
      navigate('/reservation-confirmed', {
        replace: true,
        state: {
          accommodationTitle: accommodation.title,
          accommodationCity: accommodation.city,
          checkIn: checkInDate.toISOString(),
          checkOut: checkOutDate.toISOString(),
          guests: guestsCount,
          totalLabel,
        },
      });
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!stripe || !elements) return;
    if (!user || !checkInDate || !checkOutDate || !guestName.trim()) return;
    if (totalCents <= 0) return;

    setPaymentError(null);
    setSubmitting(true);

    const { error: submitError } = await elements.submit();
    if (submitError) {
      setPaymentError(submitError.message ?? 'Validation error');
      setSubmitting(false);
      return;
    }

    const intentResult = await dispatch(
      createPaymentIntent({
        accommodationId,
        checkIn: toApiDateTime(checkInDate, checkInTime),
        checkOut: toApiDateTime(checkOutDate, checkOutTime),
      })
    );

    if (!createPaymentIntent.fulfilled.match(intentResult)) {
      setPaymentError((intentResult.payload as string) || 'Payment intent creation failed');
      setSubmitting(false);
      return;
    }

    const { paymentIntentId, clientSecret } = intentResult.payload;

    const confirmResult = await stripe.confirmPayment({
      elements,
      clientSecret,
      confirmParams: {
        return_url: window.location.origin + '/account/conversations',
      },
      redirect: 'if_required',
    });

    if (confirmResult.error) {
      setPaymentError(confirmResult.error.message ?? 'Payment confirmation failed');
      setSubmitting(false);
      return;
    }

    await submitReservation(paymentIntentId);
    setSubmitting(false);
  };

  // --- Dev-only payment bypass -------------------------------------------------
  // `import.meta.env.DEV` is a build-time constant (true under `vite`, false under
  // `vite build`). These handlers and the buttons that call them are therefore
  // dead-code-eliminated from production bundles — they cannot exist or be invoked
  // in prod. They only short-circuit Stripe on the client; the backend reservation
  // endpoint is unchanged, so no server-side check is weakened.
  const handleDevPaymentAccepted = async () => {
    setPaymentError(null);
    setSubmitting(true);
    await submitReservation();
    setSubmitting(false);
  };

  const handleDevPaymentRefused = () => {
    setPaymentError('Paiement refusé (simulation dev) — la carte a été déclinée.');
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      <section className="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h2 className="text-base font-semibold text-gray-900 mb-4">{t('confirm.tripDetails')}</h2>
        <div className="space-y-4">
          <div>
            <p className="text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1">
              {t('confirm.dates')}
            </p>
            <p className="text-sm text-gray-900">
              {checkInDate ? checkInDate.toLocaleDateString() : '—'} →{' '}
              {checkOutDate ? checkOutDate.toLocaleDateString() : '—'}
            </p>
            <p className="text-xs text-gray-400 mt-0.5">
              {nights} {nights > 1 ? t('request.nights') : t('request.night')} · {guestsCount}{' '}
              {t('hero.guests').toLowerCase()}
            </p>
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1">
                {t('request.arrivalTime')}
              </label>
              <input
                type="time"
                value={checkInTime}
                onChange={(e) => setCheckInTime(e.target.value)}
                className="w-full h-11 rounded-xl border border-gray-200 bg-gray-50 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 focus:bg-white transition-all"
                required
              />
            </div>
            <div>
              <label className="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1">
                {t('request.departureTime')}
              </label>
              <input
                type="time"
                value={checkOutTime}
                onChange={(e) => setCheckOutTime(e.target.value)}
                className="w-full h-11 rounded-xl border border-gray-200 bg-gray-50 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 focus:bg-white transition-all"
                required
              />
            </div>
          </div>
        </div>
      </section>

      <section className="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h2 className="text-base font-semibold text-gray-900 mb-4">{t('confirm.guestInfo')}</h2>
        <div className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              {t('request.guestName')}
            </label>
            <input
              type="text"
              value={guestName}
              onChange={(e) => setGuestName(e.target.value)}
              className="w-full h-11 rounded-xl border border-gray-200 bg-gray-50 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 focus:bg-white transition-all"
              required
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              {t('request.note')}{' '}
              <span className="text-gray-400 font-normal">({t('request.optional')})</span>
            </label>
            <textarea
              value={note}
              onChange={(e) => setNote(e.target.value)}
              rows={3}
              maxLength={2000}
              placeholder={t('request.notePlaceholder') as string}
              className="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 focus:bg-white resize-none transition-all"
            />
          </div>
        </div>
      </section>

      <section className="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h2 className="text-base font-semibold text-gray-900 mb-1">{t('confirm.payment.title')}</h2>
        <p className="text-xs text-gray-500 mb-4">{t('confirm.payment.subtitle')}</p>

        <div className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              {t('confirm.payment.cardLabel')}
            </label>
            <PaymentElement />
          </div>
        </div>

        <div className="mt-5 rounded-xl bg-primary-50 border border-primary-100 px-3 py-2.5 text-xs text-primary-800 flex items-start gap-2">
          <svg
            className="flex-shrink-0 mt-0.5"
            width="14"
            height="14"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
          >
            <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
          </svg>
          <span>{t('request.hostHas24h')}</span>
        </div>
      </section>

      {(paymentError || mutationError) && (
        <div className="text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
          {paymentError || mutationError}
        </div>
      )}

      {import.meta.env.DEV && (
        <div className="rounded-xl border border-dashed border-amber-400 bg-amber-50 px-4 py-3">
          <p className="text-[11px] font-semibold uppercase tracking-wider text-amber-700">
            Dev · bypass paiement
          </p>
          <p className="mt-1 text-xs text-amber-700/80">
            Visible uniquement en développement (retiré du build de production). Simule le
            résultat du paiement pour tester la navigation, sans appeler Stripe.
          </p>
          <div className="mt-3 flex flex-wrap gap-2">
            <button
              type="button"
              onClick={handleDevPaymentAccepted}
              disabled={submitting || missingDates || !guestName.trim() || totalCents <= 0}
              className="inline-flex items-center justify-center h-10 px-4 rounded-lg text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 disabled:cursor-not-allowed transition-colors"
            >
              Paiement accepté
            </button>
            <button
              type="button"
              onClick={handleDevPaymentRefused}
              disabled={submitting}
              className="inline-flex items-center justify-center h-10 px-4 rounded-lg text-sm font-semibold text-white bg-red-600 hover:bg-red-700 disabled:opacity-60 disabled:cursor-not-allowed transition-colors"
            >
              Paiement refusé
            </button>
          </div>
        </div>
      )}

      <div className="flex justify-end gap-2">
        <Link
          to={accommodation?.id ? accommodationPath(accommodation) : '/accommodations'}
          className="inline-flex items-center justify-center h-11 px-4 rounded-xl border border-gray-200 text-sm font-medium text-gray-700 hover:bg-gray-50"
        >
          {t('request.cancel')}
        </Link>
        <button
          type="submit"
          disabled={
            submitting ||
            mutationStatus === 'loading' ||
            missingDates ||
            !guestName.trim() ||
            !stripe ||
            !elements ||
            totalCents <= 0
          }
          className="inline-flex items-center justify-center h-11 px-6 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 shadow-sm shadow-primary-200 disabled:opacity-60 disabled:cursor-not-allowed transition-all"
        >
          {submitting ? t('request.submitting') : t('request.submit')}
        </button>
      </div>

    </form>
  );
};

const ReservationConfirmationPage: React.FC = () => {
  const { t, i18n } = useTranslation();
  const dispatch = useAppDispatch();
  const { id } = useParams<{ id: string }>();
  const [searchParams] = useSearchParams();
  const user = useAppSelector(selectAuthUser);
  const accommodation = useAppSelector(selectCurrentAccommodation);

  const checkInParam = searchParams.get('checkIn');
  const checkOutParam = searchParams.get('checkOut');
  const guestsParam = searchParams.get('guests');

  const checkInDate = parseDate(checkInParam);
  const checkOutDate = parseDate(checkOutParam);
  const guestsCount = guestsParam ? Math.max(1, parseInt(guestsParam, 10) || 1) : 1;

  const defaultGuestName = useMemo(() => {
    if (!user) return '';
    const full = [user.firstName, user.lastName].filter(Boolean).join(' ').trim();
    if (full) return full;
    return user.email?.split('@')[0] ?? '';
  }, [user]);

  useEffect(() => {
    if (id) dispatch(fetchAccommodation(id));
  }, [dispatch, id]);

  useEffect(() => {
    dispatch(clearMutationError());
  }, [dispatch]);

  const locale = i18n.language.startsWith('fr') ? 'fr-FR' : 'en-GB';
  const formatDate = (d: Date) =>
    new Intl.DateTimeFormat(locale, {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    }).format(d);

  const nights = useMemo(() => {
    if (!checkInDate || !checkOutDate) return 0;
    return Math.max(
      0,
      Math.round((checkOutDate.getTime() - checkInDate.getTime()) / 86_400_000)
    );
  }, [checkInDate, checkOutDate]);

  const pricePerNight = accommodation?.price ?? 0;
  const weeklyPromo = accommodation?.weeklyPromotionPercentage ?? null;
  const promoApplies =
    nights >= 7 && weeklyPromo !== null && weeklyPromo !== undefined && weeklyPromo > 0;
  const effectiveNightly = promoApplies
    ? pricePerNight * (1 - (weeklyPromo ?? 0) / 100)
    : pricePerNight;
  const subtotal = effectiveNightly * nights;
  const platformFee = subtotal * PLATFORM_COMMISSION_RATE;
  const solidarityFee = subtotal * SOLIDARITY_RATE;
  const total = subtotal + platformFee + solidarityFee;
  const totalCents = Math.round(total * 100);
  const formatPrice = (n: number) =>
    `${n.toLocaleString(i18n.language, { maximumFractionDigits: 2 })} €`;

  const missingDates = !checkInDate || !checkOutDate;

  // Dates are picked on the accommodation page, not here. Reaching /book without
  // them (e.g. direct link) is a dead-end: send the user back to choose dates
  // rather than showing a form that can't be submitted.
  if (missingDates) {
    return <Navigate to={id ? `/hebergements/${id}` : '/accommodations'} replace />;
  }

  // Stripe Elements requires a stable amount; if no nights yet, use a placeholder.
  const elementsAmount = totalCents > 0 ? totalCents : 100;

  const elementsOptions = {
    mode: 'payment' as const,
    amount: elementsAmount,
    currency: 'eur',
    capture_method: 'manual' as const,
  };

  return (
    <div className="min-h-[calc(100vh-4rem)] flex flex-col bg-gradient-to-b from-primary-50/30 via-white to-white">
      <div className="flex-1 w-full max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <Link
          to={accommodation?.id ? accommodationPath(accommodation) : '/accommodations'}
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
          {t('confirm.back')}
        </Link>

        <header className="mb-8">
          <div className="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-primary-700">
            <span className="w-1.5 h-1.5 rounded-full bg-primary-500" />
            {t('confirm.eyebrow')}
          </div>
          <h1 className="mt-2 text-3xl font-bold text-gray-900 tracking-tight">
            {t('request.title')}
          </h1>
          <p className="text-gray-500 mt-1">{t('request.subtitle')}</p>
        </header>

        {missingDates && (
          <div className="mb-6 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
            {t('confirm.missingDates')}
          </div>
        )}

        <div className="grid grid-cols-1 lg:grid-cols-[1fr_360px] gap-6">
          {accommodation && user && id ? (
            <Elements stripe={stripePromise} options={elementsOptions}>
              <BookingForm
                accommodation={accommodation}
                user={user}
                accommodationId={id}
                checkInDate={checkInDate}
                checkOutDate={checkOutDate}
                guestsCount={guestsCount}
                nights={nights}
                totalCents={totalCents}
                missingDates={missingDates}
                defaultGuestName={defaultGuestName}
                totalLabel={formatPrice(total)}
              />
            </Elements>
          ) : (
            <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 text-sm text-gray-500">
              {t('confirm.missingDates')}
            </div>
          )}

          <aside className="lg:sticky lg:top-24 h-fit">
            <div className="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
              {accommodation?.thumbnailUrl && (
                <img
                  src={accommodation.thumbnailUrl}
                  alt={accommodation.title}
                  className="w-full h-32 object-cover"
                  loading="lazy"
                />
              )}
              <div className="p-5 space-y-3">
                <div>
                  <p className="text-base font-semibold text-gray-900">
                    {accommodation?.title ?? '—'}
                  </p>
                  {accommodation?.city && (
                    <p className="text-xs text-gray-500 mt-0.5">{accommodation.city}</p>
                  )}
                  {checkInDate && checkOutDate && (
                    <p className="text-xs text-gray-400 mt-1">
                      {formatDate(checkInDate)} → {formatDate(checkOutDate)}
                    </p>
                  )}
                </div>

                {nights > 0 && (
                  <div className="pt-3 border-t border-gray-100 space-y-2 text-sm">
                    {promoApplies && (
                      <div className="flex items-center justify-between rounded-lg bg-emerald-50 border border-emerald-100 px-3 py-2 text-emerald-700">
                        <span className="text-xs font-medium">
                          {t('detail.weeklyDiscountBadge', { percent: weeklyPromo })}
                        </span>
                      </div>
                    )}
                    <div className="flex justify-between text-gray-600">
                      <span>
                        {formatPrice(effectiveNightly)} × {nights}
                      </span>
                      <span>{formatPrice(subtotal)}</span>
                    </div>
                    <div className="flex justify-between text-gray-600">
                      <span>
                        {t('detail.platformFee', {
                          rate: Math.round(PLATFORM_COMMISSION_RATE * 100),
                        })}
                      </span>
                      <span>{formatPrice(platformFee)}</span>
                    </div>
                    <div className="flex justify-between text-gray-600">
                      <span>
                        {t('detail.solidarityFee', { rate: Math.round(SOLIDARITY_RATE * 100) })}
                      </span>
                      <span>{formatPrice(solidarityFee)}</span>
                    </div>
                    <div className="flex justify-between font-bold text-gray-900 pt-2 border-t border-gray-100">
                      <span>{t('detail.total')}</span>
                      <span>{formatPrice(total)}</span>
                    </div>
                  </div>
                )}
              </div>
            </div>
          </aside>
        </div>
      </div>
      <Footer />
    </div>
  );
};

export default ReservationConfirmationPage;
