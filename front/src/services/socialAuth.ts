/**
 * Social sign-in SDK helpers. Each provider is enabled only when its client id
 * is configured (empty env var → button hidden, no SDK loaded). SDKs are loaded
 * lazily on first use, following the same approach as the Stripe integration.
 */

export type SocialProvider = 'google' | 'apple' | 'facebook';

export const GOOGLE_CLIENT_ID: string = import.meta.env.VITE_GOOGLE_CLIENT_ID ?? '';
export const APPLE_CLIENT_ID: string = import.meta.env.VITE_APPLE_CLIENT_ID ?? '';
export const FACEBOOK_APP_ID: string = import.meta.env.VITE_FACEBOOK_APP_ID ?? '';

interface GoogleAccountsId {
  initialize(config: { client_id: string; callback: (response: { credential: string }) => void }): void;
  renderButton(parent: HTMLElement, options: Record<string, unknown>): void;
}

interface AppleIdAuth {
  init(config: { clientId: string; scope: string; redirectURI: string; usePopup: boolean }): void;
  signIn(): Promise<{ authorization: { id_token: string } }>;
}

interface FacebookSdk {
  init(config: { appId: string; version: string; cookie: boolean; xfbml: boolean }): void;
  login(
    callback: (response: { authResponse?: { accessToken: string } | null }) => void,
    options: { scope: string }
  ): void;
}

declare global {
  interface Window {
    google?: { accounts: { id: GoogleAccountsId } };
    AppleID?: { auth: AppleIdAuth };
    FB?: FacebookSdk;
  }
}

const loadedScripts = new Map<string, Promise<void>>();

const loadScript = (src: string): Promise<void> => {
  const existing = loadedScripts.get(src);
  if (existing) return existing;
  const promise = new Promise<void>((resolve, reject) => {
    const script = document.createElement('script');
    script.src = src;
    script.async = true;
    script.defer = true;
    script.onload = () => resolve();
    script.onerror = () => {
      loadedScripts.delete(src);
      reject(new Error(`Failed to load ${src}`));
    };
    document.head.appendChild(script);
  });
  loadedScripts.set(src, promise);
  return promise;
};

/**
 * Renders the official Google sign-in button (the ID token flow requires it) and
 * invokes the callback with the credential (a Google ID token) on success.
 */
export const renderGoogleButton = async (
  container: HTMLElement,
  locale: string,
  onCredential: (idToken: string) => void
): Promise<void> => {
  await loadScript('https://accounts.google.com/gsi/client');
  const accountsId = window.google?.accounts.id;
  if (!accountsId) throw new Error('Google Identity Services unavailable');
  accountsId.initialize({
    client_id: GOOGLE_CLIENT_ID,
    callback: (response) => onCredential(response.credential),
  });
  accountsId.renderButton(container, {
    theme: 'outline',
    size: 'large',
    width: container.clientWidth || 320,
    locale,
  });
};

/** Opens the Sign in with Apple popup and resolves with the identity token. */
export const signInWithApple = async (): Promise<string> => {
  await loadScript('https://appleid.cdn-apple.com/appleauth/static/jsapi/appleid/1/en_US/appleid.auth.js');
  const auth = window.AppleID?.auth;
  if (!auth) throw new Error('Sign in with Apple unavailable');
  auth.init({
    clientId: APPLE_CLIENT_ID,
    scope: 'name email',
    redirectURI: window.location.origin,
    usePopup: true,
  });
  const response = await auth.signIn();
  return response.authorization.id_token;
};

/** Opens the Facebook login popup and resolves with the access token. */
export const signInWithFacebook = async (): Promise<string> => {
  await loadScript('https://connect.facebook.net/en_US/sdk.js');
  const fb = window.FB;
  if (!fb) throw new Error('Facebook SDK unavailable');
  fb.init({ appId: FACEBOOK_APP_ID, version: 'v19.0', cookie: false, xfbml: false });
  return new Promise<string>((resolve, reject) => {
    fb.login(
      (response) => {
        if (response.authResponse?.accessToken) {
          resolve(response.authResponse.accessToken);
        } else {
          reject(new Error('Facebook login cancelled'));
        }
      },
      { scope: 'email' }
    );
  });
};
