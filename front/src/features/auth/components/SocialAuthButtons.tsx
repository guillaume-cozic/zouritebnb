import React, { useCallback, useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useAppDispatch } from '../../../store/hooks';
import { socialLogin } from '../AuthSlice';
import {
  APPLE_CLIENT_ID,
  FACEBOOK_APP_ID,
  GOOGLE_CLIENT_ID,
  renderGoogleButton,
  signInWithApple,
  signInWithFacebook,
  SocialProvider,
} from '../../../services/socialAuth';

interface SocialAuthButtonsProps {
  /** Called once the API session is established (same as a classic login). */
  onSuccess: () => void;
}

const outlineButtonClass =
  'w-full inline-flex items-center justify-center gap-2.5 h-12 rounded-xl text-sm font-semibold border transition-colors disabled:opacity-60';

/**
 * Provider buttons shown on the login and register pages. A provider appears
 * only when its client id is configured; with none configured the whole block
 * (divider included) renders nothing.
 */
const SocialAuthButtons: React.FC<SocialAuthButtonsProps> = ({ onSuccess }) => {
  const { t, i18n } = useTranslation();
  const dispatch = useAppDispatch();
  const googleContainerRef = useRef<HTMLDivElement | null>(null);
  const [busy, setBusy] = useState(false);

  const authenticate = useCallback(
    async (provider: SocialProvider, token: string) => {
      const result = await dispatch(socialLogin({ provider, token }));
      if (socialLogin.fulfilled.match(result)) {
        onSuccess();
      }
    },
    [dispatch, onSuccess]
  );

  useEffect(() => {
    if (!GOOGLE_CLIENT_ID || !googleContainerRef.current) return;
    renderGoogleButton(googleContainerRef.current, i18n.language, (idToken) => {
      void authenticate('google', idToken);
    }).catch(() => {
      // SDK unreachable (offline, blocked): the button simply stays absent.
    });
  }, [authenticate, i18n.language]);

  const handleProviderClick = async (provider: 'apple' | 'facebook') => {
    setBusy(true);
    try {
      const token = provider === 'apple' ? await signInWithApple() : await signInWithFacebook();
      await authenticate(provider, token);
    } catch {
      // Popup closed or SDK failure: nothing to authenticate, keep the page as is.
    } finally {
      setBusy(false);
    }
  };

  if (!GOOGLE_CLIENT_ID && !APPLE_CLIENT_ID && !FACEBOOK_APP_ID) {
    return null;
  }

  return (
    <div className="space-y-3">
      <div className="flex items-center gap-3">
        <span className="h-px flex-1 bg-gray-200" />
        <span className="text-xs uppercase tracking-wide text-gray-400">{t('auth.orDivider')}</span>
        <span className="h-px flex-1 bg-gray-200" />
      </div>

      {GOOGLE_CLIENT_ID && <div ref={googleContainerRef} className="flex justify-center" />}

      {APPLE_CLIENT_ID && (
        <button
          type="button"
          disabled={busy}
          onClick={() => handleProviderClick('apple')}
          className={`${outlineButtonClass} border-gray-900 bg-gray-900 text-white hover:bg-black`}
        >
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M16.7 12.9c0-2 1.6-3 1.7-3-1-1.4-2.4-1.6-2.9-1.6-1.2-.1-2.4.7-3 .7-.6 0-1.6-.7-2.6-.7-1.3 0-2.6.8-3.3 2-1.4 2.4-.4 6 1 8 .7 1 1.5 2.1 2.5 2 1 0 1.4-.6 2.6-.6 1.2 0 1.5.6 2.6.6 1.1 0 1.8-1 2.5-2 .8-1.1 1.1-2.2 1.1-2.3 0 0-2.1-.8-2.2-3.1zM14.8 6.1c.5-.7.9-1.6.8-2.6-.8 0-1.8.6-2.3 1.2-.5.6-1 1.6-.8 2.5.9.1 1.8-.4 2.3-1.1z" />
          </svg>
          {t('auth.continueWithApple')}
        </button>
      )}

      {FACEBOOK_APP_ID && (
        <button
          type="button"
          disabled={busy}
          onClick={() => handleProviderClick('facebook')}
          className={`${outlineButtonClass} border-[#1877F2] bg-[#1877F2] text-white hover:bg-[#166fe0]`}
        >
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07c0 6.02 4.39 11.02 10.13 11.93v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.7 4.53-4.7 1.31 0 2.68.24 2.68.24v2.97h-1.51c-1.49 0-1.96.93-1.96 1.89v2.26h3.33l-.53 3.49h-2.8V24C19.61 23.09 24 18.09 24 12.07z" />
          </svg>
          {t('auth.continueWithFacebook')}
        </button>
      )}
    </div>
  );
};

export default SocialAuthButtons;
