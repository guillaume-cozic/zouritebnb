import React from 'react';
import { Link } from 'react-router-dom';
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
    <div className="rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden group hover:shadow-xl hover:shadow-gray-200/50 transition-all duration-300 hover:-translate-y-1">
      {/* Image */}
      <Link to={`/accommodations/${accommodation.id}`} className="block aspect-video relative overflow-hidden cursor-pointer">
        {thumbnailSrc ? (
          <img
            src={thumbnailSrc}
            alt={accommodation.title}
            className="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
          />
        ) : (
          <div className="absolute inset-0 bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" className="h-16 w-16 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
          </div>
        )}
        {/* Gradient overlay on hover */}
        <div className="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300" />

        {/* Price badge on image */}
        {accommodation.price != null && (
          <div className="absolute top-3 left-3 bg-white/95 backdrop-blur-sm rounded-lg px-3 py-1.5 shadow-sm">
            <span className="font-bold text-gray-900 text-sm">
              {accommodation.price}{'\u00A0'}€
            </span>
            <span className="text-gray-500 text-xs font-normal"> / {t('homepage.night')}</span>
          </div>
        )}

        {/* Guest count badge */}
        {accommodation.maxGuests != null && (
          <div className="absolute top-3 right-3 bg-white/95 backdrop-blur-sm rounded-lg px-2.5 py-1.5 shadow-sm flex items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-gray-500">
              <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
              <circle cx="9" cy="7" r="4" />
            </svg>
            <span className="text-xs font-medium text-gray-700">{accommodation.maxGuests}</span>
          </div>
        )}
      </Link>

      {/* Content */}
      <div className="p-5">
        <h3 className="font-semibold text-lg text-gray-900 mb-1 group-hover:text-blue-600 transition-colors">
          {accommodation.title}
        </h3>

        <div className="flex items-center text-sm text-gray-500 mb-3">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="mr-1 text-gray-400 flex-shrink-0">
            <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0" />
            <circle cx="12" cy="10" r="3" />
          </svg>
          {[accommodation.city, accommodation.country].filter(Boolean).join(', ') || '\u2014'}
        </div>

        {accommodation.description && (
          <p className="text-sm text-gray-500 mb-4 line-clamp-2 leading-relaxed">
            {accommodation.description}
          </p>
        )}

        <div className="pt-3 border-t border-gray-100 flex items-center justify-between">
          <div>
            {accommodation.price != null ? (
              <p className="text-xl font-bold text-gray-900">
                {accommodation.price}{'\u00A0'}€
                <span className="text-sm font-normal text-gray-500 ml-1">/ {t('homepage.night')}</span>
              </p>
            ) : (
              <p className="text-gray-400">{'\u2014'}</p>
            )}
          </div>
          <Link
            to={`/accommodations/${accommodation.id}`}
            className="inline-flex items-center gap-1.5 rounded-xl text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 h-10 px-5 transition-all hover:shadow-md hover:shadow-blue-200"
          >
            {t('homepage.viewDetails')}
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
              <path d="M5 12h14" />
              <path d="m12 5 7 7-7 7" />
            </svg>
          </Link>
        </div>
      </div>
    </div>
  );
};

export default AccommodationCard;
