import React, { useEffect, useRef, useState, useCallback } from 'react';
import { useParams, Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { fetchAccommodation, uploadPhotos, reorderPhotos, deletePhoto } from '../AccommodationSlice';
import { selectCurrentAccommodation, selectAccommodationStatus, selectAccommodationError } from '../AccommodationSelectors';
import EditLayout, { SECTIONS } from './EditLayout';
import { extractErrorMessage } from '../../../services/errors';

const API_BASE = process.env.REACT_APP_API_URL || 'http://localhost:8080';

const AccommodationPhotosPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const { t } = useTranslation();
  const dispatch = useAppDispatch();
  const accommodation = useAppSelector(selectCurrentAccommodation);
  const status = useAppSelector(selectAccommodationStatus);
  const error = useAppSelector(selectAccommodationError);

  const fileInputRef = useRef<HTMLInputElement>(null);
  const [dragOverUpload, setDragOverUpload] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [uploadSuccess, setUploadSuccess] = useState(false);
  const [uploadError, setUploadError] = useState<string | null>(null);

  // Local photos state for drag reorder
  const [localPhotos, setLocalPhotos] = useState<{ id: string; url: string }[]>([]);
  const [dragIdx, setDragIdx] = useState<number | null>(null);
  const [dragOverIdx, setDragOverIdx] = useState<number | null>(null);

  useEffect(() => {
    if (id) dispatch(fetchAccommodation(id));
  }, [dispatch, id]);

  // Sync local photos from accommodation
  useEffect(() => {
    if (accommodation?.photos) {
      setLocalPhotos(accommodation.photos);
    }
  }, [accommodation?.photos]);

  const handleUpload = async (files: FileList | null) => {
    if (!files || !accommodation?.id) return;
    setUploading(true);
    setUploadSuccess(false);
    setUploadError(null);
    try {
      await dispatch(uploadPhotos({ id: accommodation.id, files: Array.from(files) })).unwrap();
      setUploadSuccess(true);
      setTimeout(() => setUploadSuccess(false), 3000);
    } catch (err) {
      setUploadError(typeof err === 'string' ? err : extractErrorMessage(err, t('photos.uploadError')));
    } finally {
      setUploading(false);
      if (fileInputRef.current) fileInputRef.current.value = '';
    }
  };

  const handleDropUpload = (e: React.DragEvent) => {
    e.preventDefault();
    setDragOverUpload(false);
    // Only handle file drops, not reorder drops
    if (e.dataTransfer.files.length > 0) {
      handleUpload(e.dataTransfer.files);
    }
  };

  // --- Drag & drop reorder ---
  const handleDragStart = (idx: number) => {
    setDragIdx(idx);
  };

  const handleDragOver = (e: React.DragEvent, idx: number) => {
    e.preventDefault();
    if (dragIdx !== null && dragIdx !== idx) {
      setDragOverIdx(idx);
    }
  };

  const handleDragEnd = useCallback(async () => {
    if (dragIdx === null || dragOverIdx === null || dragIdx === dragOverIdx) {
      setDragIdx(null);
      setDragOverIdx(null);
      return;
    }

    const reordered = [...localPhotos];
    const [moved] = reordered.splice(dragIdx, 1);
    reordered.splice(dragOverIdx, 0, moved);
    setLocalPhotos(reordered);
    setDragIdx(null);
    setDragOverIdx(null);

    if (accommodation?.id) {
      await dispatch(reorderPhotos({
        id: accommodation.id,
        photoIds: reordered.map((p) => p.id),
      }));
    }
  }, [dragIdx, dragOverIdx, localPhotos, accommodation?.id, dispatch]);

  const handleDelete = async (photoId: string) => {
    if (!accommodation?.id) return;
    setLocalPhotos((prev) => prev.filter((p) => p.id !== photoId));
    try {
      await dispatch(deletePhoto({ id: accommodation.id, photoId })).unwrap();
    } catch {
      // Re-sync from store on error
      if (accommodation.photos) setLocalPhotos(accommodation.photos);
    }
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
          <Link to="/" className="text-primary-600 hover:underline">{t('detail.backToHome')}</Link>
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
        {/* Upload card */}
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

          {uploadError && (
            <div className="mb-4 flex items-center gap-3 rounded-xl bg-red-50 border border-red-100 p-4">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="text-red-500 flex-shrink-0"><circle cx="12" cy="12" r="10" /><path d="m15 9-6 6" /><path d="m9 9 6 6" /></svg>
              <p className="text-sm text-red-700">{uploadError}</p>
            </div>
          )}

          {/* Upload zone */}
          <div
            onDragOver={(e) => { e.preventDefault(); setDragOverUpload(true); }}
            onDragLeave={() => setDragOverUpload(false)}
            onDrop={handleDropUpload}
            onClick={() => fileInputRef.current?.click()}
            className={`relative flex flex-col items-center justify-center rounded-2xl border-2 border-dashed p-10 cursor-pointer transition-all ${
              dragOverUpload
                ? 'border-primary-400 bg-primary-50/50'
                : 'border-gray-200 bg-gray-50/50 hover:border-primary-300 hover:bg-primary-50/30'
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
                <svg className="animate-spin mb-3 text-primary-500" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M21 12a9 9 0 11-6.219-8.56" /></svg>
                <p className="text-sm font-medium text-primary-600">{t('photos.uploading')}</p>
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

        {/* Photo gallery with drag & drop reorder */}
        {localPhotos.length > 0 && (
          <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sm:p-8">
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-sm font-semibold text-gray-700">
                {t('photos.gallery')}
              </h3>
              <div className="flex items-center gap-3">
                <span className="text-xs text-gray-400">{t('photos.dragToReorder')}</span>
                <span className="text-sm text-gray-400">{localPhotos.length} / 20</span>
              </div>
            </div>
            <div className="grid grid-cols-2 sm:grid-cols-3 gap-4">
              {localPhotos.map((photo, idx) => (
                <div
                  key={photo.id}
                  draggable
                  onDragStart={() => handleDragStart(idx)}
                  onDragOver={(e) => handleDragOver(e, idx)}
                  onDragEnd={handleDragEnd}
                  className={`relative group aspect-[3/2] rounded-xl overflow-hidden bg-gray-100 cursor-grab active:cursor-grabbing transition-all ${
                    dragIdx === idx ? 'opacity-40 scale-95' : ''
                  } ${
                    dragOverIdx === idx ? 'ring-2 ring-primary-400 ring-offset-2' : ''
                  }`}
                >
                  <img
                    src={`${API_BASE}${photo.url}`}
                    alt={`${accommodation.title} - ${idx + 1}`}
                    className="absolute inset-0 w-full h-full object-cover pointer-events-none"
                  />
                  {/* Position badge */}
                  <div className="absolute top-2 left-2 flex items-center justify-center w-7 h-7 rounded-lg bg-black/50 text-white text-xs font-bold backdrop-blur-sm">
                    {idx + 1}
                  </div>
                  {/* Delete button */}
                  <button
                    type="button"
                    onClick={(e) => { e.stopPropagation(); handleDelete(photo.id); }}
                    className="absolute top-2 right-2 flex items-center justify-center w-7 h-7 rounded-lg bg-black/50 hover:bg-red-600 text-white backdrop-blur-sm opacity-0 group-hover:opacity-100 transition-all"
                    title={t('photos.delete')}
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><path d="M18 6L6 18" /><path d="M6 6l12 12" /></svg>
                  </button>
                  {/* Drag handle hint */}
                  <div className="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors flex items-center justify-center pointer-events-none">
                    <div className="opacity-0 group-hover:opacity-100 transition-opacity">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="drop-shadow-lg"><circle cx="9" cy="5" r="1" /><circle cx="15" cy="5" r="1" /><circle cx="9" cy="12" r="1" /><circle cx="15" cy="12" r="1" /><circle cx="9" cy="19" r="1" /><circle cx="15" cy="19" r="1" /></svg>
                    </div>
                  </div>
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
