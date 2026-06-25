import React, { useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import Footer from '../../../components/Footer';
import { fetchWishlist } from '../WishlistSlice';
import { selectWishlistItems, selectWishlistStatus } from '../WishlistSelectors';
import WishlistButton from './WishlistButton';

const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:8080';

const WishlistPage: React.FC = () => {
  const { t } = useTranslation();
  const dispatch = useAppDispatch();
  const items = useAppSelector(selectWishlistItems);
  const status = useAppSelector(selectWishlistStatus);

  useEffect(() => {
    dispatch(fetchWishlist());
  }, [dispatch]);

  return (
    <>
      <main className="min-h-screen py-8">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center gap-3 mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="currentColor" className="text-rose-500">
              <path d="M2 9.5a5.5 5.5 0 0 1 9.591-3.676.56.56 0 0 0 .818 0A5.49 5.49 0 0 1 22 9.5c0 2.29-1.5 4-3 5.5l-5.492 5.313a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5" />
            </svg>
            <h1 className="text-3xl font-bold">{t('wishlist.title')}</h1>
          </div>

          {status === 'loading' && items.length === 0 ? (
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
              {[0, 1, 2].map((i) => (
                <div key={i} className="animate-pulse rounded-2xl border border-gray-100 overflow-hidden">
                  <div className="aspect-video bg-gray-200" />
                  <div className="p-5 space-y-3">
                    <div className="h-5 bg-gray-200 rounded w-2/3" />
                    <div className="h-4 bg-gray-100 rounded w-1/3" />
                  </div>
                </div>
              ))}
            </div>
          ) : items.length === 0 ? (
            <div className="text-center py-20">
              <div className="w-16 h-16 mx-auto mb-4 rounded-full bg-rose-50 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-rose-300">
                  <path d="M2 9.5a5.5 5.5 0 0 1 9.591-3.676.56.56 0 0 0 .818 0A5.49 5.49 0 0 1 22 9.5c0 2.29-1.5 4-3 5.5l-5.492 5.313a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5" />
                </svg>
              </div>
              <p className="text-gray-500 mb-4">{t('wishlist.empty')}</p>
              <Link to="/accommodations" className="inline-flex items-center justify-center h-11 rounded-xl px-6 text-sm font-semibold text-white bg-primary-600 hover:bg-primary-700 transition-colors">
                {t('wishlist.browse')}
              </Link>
            </div>
          ) : (
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
              {items.map((item) => (
                <div
                  key={item.accommodationId}
                  className="relative flex flex-col rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden group hover:shadow-xl hover:shadow-gray-200/50 transition-all duration-300 hover:-translate-y-1"
                >
                  <Link to={`/accommodations/${item.accommodationId}`} className="block aspect-video relative overflow-hidden">
                    {item.photoUrl ? (
                      <img
                        src={`${API_BASE}${item.photoUrl}`}
                        alt={item.title ?? ''}
                        className="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                      />
                    ) : (
                      <div className="absolute inset-0 bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" className="h-14 w-14 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                      </div>
                    )}
                  </Link>
                  <WishlistButton accommodationId={item.accommodationId} className="absolute top-3 right-3 z-10" />

                  <div className="p-5 flex flex-col flex-1">
                    <Link to={`/accommodations/${item.accommodationId}`} className="font-semibold text-lg text-gray-900 group-hover:text-primary-600 transition-colors">
                      {item.title}
                    </Link>
                    <div className="flex items-center text-sm text-gray-500 mt-1">
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="mr-1 text-gray-400 flex-shrink-0">
                        <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0" />
                        <circle cx="12" cy="10" r="3" />
                      </svg>
                      {[item.city, item.country].filter(Boolean).join(', ') || '—'}
                    </div>
                    {item.price != null && (
                      <p className="mt-auto pt-3 text-xl font-bold text-gray-900">
                        {item.price}{' '}€
                        <span className="text-sm font-normal text-gray-500 ml-1">/ {t('homepage.night')}</span>
                      </p>
                    )}
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </main>
      <Footer />
    </>
  );
};

export default WishlistPage;
