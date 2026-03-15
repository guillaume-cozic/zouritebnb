import React from 'react';
import { useTranslation } from 'react-i18next';
import { AccommodationListItem } from '../HomepageTypes';

const API_BASE = process.env.REACT_APP_API_URL || 'http://localhost:8080';

interface AccommodationCardProps {
  accommodation: AccommodationListItem;
}

const AccommodationCard: React.FC<AccommodationCardProps> = ({ accommodation }) => {
  const { t } = useTranslation();
  const thumbnailSrc = accommodation.thumbnailUrl
    ? `${API_BASE}${accommodation.thumbnailUrl}`
    : null;

  return (
    <div className="bg-white rounded-xl overflow-hidden shadow-md hover:shadow-lg transition-shadow">
      <div className="aspect-[4/3] bg-gray-200 relative">
        {thumbnailSrc ? (
          <img
            src={thumbnailSrc}
            alt={accommodation.title}
            className="w-full h-full object-cover"
          />
        ) : (
          <div className="w-full h-full flex items-center justify-center text-gray-400">
            <svg xmlns="http://www.w3.org/2000/svg" className="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
            </svg>
          </div>
        )}
      </div>
      <div className="p-4">
        <h3 className="font-semibold text-gray-800 truncate">{accommodation.title}</h3>
        <p className="text-sm text-gray-500 mt-1">
          {[accommodation.city, accommodation.country].filter(Boolean).join(', ')}
        </p>
        <div className="flex items-center justify-between mt-3">
          <span className="font-bold text-gray-900">
            {accommodation.price != null ? `${accommodation.price} ${t('homepage.perNight')}` : '\u2014'}
          </span>
          {accommodation.maxGuests != null && (
            <span className="text-sm text-gray-500">
              {t('homepage.guest', { count: accommodation.maxGuests })}
            </span>
          )}
        </div>
      </div>
    </div>
  );
};

export default AccommodationCard;
