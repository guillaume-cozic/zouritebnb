import React, { useRef, useState } from 'react';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { addPhoto, goToStep } from '../AccommodationSlice';
import {
  selectCurrentAccommodation,
  selectPhotoUploadStatus,
  selectAccommodationError,
} from '../AccommodationSelectors';

function PhotosStep() {
  const dispatch = useAppDispatch();
  const accommodation = useAppSelector(selectCurrentAccommodation);
  const uploadStatus = useAppSelector(selectPhotoUploadStatus);
  const error = useAppSelector(selectAccommodationError);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const [uploadedFiles, setUploadedFiles] = useState<{ name: string; url: string }[]>([]);
  const [isDragging, setIsDragging] = useState(false);

  const processFiles = async (files: FileList) => {
    if (!accommodation?.id) return;

    const fileArray = Array.from(files);

    for (const file of fileArray) {
      const fileName = file.name;
      const previewUrl = URL.createObjectURL(file);
      const result = await dispatch(addPhoto({ id: accommodation.id, file }));
      if (addPhoto.fulfilled.match(result)) {
        setUploadedFiles((prev) => [...prev, { name: fileName, url: previewUrl }]);
      } else {
        URL.revokeObjectURL(previewUrl);
      }
    }

    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files) processFiles(e.target.files);
  };

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(false);
    if (e.dataTransfer.files) processFiles(e.dataTransfer.files);
  };

  const handleFinish = () => {
    dispatch(goToStep('success'));
  };

  return (
    <div className="space-y-8">
      <div className="space-y-1">
        <p className="text-sm text-gray-500">
          Ajoutez des photos pour mettre en valeur votre hébergement. Les voyageurs adorent les annonces avec de belles photos.
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

      {/* Upload status */}
      {uploadStatus === 'loading' && (
        <div className="flex items-center justify-center gap-3 py-3 px-4 rounded-xl bg-purple-50 border border-purple-100">
          <svg className="animate-spin h-5 w-5 text-purple-500" fill="none" viewBox="0 0 24 24">
            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
          <span className="text-sm font-medium text-purple-700">Upload en cours...</span>
        </div>
      )}

      {/* Uploaded files list */}
      {uploadedFiles.length > 0 && (
        <div className="space-y-3">
          <div className="flex items-center justify-between">
            <h4 className="text-sm font-semibold text-gray-700">
              Photos ajoutées
            </h4>
            <span className="text-xs font-medium text-purple-600 bg-purple-50 px-2.5 py-1 rounded-full">
              {uploadedFiles.length} / 20
            </span>
          </div>
          <div className="grid grid-cols-3 gap-3">
            {uploadedFiles.map((photo, i) => (
              <div key={i} className="relative group rounded-xl overflow-hidden border border-gray-100 shadow-sm aspect-square">
                <img
                  src={photo.url}
                  alt={photo.name}
                  className="w-full h-full object-cover"
                />
                <div className="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-all duration-200" />
                <div className="absolute top-2 right-2 w-6 h-6 rounded-full bg-emerald-500 flex items-center justify-center shadow">
                  <svg className="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={2.5}>
                    <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                  </svg>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {error && (
        <div className="flex items-center gap-3 rounded-xl bg-red-50 border border-red-100 p-4">
          <svg className="w-5 h-5 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clipRule="evenodd" />
          </svg>
          <p className="text-sm text-red-700">{error}</p>
        </div>
      )}

      <button
        type="button"
        onClick={handleFinish}
        className="
          w-full py-4 px-6 rounded-xl text-sm font-semibold text-white
          bg-gradient-to-r from-violet-500 to-purple-600
          hover:from-violet-600 hover:to-purple-700
          focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500
          shadow-lg shadow-purple-200 hover:shadow-xl hover:shadow-purple-300
          transform hover:-translate-y-0.5 active:translate-y-0
          transition-all duration-200
        "
      >
        <span className="flex items-center justify-center gap-2">
          {uploadedFiles.length > 0 ? 'Terminer' : 'Passer cette étape'}
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={2}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
          </svg>
        </span>
      </button>
    </div>
  );
}

export default PhotosStep;
