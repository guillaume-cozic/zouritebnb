import React, { useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import {
  fetchAllAccommodations,
  publishAccommodation,
  unpublishAccommodation,
  setStatusFilter,
} from '../AccommodationManagementSlice';
import {
  selectManagedAccommodations,
  selectManagementStatus,
  selectManagementError,
  selectManagementStatusFilter,
} from '../AccommodationManagementSelectors';
import { StatusFilter } from '../AccommodationManagementTypes';
import EmptyState, { HomeIcon } from '../../../components/EmptyState';

const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:8080';

const StatusBadge: React.FC<{ status: string }> = ({ status }) => {
  const classes =
    status === 'published'
      ? 'bg-green-50 text-green-700 border-green-200'
      : 'bg-amber-50 text-amber-700 border-amber-200';
  return (
    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border ${classes}`}>
      {status}
    </span>
  );
};

const BackofficeAccommodationsPage: React.FC = () => {
  const { t } = useTranslation();
  const dispatch = useAppDispatch();
  const items = useAppSelector(selectManagedAccommodations);
  const status = useAppSelector(selectManagementStatus);
  const error = useAppSelector(selectManagementError);
  const statusFilter = useAppSelector(selectManagementStatusFilter);

  useEffect(() => {
    dispatch(fetchAllAccommodations(statusFilter));
  }, [dispatch, statusFilter]);

  const filters: StatusFilter[] = ['all', 'published', 'draft'];

  return (
    <div className="w-full px-4 sm:px-6 lg:px-8 py-10">
      <div className="flex items-end justify-between mb-8 gap-4">
        <header className="relative">
          <div className="absolute -left-4 top-0 bottom-2 w-1 bg-gradient-to-b from-primary-500 via-primary-400 to-transparent rounded-full" aria-hidden="true" />
          <div className="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-primary-700">
            <span className="w-1.5 h-1.5 rounded-full bg-primary-500" />
            {t('backoffice.menu.title')}
          </div>
          <h1 className="mt-2 text-3xl font-bold text-gray-900 tracking-tight">{t('backoffice.title')}</h1>
          <p className="text-gray-500 mt-1">{t('backoffice.subtitle')}</p>
        </header>
        <Link to="/create">
          <button className="inline-flex items-center gap-2 rounded-xl text-sm font-medium text-white bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 h-10 px-5 shadow-sm shadow-primary-200 transition-all">
            {t('navbar.createAccommodation')}
          </button>
        </Link>
      </div>

      <div className="flex items-center gap-2 mb-6">
        {filters.map((f) => (
          <button
            key={f}
            onClick={() => dispatch(setStatusFilter(f))}
            className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
              statusFilter === f
                ? 'bg-primary-600 text-white'
                : 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50'
            }`}
          >
            {t(`backoffice.filter.${f}`)}
          </button>
        ))}
      </div>

      {status === 'loading' && (
        <div className="text-center py-12 text-gray-500">{t('homepage.loading')}</div>
      )}
      {status === 'failed' && (
        <div className="text-center py-12 text-red-500">{error}</div>
      )}
      {status === 'succeeded' && items.length === 0 && (
        statusFilter === 'all' ? (
          <EmptyState
            icon={<HomeIcon />}
            title={t('backoffice.empty.title')}
            description={t('backoffice.empty.description')}
            action={
              <Link to="/create">
                <button className="inline-flex items-center gap-2 rounded-xl text-sm font-medium text-white bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 h-10 px-5 shadow-sm shadow-primary-200 transition-all">
                  {t('navbar.createAccommodation')}
                </button>
              </Link>
            }
          />
        ) : (
          <EmptyState
            icon={<HomeIcon />}
            title={t('backoffice.empty.filteredTitle')}
            description={t('backoffice.empty.filteredDescription')}
            action={
              <button
                onClick={() => dispatch(setStatusFilter('all'))}
                className="inline-flex items-center gap-2 rounded-xl text-sm font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 h-10 px-5 transition-colors"
              >
                {t('backoffice.empty.showAll')}
              </button>
            }
          />
        )
      )}
      {items.length > 0 && (
        <div className="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 border-b border-gray-100">
              <tr>
                <th className="text-left px-4 py-3 font-medium text-gray-600"></th>
                <th className="text-left px-4 py-3 font-medium text-gray-600">{t('backoffice.col.title')}</th>
                <th className="text-left px-4 py-3 font-medium text-gray-600">{t('backoffice.col.city')}</th>
                <th className="text-left px-4 py-3 font-medium text-gray-600">{t('backoffice.col.price')}</th>
                <th className="text-left px-4 py-3 font-medium text-gray-600">{t('backoffice.col.status')}</th>
                <th className="text-right px-4 py-3 font-medium text-gray-600">{t('backoffice.col.actions')}</th>
              </tr>
            </thead>
            <tbody>
              {items.map((item) => (
                <tr key={item.id} className="border-b border-gray-50 last:border-0 hover:bg-gray-50/50">
                  <td className="px-4 py-3 w-20">
                    {item.thumbnailUrl ? (
                      <img
                        src={`${API_BASE}${item.thumbnailUrl}`}
                        alt={item.title}
                        className="w-14 h-14 rounded-lg object-cover"
                      />
                    ) : (
                      <div className="w-14 h-14 rounded-lg bg-gray-100" />
                    )}
                  </td>
                  <td className="px-4 py-3 font-medium text-gray-900">{item.title}</td>
                  <td className="px-4 py-3 text-gray-600">
                    {item.city ? `${item.city}${item.country ? `, ${item.country}` : ''}` : '—'}
                  </td>
                  <td className="px-4 py-3 text-gray-600">
                    {item.price !== null ? `${item.price} €` : '—'}
                  </td>
                  <td className="px-4 py-3">
                    <StatusBadge status={item.status} />
                  </td>
                  <td className="px-4 py-3 text-right">
                    <div className="inline-flex items-center gap-2">
                      <Link
                        to={`/accommodations/${item.id}/edit`}
                        className="px-3 py-1.5 rounded-lg text-xs font-medium border border-gray-200 bg-white hover:bg-gray-50"
                      >
                        {t('backoffice.action.edit')}
                      </Link>
                      <Link
                        to={`/admin/accommodations/${item.id}/calendar`}
                        className="px-3 py-1.5 rounded-lg text-xs font-medium border border-primary-200 bg-primary-50 text-primary-700 hover:bg-primary-100"
                      >
                        {t('backoffice.action.calendar')}
                      </Link>
                      {item.status === 'published' ? (
                        <button
                          onClick={() => dispatch(unpublishAccommodation(item.id))}
                          className="px-3 py-1.5 rounded-lg text-xs font-medium border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100"
                        >
                          {t('backoffice.action.unpublish')}
                        </button>
                      ) : (
                        <button
                          onClick={() => dispatch(publishAccommodation(item.id))}
                          className="px-3 py-1.5 rounded-lg text-xs font-medium border border-green-200 bg-green-50 text-green-700 hover:bg-green-100"
                        >
                          {t('backoffice.action.publish')}
                        </button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
};

export default BackofficeAccommodationsPage;
