import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAppDispatch } from '../../../store/hooks';
import { requestPasswordReset } from '../AuthSlice';
import AuthLayout from './AuthLayout';

const ForgotPasswordPage: React.FC = () => {
  const { t } = useTranslation();
  const dispatch = useAppDispatch();
  const [email, setEmail] = useState('');
  const [status, setStatus] = useState<'idle' | 'loading' | 'sent'>('idle');

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setStatus('loading');
    // The API answers 202 whether or not the address exists, so we always land on
    // the same neutral confirmation — that is exactly what prevents email probing.
    await dispatch(requestPasswordReset({ email }));
    setStatus('sent');
  };

  return (
    <AuthLayout>
      <div className="text-center mb-8">
        <div className="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 text-white shadow-lg shadow-primary-200 mb-5">
          <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
            <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
          </svg>
        </div>
        <h1 className="text-3xl font-bold text-gray-900 tracking-tight">
          {t('auth.forgotPassword.title')}
        </h1>
        <p className="text-gray-500 mt-2">{t('auth.forgotPassword.subtitle')}</p>
      </div>

      {status === 'sent' ? (
        <div className="bg-white rounded-3xl border border-gray-100 shadow-sm p-7 sm:p-8 space-y-5 text-center">
          <p className="text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-3">
            {t('auth.forgotPassword.sent')}
          </p>
          <Link to="/login" className="text-primary-700 font-medium hover:underline text-sm">
            {t('auth.backToLogin')}
          </Link>
        </div>
      ) : (
        <form onSubmit={handleSubmit} className="bg-white rounded-3xl border border-gray-100 shadow-sm p-7 sm:p-8 space-y-5">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">{t('auth.email')}</label>
            <div className="relative">
              <svg className="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <rect width="20" height="16" x="2" y="4" rx="2" />
                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
              </svg>
              <input
                type="email"
                required
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="you@example.com"
                autoComplete="email"
                className="w-full h-12 pl-10 pr-3 rounded-xl border border-gray-200 bg-gray-50 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 focus:bg-white transition-colors"
              />
            </div>
          </div>
          <button
            type="submit"
            disabled={status === 'loading'}
            className="w-full inline-flex items-center justify-center h-12 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 shadow-sm shadow-primary-200 disabled:opacity-60 transition-all"
          >
            {status === 'loading' ? t('auth.loading') : t('auth.forgotPassword.submit')}
          </button>
          <p className="text-sm text-center text-gray-500 pt-1">
            <Link to="/login" className="text-primary-700 font-medium hover:underline">{t('auth.backToLogin')}</Link>
          </p>
        </form>
      )}
    </AuthLayout>
  );
};

export default ForgotPasswordPage;
