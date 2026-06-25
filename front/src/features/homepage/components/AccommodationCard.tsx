import React, { useEffect, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { AccommodationListItem } from '../HomepageTypes';
import RatingBadge from '../../review/components/RatingBadge';
import WishlistButton from '../../wishlist/components/WishlistButton';

const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:8080';
const PHOTO_CYCLE_MS = 1200;

interface AccommodationCardProps {
  accommodation: AccommodationListItem;
  onHoverChange?: (hovered: boolean) => void;
}

const AccommodationCard: React.FC<AccommodationCardProps> = ({ accommodation, onHoverChange }) => {
  const { t } = useTranslation();

  const photos = (accommodation.photoUrls && accommodation.photoUrls.length > 0)
    ? accommodation.photoUrls
    : (accommodation.thumbnailUrl ? [accommodation.thumbnailUrl] : []);
  const absolutePhotos = photos.map((u) => `${API_BASE}${u}`);

  const [photoIndex, setPhotoIndex] = useState(0);
  const [isHovering, setIsHovering] = useState(false);
  const intervalRef = useRef<number | null>(null);

  useEffect(() => {
    if (!isHovering || absolutePhotos.length <= 1) {
      if (intervalRef.current) {
        window.clearInterval(intervalRef.current);
        intervalRef.current = null;
      }
      return;
    }
    intervalRef.current = window.setInterval(() => {
      setPhotoIndex((i) => (i + 1) % absolutePhotos.length);
    }, PHOTO_CYCLE_MS);
    return () => {
      if (intervalRef.current) {
        window.clearInterval(intervalRef.current);
        intervalRef.current = null;
      }
    };
  }, [isHovering, absolutePhotos.length]);

  useEffect(() => {
    if (!isHovering) setPhotoIndex(0);
  }, [isHovering]);

  return (
    <div
      className="relative flex flex-col h-full rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden group hover:shadow-xl hover:shadow-gray-200/50 transition-all duration-300 hover:-translate-y-1"
      onMouseEnter={() => { setIsHovering(true); onHoverChange?.(true); }}
      onMouseLeave={() => { setIsHovering(false); onHoverChange?.(false); }}
    >
      {/* Wishlist heart (over the photo, above the card-wide link) */}
      <WishlistButton accommodationId={accommodation.id} className="absolute top-3 right-3 z-10" />
      {/* Image */}
      <Link to={`/accommodations/${accommodation.id}`} className="block aspect-video relative overflow-hidden cursor-pointer">
        {absolutePhotos.length > 0 ? (
          <>
            {absolutePhotos.map((src, i) => (
              <img
                key={src}
                src={src}
                alt={accommodation.title}
                loading={i === 0 ? 'eager' : 'lazy'}
                className={`absolute inset-0 h-full w-full object-cover transition-opacity duration-500 ${i === photoIndex ? 'opacity-100' : 'opacity-0'} ${i === photoIndex && isHovering ? 'scale-105' : ''} transform-gpu`}
              />
            ))}
            {absolutePhotos.length > 1 && isHovering && (
              <div className="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1.5">
                {absolutePhotos.map((_, i) => (
                  <span
                    key={i}
                    className={`h-1 rounded-full transition-all duration-300 ${
                      i === photoIndex ? 'w-5 bg-white' : 'w-1.5 bg-white/60'
                    }`}
                  />
                ))}
              </div>
            )}
          </>
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

        {/* Guest count badge (bottom-right to leave the top-right for the wishlist heart) */}
        {accommodation.maxGuests != null && (
          <div className="absolute bottom-3 right-3 bg-white/95 backdrop-blur-sm rounded-lg px-2.5 py-1.5 shadow-sm flex items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-gray-500">
              <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
              <circle cx="9" cy="7" r="4" />
            </svg>
            <span className="text-xs font-medium text-gray-700">{accommodation.maxGuests}</span>
          </div>
        )}
      </Link>

      {/* Content */}
      <div className="p-5 flex flex-col flex-1">
        <div className="flex items-start justify-between gap-2 mb-1">
          <h3 className="font-semibold text-lg text-gray-900 group-hover:text-primary-600 transition-colors">
            {accommodation.title}
          </h3>
          {accommodation.averageRating != null && accommodation.reviewCount > 0 && (
            <RatingBadge
              rating={accommodation.averageRating}
              count={accommodation.reviewCount}
              showCount={false}
              className="flex-shrink-0 mt-0.5"
            />
          )}
        </div>

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

        <div className="mt-auto pt-3 border-t border-gray-100 flex items-center justify-between">
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
            className="inline-flex items-center gap-1.5 rounded-xl text-sm font-semibold text-white bg-primary-600 hover:bg-primary-700 h-10 px-5 transition-all hover:shadow-md hover:shadow-primary-200"
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
