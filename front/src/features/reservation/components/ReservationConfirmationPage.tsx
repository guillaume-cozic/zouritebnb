import React, { useEffect, useMemo, useState } from 'react';
import { Link, useNavigate, useParams, useSearchParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { fetchAccommodation } from '../../accommodation/AccommodationSlice';
import { selectCurrentAccommodation } from '../../accommodation/AccommodationSelectors';
import { requestReservation, clearMutationError } from '../ReservationSlice';
import {
  selectReservationMutationError,
  selectReservationMutationStatus,
} from '../ReservationSelectors';
import { selectAuthUser } from '../../auth/AuthSelectors';
import Footer from '../../../components/Footer';

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

const ReservationConfirmationPage: React.FC = () => {
  const { t, i18n } = useTranslation();
  const dispatch = useAppDispatch();
  const navigate = useNavigate();
  const { id } = useParams<{ id: string }>();
  const [searchParams] = useSearchParams();
  const user = useAppSelector(selectAuthUser);
  const accommodation = useAppSelector(selectCurrentAccommodation);
  const mutationStatus = useAppSelector(selectReservationMutationStatus);
  const mutationError = useAppSelector(selectReservationMutationError);

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
  const [guestName, setGuestName] = useState(defaultGuestName);
  const [checkInTime, setCheckInTime] = useState('15:00');
  const [checkOutTime, setCheckOutTime] = useState('11:00');
  const [note, setNote] = useState('');
  const [cardName, setCardName] = useState('');
  const [cardNumber, setCardNumber] = useState('');
  const [cardExpiry, setCardExpiry] = useState('');
  const [cardCvc, setCardCvc] = useState('');
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    if (id) dispatch(fetchAccommodation(id));
  }, [dispatch, id]);

  useEffect(() => {
    dispatch(clearMutationError());
  }, [dispatch]);

  useEffect(() => {
    if (defaultGuestName) setGuestName((prev) => prev || defaultGuestName);
  }, [defaultGuestName]);

  const locale = i18n.language.startsWith('fr') ? 'fr-FR' : 'en-GB';
  const formatDate = (d: Date) =>
    new Intl.DateTimeFormat(locale, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(d);

  const nights = useMemo(() => {
    if (!checkInDate || !checkOutDate) return 0;
    return Math.max(0, Math.round((checkOutDate.getTime() - checkInDate.getTime()) / 86_400_000));
  }, [checkInDate, checkOutDate]);

  const pricePerNight = accommodation?.price ?? 0;
  const weeklyPromo = accommodation?.weeklyPromotionPercentage ?? null;
  const promoApplies = nights >= 7 && weeklyPromo !== null && weeklyPromo !== undefined && weeklyPromo > 0;
  const effectiveNightly = promoApplies ? pricePerNight * (1 - (weeklyPromo ?? 0) / 100) : pricePerNight;
  const subtotal = effectiveNightly * nights;
  const platformFee = subtotal * PLATFORM_COMMISSION_RATE;
  const solidarityFee = subtotal * SOLIDARITY_RATE;
  const total = subtotal + platformFee + solidarityFee;
  const formatPrice = (n: number) => `${n.toLocaleString(i18n.language, { maximumFractionDigits: 2 })} €`;

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!user || !checkInDate || !checkOutDate || !accommodation || !guestName.trim()) return;

    if (!id) return;
    setSubmitting(true);
    const result = await dispatch(
      requestReservation({
        accommodationId: id,
        guestUserId: user.id,
        checkIn: toApiDateTime(checkInDate, checkInTime),
        checkOut: toApiDateTime(checkOutDate, checkOutTime),
        guestName: guestName.trim(),
        note: note.trim() || undefined,
      })
    );
    setSubmitting(false);

    if (requestReservation.fulfilled.match(result)) {
      navigate('/account/conversations');
    }
  };

  const missingDates = !checkInDate || !checkOutDate;

  return (
    <div className="min-h-[calc(100vh-4rem)] flex flex-col bg-gradient-to-b from-blue-50/30 via-white to-white">
      <div className="flex-1 w-full max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <Link
          to={accommodation?.id ? `/accommodations/${accommodation.id}` : '/accommodations'}
          className="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-blue-700 mb-6"
        >
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
            <path d="m15 18-6-6 6-6" />
          </svg>
          {t('confirm.back')}
        </Link>

        <header className="mb-8">
          <div className="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-blue-700">
            <span className="w-1.5 h-1.5 rounded-full bg-blue-500" />
            {t('confirm.eyebrow')}
          </div>
          <h1 className="mt-2 text-3xl font-bold text-gray-900 tracking-tight">{t('request.title')}</h1>
          <p className="text-gray-500 mt-1">{t('request.subtitle')}</p>
        </header>

        {missingDates && (
          <div className="mb-6 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
            {t('confirm.missingDates')}
          </div>
        )}

        <div className="grid grid-cols-1 lg:grid-cols-[1fr_360px] gap-6">
          <form onSubmit={handleSubmit} className="space-y-6">
            <section className="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
              <h2 className="text-base font-semibold text-gray-900 mb-4">{t('confirm.tripDetails')}</h2>
              <div className="space-y-4">
                <div>
                  <p className="text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1">
                    {t('confirm.dates')}
                  </p>
                  <p className="text-sm text-gray-900">
                    {checkInDate ? formatDate(checkInDate) : '—'} → {checkOutDate ? formatDate(checkOutDate) : '—'}
                  </p>
                  <p className="text-xs text-gray-400 mt-0.5">
                    {nights} {nights > 1 ? t('request.nights') : t('request.night')} · {guestsCount} {t('hero.guests').toLowerCase()}
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
                      className="w-full h-11 rounded-xl border border-gray-200 bg-gray-50 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 focus:bg-white transition-all"
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
                      className="w-full h-11 rounded-xl border border-gray-200 bg-gray-50 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 focus:bg-white transition-all"
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
                  <label className="block text-sm font-medium text-gray-700 mb-1">{t('request.guestName')}</label>
                  <input
                    type="text"
                    value={guestName}
                    onChange={(e) => setGuestName(e.target.value)}
                    className="w-full h-11 rounded-xl border border-gray-200 bg-gray-50 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 focus:bg-white transition-all"
                    required
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    {t('request.note')} <span className="text-gray-400 font-normal">({t('request.optional')})</span>
                  </label>
                  <textarea
                    value={note}
                    onChange={(e) => setNote(e.target.value)}
                    rows={3}
                    maxLength={2000}
                    placeholder={t('request.notePlaceholder') as string}
                    className="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 focus:bg-white resize-none transition-all"
                  />
                </div>
              </div>
            </section>

            <section className="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
              <h2 className="text-base font-semibold text-gray-900 mb-1">{t('confirm.payment.title')}</h2>
              <p className="text-xs text-gray-500 mb-4">{t('confirm.payment.subtitle')}</p>

              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">{t('confirm.payment.cardName')}</label>
                  <input
                    type="text"
                    value={cardName}
                    onChange={(e) => setCardName(e.target.value)}
                    placeholder={t('confirm.payment.cardNamePlaceholder') as string}
                    autoComplete="cc-name"
                    className="w-full h-11 rounded-xl border border-gray-200 bg-gray-50 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 focus:bg-white transition-all"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">{t('confirm.payment.cardNumber')}</label>
                  <div className="relative">
                    <svg className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" width="18" height="14" viewBox="0 0 24 18" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                      <rect x="1" y="1" width="22" height="16" rx="2" />
                      <line x1="1" y1="6" x2="23" y2="6" />
                    </svg>
                    <input
                      type="text"
                      inputMode="numeric"
                      value={cardNumber}
                      onChange={(e) => setCardNumber(e.target.value.replace(/[^\d ]/g, ''))}
                      placeholder="4242 4242 4242 4242"
                      autoComplete="cc-number"
                      maxLength={23}
                      className="w-full h-11 pl-10 pr-3 rounded-xl border border-gray-200 bg-gray-50 text-sm tracking-wider font-mono focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 focus:bg-white transition-all"
                    />
                  </div>
                </div>
                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">{t('confirm.payment.expiry')}</label>
                    <input
                      type="text"
                      value={cardExpiry}
                      onChange={(e) => setCardExpiry(e.target.value.replace(/[^\d/]/g, ''))}
                      placeholder="MM/AA"
                      autoComplete="cc-exp"
                      maxLength={5}
                      className="w-full h-11 rounded-xl border border-gray-200 bg-gray-50 px-3 text-sm font-mono tracking-wider focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 focus:bg-white transition-all"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">{t('confirm.payment.cvc')}</label>
                    <input
                      type="text"
                      inputMode="numeric"
                      value={cardCvc}
                      onChange={(e) => setCardCvc(e.target.value.replace(/\D/g, ''))}
                      placeholder="123"
                      autoComplete="cc-csc"
                      maxLength={4}
                      className="w-full h-11 rounded-xl border border-gray-200 bg-gray-50 px-3 text-sm font-mono tracking-wider focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 focus:bg-white transition-all"
                    />
                  </div>
                </div>
              </div>

              <div className="mt-5 rounded-xl bg-blue-50 border border-blue-100 px-3 py-2.5 text-xs text-blue-800 flex items-start gap-2">
                <svg className="flex-shrink-0 mt-0.5" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                  <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
                  <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                </svg>
                <span>{t('request.hostHas24h')}</span>
              </div>
            </section>

            {mutationError && (
              <div className="text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                {mutationError}
              </div>
            )}

            <div className="flex justify-end gap-2">
              <Link
                to={accommodation?.id ? `/accommodations/${accommodation.id}` : '/accommodations'}
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
                  !accommodation
                }
                className="inline-flex items-center justify-center h-11 px-6 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 shadow-sm shadow-blue-200 disabled:opacity-60 disabled:cursor-not-allowed transition-all"
              >
                {submitting ? t('request.submitting') : t('request.submit')}
              </button>
            </div>
          </form>

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
                      <span>{formatPrice(effectiveNightly)} × {nights}</span>
                      <span>{formatPrice(subtotal)}</span>
                    </div>
                    <div className="flex justify-between text-gray-600">
                      <span>{t('detail.platformFee', { rate: Math.round(PLATFORM_COMMISSION_RATE * 100) })}</span>
                      <span>{formatPrice(platformFee)}</span>
                    </div>
                    <div className="flex justify-between text-gray-600">
                      <span>{t('detail.solidarityFee', { rate: Math.round(SOLIDARITY_RATE * 100) })}</span>
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
