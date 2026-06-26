import React, { useEffect, useRef, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { verifyEmail } from '../AuthSlice';
import { selectIsAuthenticated } from '../AuthSelectors';
import AuthLayout from './AuthLayout';

type VerifyStatus = 'verifying' | 'success' | 'error';

const VerifyEmailPage: React.FC = () => {
  const { t } = useTranslation();
  const dispatch = useAppDispatch();
  const [searchParams] = useSearchParams();
  const token = searchParams.get('token') ?? '';
  const isAuthenticated = useAppSelector(selectIsAuthenticated);

  const [status, setStatus] = useState<VerifyStatus>('verifying');
  // The link may be opened twice (e.g. by an email scanner then the user); guard so we
  // only consume the single-use token once per mount.
  const attempted = useRef(false);

  useEffect(() => {
    if (attempted.current) return;
    attempted.current = true;

    if (!token) {
      setStatus('error');
      return;
    }

    void (async () => {
      const result = await dispatch(verifyEmail({ token }));
      setStatus(verifyEmail.fulfilled.match(result) ? 'success' : 'error');
    })();
  }, [dispatch, token]);

  return (
    <AuthLayout>
      <div className="bg-white rounded-3xl border border-gray-100 shadow-sm p-7 sm:p-8 space-y-5 text-center">
        {status === 'verifying' && (
          <>
            <div className="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-primary-50 text-primary-600 mb-2">
              <svg className="animate-spin" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                <path d="M21 12a9 9 0 1 1-6.219-8.56" />
              </svg>
            </div>
            <h1 className="text-2xl font-bold text-gray-900">{t('auth.verifyEmail.verifying')}</h1>
          </>
        )}

        {status === 'success' && (
          <>
            <div className="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 mb-2">
              <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                <path d="M20 6 9 17l-5-5" />
              </svg>
            </div>
            <h1 className="text-2xl font-bold text-gray-900">{t('auth.verifyEmail.successTitle')}</h1>
            <p className="text-gray-500">{t('auth.verifyEmail.successText')}</p>
            <Link
              to={isAuthenticated ? '/account' : '/login'}
              className="inline-flex items-center justify-center h-12 px-6 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 shadow-sm shadow-primary-200 transition-all"
            >
              {isAuthenticated ? t('auth.verifyEmail.goToAccount') : t('auth.login')}
            </Link>
          </>
        )}

        {status === 'error' && (
          <>
            <div className="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-red-50 text-red-600 mb-2">
              <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                <circle cx="12" cy="12" r="10" />
                <path d="m15 9-6 6M9 9l6 6" />
              </svg>
            </div>
            <h1 className="text-2xl font-bold text-gray-900">{t('auth.verifyEmail.errorTitle')}</h1>
            <p className="text-gray-500">{t('auth.verifyEmail.errorText')}</p>
            <Link to={isAuthenticated ? '/account' : '/login'} className="text-primary-700 font-medium hover:underline text-sm">
              {t('auth.backToLogin')}
            </Link>
          </>
        )}
      </div>
    </AuthLayout>
  );
};

export default VerifyEmailPage;
