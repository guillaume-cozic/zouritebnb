import React, { useRef, useState } from 'react';
import WizardNavigation from '../../../components/WizardNavigation';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { goToStep } from '../AccommodationSlice';
import { selectCurrentAccommodation } from '../AccommodationSelectors';
import api from '../../../services/api';

type PhotoStatus = 'pending' | 'uploading' | 'success' | 'error';

interface PhotoItem {
  id: string;
  name: string;
  url: string;
  status: PhotoStatus;
  progress: number;
  error?: string;
}

function PhotosStep() {
  const dispatch = useAppDispatch();
  const accommodation = useAppSelector(selectCurrentAccommodation);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const [photos, setPhotos] = useState<PhotoItem[]>([]);
  const [isDragging, setIsDragging] = useState(false);

  const updatePhoto = (id: string, updates: Partial<PhotoItem>) => {
    setPhotos((prev) => prev.map((p) => (p.id === id ? { ...p, ...updates } : p)));
  };

  const uploadSinglePhoto = async (photo: PhotoItem) => {
    if (!accommodation?.id) return;

    updatePhoto(photo.id, { status: 'uploading', progress: 0 });

    const formData = new FormData();
    formData.append('file', await fetch(photo.url).then((r) => r.blob()), photo.name);

    try {
      await api.post(`/api/accommodations/${accommodation.id}/photos`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
        onUploadProgress: (e) => {
          const progress = e.total ? Math.round((e.loaded / e.total) * 100) : 0;
          updatePhoto(photo.id, { progress });
        },
      });
      updatePhoto(photo.id, { status: 'success', progress: 100 });
    } catch (err: any) {
      updatePhoto(photo.id, {
        status: 'error',
        error: err.response?.data?.detail || "Erreur lors de l'upload",
      });
    }
  };

  const addFiles = (files: FileList) => {
    const newPhotos: PhotoItem[] = Array.from(files).map((file) => ({
      id: `${Date.now()}-${Math.random().toString(36).slice(2)}`,
      name: file.name,
      url: URL.createObjectURL(file),
      status: 'pending' as PhotoStatus,
      progress: 0,
    }));

    setPhotos((prev) => [...prev, ...newPhotos]);

    // Upload each photo one by one
    newPhotos.reduce(
      (chain, photo) => chain.then(() => uploadSinglePhoto(photo)),
      Promise.resolve()
    );

    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files) addFiles(e.target.files);
  };

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(false);
    if (e.dataTransfer.files) addFiles(e.dataTransfer.files);
  };

  const retryUpload = (photo: PhotoItem) => {
    uploadSinglePhoto(photo);
  };

  const removePhoto = (id: string) => {
    setPhotos((prev) => {
      const photo = prev.find((p) => p.id === id);
      if (photo) URL.revokeObjectURL(photo.url);
      return prev.filter((p) => p.id !== id);
    });
  };

  const handleFinish = () => {
    dispatch(goToStep('success'));
  };

  const successCount = photos.filter((p) => p.status === 'success').length;
  const hasUploading = photos.some((p) => p.status === 'uploading');

  return (
    <div className="space-y-8">
      <div className="space-y-1">
        <p className="text-sm text-gray-500">
          Ajoutez des photos pour mettre en valeur votre hébergement. Chaque photo est envoyée individuellement.
        </p>
      </div>

      {/* Drop zone */}
      <div
        onDragOver={(e) => { e.preventDefault(); setIsDragging(true); }}
        onDragLeave={() => setIsDragging(false)}
        onDrop={handleDrop}
        onClick={() => fileInputRef.current?.click()}
        className={`
          relative border-2 border-dashed rounded-2xl p-10 text-center cursor-pointer
          transition-all duration-300
          ${isDragging
            ? 'border-purple-400 bg-purple-50 scale-[1.02]'
            : 'border-gray-200 bg-gray-50 hover:border-purple-300 hover:bg-purple-50/50'
          }
        `}
      >
        <div className="flex flex-col items-center gap-3">
          <div className={`
            w-16 h-16 rounded-2xl flex items-center justify-center transition-all duration-300
            ${isDragging ? 'bg-purple-200 text-purple-600' : 'bg-gray-200 text-gray-400'}
          `}>
            <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={1.5}>
              <path strokeLinecap="round" strokeLinejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
            </svg>
          </div>
          <div>
            <p className="text-sm font-semibold text-gray-700">
              Glissez vos photos ici
            </p>
            <p className="text-xs text-gray-400 mt-1">
              ou cliquez pour parcourir — JPEG, PNG, WebP (max. 20)
            </p>
          </div>
        </div>

        <input
          ref={fileInputRef}
          type="file"
          accept="image/jpeg,image/png,image/webp"
          multiple
          onChange={handleFileChange}
          className="hidden"
        />
      </div>

      {/* Photos grid */}
      {photos.length > 0 && (
        <div className="space-y-3">
          <div className="flex items-center justify-between">
            <h4 className="text-sm font-semibold text-gray-700">
              Photos
            </h4>
            <span className="text-xs font-medium text-purple-600 bg-purple-50 px-2.5 py-1 rounded-full">
              {successCount} / 20
            </span>
          </div>

          <div className="grid grid-cols-3 gap-3">
            {photos.map((photo) => (
              <div key={photo.id} className="relative rounded-xl overflow-hidden border border-gray-100 shadow-sm aspect-square group">
                {/* Preview image */}
                <img
                  src={photo.url}
                  alt={photo.name}
                  className={`w-full h-full object-cover transition-all duration-300 ${
                    photo.status === 'error' ? 'opacity-50 grayscale' : ''
                  }`}
                />

                {/* Uploading overlay */}
                {photo.status === 'uploading' && (
                  <div className="absolute inset-0 bg-black/40 flex flex-col items-center justify-center gap-2">
                    <svg className="animate-spin h-6 w-6 text-white" fill="none" viewBox="0 0 24 24">
                      <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                      <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                    <span className="text-white text-xs font-semibold">{photo.progress}%</span>
                    {/* Progress bar */}
                    <div className="absolute bottom-0 left-0 right-0 h-1.5 bg-black/20">
                      <div
                        className="h-full bg-purple-400 transition-all duration-300 ease-out"
                        style={{ width: `${photo.progress}%` }}
                      />
                    </div>
                  </div>
                )}

                {/* Pending overlay */}
                {photo.status === 'pending' && (
                  <div className="absolute inset-0 bg-black/20 flex items-center justify-center">
                    <span className="text-white text-xs font-medium bg-black/40 px-2 py-1 rounded-full">En attente</span>
                  </div>
                )}

                {/* Success badge */}
                {photo.status === 'success' && (
                  <div className="absolute top-2 right-2 w-6 h-6 rounded-full bg-emerald-500 flex items-center justify-center shadow">
                    <svg className="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={2.5}>
                      <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                  </div>
                )}

                {/* Error overlay */}
                {photo.status === 'error' && (
                  <div className="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-black/30">
                    <div className="w-8 h-8 rounded-full bg-red-500 flex items-center justify-center shadow">
                      <svg className="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={2.5}>
                        <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </div>
                    <p className="text-white text-xs font-medium text-center px-2 drop-shadow">
                      {photo.error || 'Échec'}
                    </p>
                    <button
                      type="button"
                      onClick={(e) => { e.stopPropagation(); retryUpload(photo); }}
                      className="text-xs font-semibold text-white bg-white/20 hover:bg-white/30 backdrop-blur-sm px-3 py-1 rounded-full transition-colors"
                    >
                      Réessayer
                    </button>
                  </div>
                )}

                {/* Remove button (visible on hover, except when uploading) */}
                {photo.status !== 'uploading' && (
                  <button
                    type="button"
                    onClick={(e) => { e.stopPropagation(); removePhoto(photo.id); }}
                    className="absolute top-2 left-2 w-6 h-6 rounded-full bg-black/50 hover:bg-black/70 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                  >
                    <svg className="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={3}>
                      <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                )}
              </div>
            ))}
          </div>
        </div>
      )}

      <WizardNavigation
        onBack={() => dispatch(goToStep('address'))}
        onSkip={successCount === 0 ? handleFinish : undefined}
        skipLabel="Plus tard"
        submitLabel={hasUploading ? 'Upload en cours...' : 'Terminer'}
        isLoading={hasUploading}
        isSubmit={false}
        onClick={!hasUploading ? handleFinish : undefined}
      />
    </div>
  );
}

export default PhotosStep;
