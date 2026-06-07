import React from 'react';
import { Navigate, Outlet } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAppSelector } from '../../../store/hooks';
import {
  selectHasAccommodation,
  selectOwnershipStatus,
} from '../AccommodationManagementSelectors';

/**
 * Route guard for host pages that are meaningless without a listing
 * (Mes hébergements, Réservations, Calendrier).
 *
 * A host that owns no accommodation is redirected to the creation wizard.
 * The ownership signal is loaded by BackofficeLayout; while it is still
 * unresolved (`null`) we wait instead of redirecting, to avoid a flash.
 */
const RequireAccommodation: React.FC = () => {
  const { t } = useTranslation();
  const hasAccommodation = useAppSelector(selectHasAccommodation);
  const ownershipStatus = useAppSelector(selectOwnershipStatus);

  if (hasAccommodation === false) {
    return <Navigate to="/create" replace />;
  }

  if (hasAccommodation === null && ownershipStatus !== 'failed') {
    return (
      <div className="flex items-center justify-center py-24 text-gray-400 text-sm">
        {t('homepage.loading')}
      </div>
    );
  }

  return <Outlet />;
};

export default RequireAccommodation;
