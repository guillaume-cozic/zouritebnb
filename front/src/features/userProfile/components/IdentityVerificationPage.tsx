import React, { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { selectAuthUser } from '../../auth/AuthSelectors';
import {
  fetchVerificationStatus,
  submitIdentityVerification,
} from '../UserProfileSlice';
import {
  selectVerificationStatus,
  selectVerificationOperationStatus,
  selectVerificationUploadProgress,
  selectVerificationError,
  selectVerificationVerifiedAt,
} from '../UserProfileSelectors';
import { IdentityDocumentType } from '../UserProfileTypes';
import VerificationBadge from './VerificationBadge';
import { Button } from '../../../components/ui';

const DOCUMENT_TYPES: IdentityDocumentType[] = ['passport', 'id_card', 'driving_license'];

const FileField: React.FC<{
  label: string;
  hint: string;
  file: File | null;
  onSelect: (file: File | null) => void;
}> = ({ label, hint, file, onSelect }) => (
  <label className="flex flex-col gap-2 rounded-xl border border-dashed border-gray-300 p-4 cursor-pointer hover:border-primary-400 transition-colors">
    <span className="text-sm font-medium text-gray-900">{label}</span>
    <span className="text-xs text-gray-500">{file ? file.name : hint}</span>
    <input
      type="file"
      accept="image/jpeg,image/png,image/webp"
      className="hidden"
      onChange={(e) => onSelect(e.target.files?.[0] ?? null)}
    />
  </label>
);

const IdentityVerificationPage: React.FC = () => {
  const { t } = useTranslation();
  const dispatch = useAppDispatch();
  const user = useAppSelector(selectAuthUser);
  const status = useAppSelector(selectVerificationStatus);
  const operationStatus = useAppSelector(selectVerificationOperationStatus);
  const uploadProgress = useAppSelector(selectVerificationUploadProgress);
  const error = useAppSelector(selectVerificationError);
  const verifiedAt = useAppSelector(selectVerificationVerifiedAt);

  const [documentType, setDocumentType] = useState<IdentityDocumentType>('passport');
  const [documentFile, setDocumentFile] = useState<File | null>(null);
  const [selfieFile, setSelfieFile] = useState<File | null>(null);

  useEffect(() => {
    if (user) {
      dispatch(fetchVerificationStatus(user.id));
    }
  }, [dispatch, user]);

  const submitting = operationStatus === 'loading';
  const analyzing = submitting && uploadProgress === 100;
  const canSubmit = !!documentFile && !!selfieFile && !submitting && status !== 'verified';

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!user || !documentFile || !selfieFile) return;
    dispatch(
      submitIdentityVerification({
        userId: user.id,
        documentType,
        documentFile,
        selfieFile,
      })
    );
  };

  return (
    <div className="max-w-2xl mx-auto px-4 sm:px-6 py-10">
      <div className="flex items-center justify-between mb-2">
        <h1 className="text-2xl font-bold text-gray-900">{t('userProfile.verification.title')}</h1>
        <VerificationBadge status={status} />
      </div>
      <p className="text-sm text-gray-500 mb-8">{t('userProfile.verification.subtitle')}</p>

      {status === 'verified' ? (
        <div className="rounded-2xl border border-green-200 bg-green-50 p-6 text-center">
          <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-green-600 text-white">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
              <path d="M20 6 9 17l-5-5" />
            </svg>
          </div>
          <p className="text-lg font-semibold text-green-800">{t('userProfile.verification.verifiedTitle')}</p>
          {verifiedAt && (
            <p className="mt-1 text-sm text-green-700">
              {t('userProfile.verification.verifiedOn', {
                date: new Date(verifiedAt).toLocaleDateString(),
              })}
            </p>
          )}
        </div>
      ) : (
        <form onSubmit={handleSubmit} className="space-y-5 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
          <div>
            <label className="mb-1.5 block text-sm font-medium text-gray-900">
              {t('userProfile.verification.documentTypeLabel')}
            </label>
            <select
              value={documentType}
              onChange={(e) => setDocumentType(e.target.value as IdentityDocumentType)}
              disabled={submitting}
              className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
            >
              {DOCUMENT_TYPES.map((type) => (
                <option key={type} value={type}>
                  {t(`userProfile.verification.documentTypes.${type}`)}
                </option>
              ))}
            </select>
          </div>

          <FileField
            label={t('userProfile.verification.documentLabel')}
            hint={t('userProfile.verification.documentHint')}
            file={documentFile}
            onSelect={setDocumentFile}
          />
          <FileField
            label={t('userProfile.verification.selfieLabel')}
            hint={t('userProfile.verification.selfieHint')}
            file={selfieFile}
            onSelect={setSelfieFile}
          />

          {submitting && (
            <div>
              <div className="h-2 w-full overflow-hidden rounded-full bg-gray-100">
                <div
                  className="h-full rounded-full bg-primary-600 transition-all"
                  style={{ width: `${uploadProgress}%` }}
                />
              </div>
              <p className="mt-2 text-center text-sm text-gray-500">
                {analyzing
                  ? t('userProfile.verification.analyzing')
                  : t('userProfile.verification.uploading', { progress: uploadProgress })}
              </p>
            </div>
          )}

          {operationStatus === 'failed' && error && (
            <p className="text-sm text-red-600">{error}</p>
          )}

          <Button type="submit" disabled={!canSubmit} className="w-full">
            {t('userProfile.verification.submit')}
          </Button>
        </form>
      )}
    </div>
  );
};

export default IdentityVerificationPage;
