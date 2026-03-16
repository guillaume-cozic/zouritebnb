import React, { useEffect, useRef, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { fetchAccommodation, addPhoto } from '../AccommodationSlice';
import { selectCurrentAccommodation, selectAccommodationStatus, selectAccommodationError } from '../AccommodationSelectors';
import EditLayout, { SECTIONS } from './EditLayout';

const API_BASE = process.env.REACT_APP_API_URL || 'http://localhost:8080';

const AccommodationPhotosPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const { t } = useTranslation();
  const dispatch = useAppDispatch();
  const accommodation = useAppSelector(selectCurrentAccommodation);
  const status = useAppSelector(selectAccommodationStatus);
  const error = useAppSelector(selectAccommodationError);

  const fileInputRef = useRef<HTMLInputElement>(null);
  const [dragOver, setDragOver] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [uploadSuccess, setUploadSuccess] = useState(false);

  useEffect(() => {
    if (id) dispatch(fetchAccommodation(id));
  }, [dispatch, id]);

  const handleUpload = async (files: FileList | null) => {
    if (!files || !accommodation?.id) return;
    setUploading(true);
    setUploadSuccess(false);
    try {
      for (let i = 0; i < files.length; i++) {
        await dispatch(addPhoto({ id: accommodation.id, file: files[i] })).unwrap();
      }
      setUploadSuccess(true);
      setTimeout(() => setUploadSuccess(false), 3000);
      // Re-fetch to get updated thumbnailUrl
      dispatch(fetchAccommodation(accommodation.id));
    } catch {
      // error is handled via redux state
    } finally {
      setUploading(false);
      if (fileInputRef.current) fileInputRef.current.value = '';
    }
  };

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    setDragOver(false);
    handleUpload(e.dataTransfer.files);
  };

  // Loading
  if (status === 'loading' && !accommodation) {
    return (
      <main className="min-h-screen py-8">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="animate-pulse space-y-6">
            <div className="h-8 bg-gray-200 rounded-lg w-1/3" />
            <div className="h-64 bg-gray-100 rounded-lg" />
          </div>
        </div>
      </main>
    );
  }

  if (!accommodation) {
    return (
      <main className="min-h-screen py-8">
        <div className="max-w-7xl mx-auto px-4 text-center py-20">
          <p className="text-red-500 mb-4">{error || t('edit.notFound')}</p>
          <Link to="/" className="text-blue-600 hover:underline">{t('detail.backToHome')}</Link>
        </div>
      </main>
    );
  }

  return (
    <EditLayout
      accommodationId={accommodation.id!}
      accommodationTitle={accommodation.title ?? ''}
      activeSection="photos"
      error={error}
    >
      <div className="space-y-6">
        {/* Header card */}
        <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sm:p-8">
          <div className="flex items-center justify-between mb-6">
            <div className="flex items-center gap-3">
              <div className="flex items-center justify-center w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600">
                {SECTIONS[6].icon}
              </div>
              <h2 className="text-lg font-semibold">{t('edit.section.photos')}</h2>
            </div>
            {uploadSuccess && (
              <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border bg-emerald-50 text-emerald-600 border-emerald-100">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><path d="M20 6L9 17l-5-5" /></svg>
                {t('photos.uploadSuccess')}
              </span>
            )}
          </div>

          {/* Upload zone */}
          <div
            onDragOver={(e) => { e.preventDefault(); setDragOver(true); }}
            onDragLeave={() => setDragOver(false)}
            onDrop={handleDrop}
            onClick={() => fileInputRef.current?.click()}
            className={`relative flex flex-col items-center justify-center rounded-2xl border-2 border-dashed p-10 cursor-pointer transition-all ${
              dragOver
                ? 'border-blue-400 bg-blue-50/50'
                : 'border-gray-200 bg-gray-50/50 hover:border-blue-300 hover:bg-blue-50/30'
            }`}
          >
            <input
              ref={fileInputRef}
              type="file"
              accept="image/jpeg,image/png,image/webp"
              multiple
              className="hidden"
              onChange={(e) => handleUpload(e.target.files)}
            />

            {uploading ? (
              <>
                <svg className="animate-spin mb-3 text-blue-500" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M21 12a9 9 0 11-6.219-8.56" /></svg>
                <p className="text-sm font-medium text-blue-600">{t('photos.uploading')}</p>
              </>
            ) : (
              <>
                <div className="flex items-center justify-center w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-500 mb-4">
                  <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4" /><polyline points="17 8 12 3 7 8" /><line x1="12" y1="3" x2="12" y2="15" /></svg>
                </div>
                <p className="text-sm font-medium text-gray-700 mb-1">{t('photos.dropzone')}</p>
                <p className="text-xs text-gray-400">{t('photos.formats')}</p>
              </>
            )}
          </div>
        </div>

        {/* Photo gallery */}
        {accommodation.photos && accommodation.photos.length > 0 && (
          <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sm:p-8">
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-sm font-semibold text-gray-700">
                {t('photos.gallery')}
              </h3>
              <span className="text-sm text-gray-400">{accommodation.photos.length} / 20</span>
            </div>
            <div className="grid grid-cols-2 sm:grid-cols-3 gap-4">
              {accommodation.photos.map((photo) => (
                <div key={photo.id} className="relative group aspect-[3/2] rounded-xl overflow-hidden bg-gray-100">
                  <img
                    src={`${API_BASE}${photo.url}`}
                    alt={accommodation.title}
                    className="absolute inset-0 w-full h-full object-cover"
                  />
                </div>
              ))}
            </div>
          </div>
        )}
      </div>
    </EditLayout>
  );
};

export default AccommodationPhotosPage;
