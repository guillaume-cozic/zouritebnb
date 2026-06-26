import React, { useState } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { useAppDispatch } from '../../../store/hooks';
import { resetPassword } from '../AuthSlice';
import AuthLayout from './AuthLayout';

const PASSWORD_MIN_LENGTH = 8;

const ResetPasswordPage: React.FC = () => {
  const { t } = useTranslation();
  const dispatch = useAppDispatch();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const token = searchParams.get('token') ?? '';

  const [password, setPassword] = useState('');
  const [confirm, setConfirm] = useState('');
  const [status, setStatus] = useState<'idle' | 'loading'>('idle');
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);

    if (password.length < PASSWORD_MIN_LENGTH) {
      setError(t('auth.resetPassword.tooShort'));
      return;
    }
    if (password !== confirm) {
      setError(t('auth.resetPassword.mismatch'));
      return;
    }

    setStatus('loading');
    const result = await dispatch(resetPassword({ token, password }));
    setStatus('idle');

    if (resetPassword.fulfilled.match(result)) {
      navigate('/login?reset=success');
    } else {
      setError((result.payload as string) ?? t('auth.resetPassword.error'));
    }
  };

  if (!token) {
    return (
      <AuthLayout>
        <div className="bg-white rounded-3xl border border-gray-100 shadow-sm p-7 sm:p-8 space-y-5 text-center">
          <p className="text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-3 py-3">
            {t('auth.resetPassword.missingToken')}
          </p>
          <Link to="/forgot-password" className="text-primary-700 font-medium hover:underline text-sm">
            {t('auth.forgotPassword.title')}
          </Link>
        </div>
      </AuthLayout>
    );
  }

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
          {t('auth.resetPassword.title')}
        </h1>
        <p className="text-gray-500 mt-2">{t('auth.resetPassword.subtitle')}</p>
      </div>

      <form onSubmit={handleSubmit} className="bg-white rounded-3xl border border-gray-100 shadow-sm p-7 sm:p-8 space-y-5">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">{t('auth.resetPassword.newPassword')}</label>
          <input
            type="password"
            required
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            placeholder="••••••••"
            autoComplete="new-password"
            className="w-full h-12 px-3 rounded-xl border border-gray-200 bg-gray-50 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 focus:bg-white transition-colors"
          />
          <p className="text-xs text-gray-400 mt-1.5">{t('auth.passwordHint')}</p>
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">{t('auth.resetPassword.confirmPassword')}</label>
          <input
            type="password"
            required
            value={confirm}
            onChange={(e) => setConfirm(e.target.value)}
            placeholder="••••••••"
            autoComplete="new-password"
            className="w-full h-12 px-3 rounded-xl border border-gray-200 bg-gray-50 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 focus:bg-white transition-colors"
          />
        </div>
        {error && (
          <p className="text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
            {error}
          </p>
        )}
        <button
          type="submit"
          disabled={status === 'loading'}
          className="w-full inline-flex items-center justify-center h-12 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 shadow-sm shadow-primary-200 disabled:opacity-60 transition-all"
        >
          {status === 'loading' ? t('auth.loading') : t('auth.resetPassword.submit')}
        </button>
        <p className="text-sm text-center text-gray-500 pt-1">
          <Link to="/login" className="text-primary-700 font-medium hover:underline">{t('auth.backToLogin')}</Link>
        </p>
      </form>
    </AuthLayout>
  );
};

export default ResetPasswordPage;
