import React, { useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { Card, Spinner } from '../../../components/ui';
import { revenuePageOpened } from '../HostRevenueSlice';
import {
  selectHostRevenue,
  selectHostRevenueStatus,
  selectHostRevenueError,
} from '../HostRevenueSelectors';
import { HostRevenue, PayoutStatus } from '../HostRevenueTypes';

const useLocale = (): string => {
  const { i18n } = useTranslation();
  return i18n.language.startsWith('fr') ? 'fr-FR' : 'en-GB';
};

const formatMoney = (amount: number, locale: string): string =>
  new Intl.NumberFormat(locale, {
    style: 'currency',
    currency: 'EUR',
    maximumFractionDigits: 0,
  }).format(amount);

const formatDate = (iso: string, locale: string): string =>
  new Intl.DateTimeFormat(locale, { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(iso));

const formatMonth = (month: string, locale: string): string => {
  const [year, m] = month.split('-');
  return new Intl.DateTimeFormat(locale, { month: 'long', year: 'numeric' }).format(
    new Date(Number(year), Number(m) - 1, 1)
  );
};

const SummaryCard: React.FC<{ label: string; amount: string; hint: string; tone: 'primary' | 'warning' | 'success' }> = ({
  label,
  amount,
  hint,
  tone,
}) => {
  const toneClass = {
    primary: 'text-primary-700',
    warning: 'text-warning-600',
    success: 'text-success-600',
  }[tone];
  return (
    <Card>
      <p className="text-xs font-medium uppercase tracking-wide text-surface-500">{label}</p>
      <p className={`mt-2 text-3xl font-bold ${toneClass}`}>{amount}</p>
      <p className="mt-1 text-sm text-surface-500">{hint}</p>
    </Card>
  );
};

const PayoutStatusBadge: React.FC<{ status: PayoutStatus }> = ({ status }) => {
  const { t } = useTranslation();
  const cls =
    status === 'available'
      ? 'bg-success-50 text-success-700 border-success-200'
      : 'bg-warning-50 text-warning-700 border-warning-200';
  return (
    <span className={`inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium ${cls}`}>
      {t(`revenue.payoutStatus.${status}`)}
    </span>
  );
};

const RevenueContent: React.FC<{ data: HostRevenue }> = ({ data }) => {
  const { t } = useTranslation();
  const locale = useLocale();

  return (
    <>
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <SummaryCard
          label={t('revenue.summary.total')}
          amount={formatMoney(data.totalEarned, locale)}
          hint={t('revenue.summary.totalHint', { count: data.confirmedReservations })}
          tone="primary"
        />
        <SummaryCard
          label={t('revenue.summary.pending')}
          amount={formatMoney(data.pendingAmount, locale)}
          hint={t('revenue.summary.pendingHint', { count: data.upcomingStays })}
          tone="warning"
        />
        <SummaryCard
          label={t('revenue.summary.available')}
          amount={formatMoney(data.availableAmount, locale)}
          hint={t('revenue.summary.availableHint')}
          tone="success"
        />
      </div>

      <Card title={t('revenue.payouts.title')} subtitle={t('revenue.payouts.subtitle')} className="mt-6">
        {data.payouts.length === 0 ? (
          <p className="py-6 text-center text-sm text-surface-400">{t('revenue.empty')}</p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-surface-200 text-left text-surface-500">
                  <th className="px-3 py-2 font-medium">{t('revenue.payouts.accommodation')}</th>
                  <th className="px-3 py-2 font-medium">{t('revenue.payouts.guest')}</th>
                  <th className="px-3 py-2 font-medium">{t('revenue.payouts.dates')}</th>
                  <th className="px-3 py-2 text-right font-medium">{t('revenue.payouts.amount')}</th>
                  <th className="px-3 py-2 text-right font-medium">{t('revenue.payouts.status')}</th>
                </tr>
              </thead>
              <tbody>
                {data.payouts.map((p) => (
                  <tr key={p.reservationId} className="border-b border-surface-100 last:border-0">
                    <td className="px-3 py-2.5 text-surface-900">
                      {p.accommodationTitle ?? t('revenue.payouts.unknownAccommodation')}
                    </td>
                    <td className="px-3 py-2.5 text-surface-700">{p.guestName}</td>
                    <td className="px-3 py-2.5 text-surface-500">
                      {formatDate(p.checkIn, locale)} → {formatDate(p.checkOut, locale)}
                    </td>
                    <td className="px-3 py-2.5 text-right font-semibold text-surface-900">
                      {formatMoney(p.amount, locale)}
                    </td>
                    <td className="px-3 py-2.5 text-right">
                      <PayoutStatusBadge status={p.status} />
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </Card>

      <div className="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <Card title={t('revenue.byAccommodation.title')}>
          {data.byAccommodation.length === 0 ? (
            <p className="py-6 text-center text-sm text-surface-400">{t('revenue.empty')}</p>
          ) : (
            <ul className="divide-y divide-surface-100">
              {data.byAccommodation.map((a) => (
                <li key={a.accommodationId ?? a.title ?? 'unknown'} className="flex items-center justify-between py-2.5">
                  <span className="min-w-0 truncate text-surface-800">
                    {a.title ?? t('revenue.payouts.unknownAccommodation')}
                    <span className="ml-2 text-xs text-surface-400">
                      {t('revenue.byAccommodation.reservations', { count: a.reservations })}
                    </span>
                  </span>
                  <span className="font-semibold text-surface-900">{formatMoney(a.amount, locale)}</span>
                </li>
              ))}
            </ul>
          )}
        </Card>

        <Card title={t('revenue.byMonth.title')} subtitle={t('revenue.byMonth.subtitle')}>
          {data.byMonth.length === 0 ? (
            <p className="py-6 text-center text-sm text-surface-400">{t('revenue.empty')}</p>
          ) : (
            <ul className="divide-y divide-surface-100">
              {data.byMonth.map((m) => (
                <li key={m.month} className="flex items-center justify-between py-2.5">
                  <span className="capitalize text-surface-800">{formatMonth(m.month, locale)}</span>
                  <span className="font-semibold text-surface-900">{formatMoney(m.amount, locale)}</span>
                </li>
              ))}
            </ul>
          )}
        </Card>
      </div>
    </>
  );
};

const HostRevenuePage: React.FC = () => {
  const { t } = useTranslation();
  const dispatch = useAppDispatch();
  const data = useAppSelector(selectHostRevenue);
  const status = useAppSelector(selectHostRevenueStatus);
  const error = useAppSelector(selectHostRevenueError);

  useEffect(() => {
    dispatch(revenuePageOpened());
  }, [dispatch]);

  return (
    <div className="w-full px-6 py-8 sm:px-10">
      <header className="mb-6">
        <h1 className="text-2xl font-bold text-surface-900">{t('revenue.title')}</h1>
        <p className="mt-1 text-sm text-surface-500">{t('revenue.subtitle')}</p>
      </header>

      {status === 'loading' && !data && (
        <div className="flex justify-center py-16">
          <Spinner />
        </div>
      )}
      {status === 'failed' && (
        <div className="rounded-xl border border-danger-200 bg-danger-50 px-4 py-3 text-sm text-danger-700">{error}</div>
      )}
      {data && <RevenueContent data={data} />}
    </div>
  );
};

export default HostRevenuePage;
