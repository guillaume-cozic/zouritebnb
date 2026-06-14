import type { Mocked } from 'vitest';
vi.mock('../../services/api', async (importOriginal) => ({
  ...((await importOriginal()) as Record<string, unknown>),
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), patch: vi.fn(), delete: vi.fn() },
}));

import { configureStore } from '@reduxjs/toolkit';
import userProfileReducer, {
  submitIdentityVerification,
  fetchVerificationStatus,
  resetVerification,
} from './UserProfileSlice';
import api from '../../services/api';

const mockedApi = api as Mocked<typeof api>;

const buildStore = () => configureStore({ reducer: { userProfile: userProfileReducer } });

const aFile = () => new File(['bytes'], 'doc.jpg', { type: 'image/jpeg' });

beforeEach(() => {
  vi.clearAllMocks();
});

describe('submitIdentityVerification', () => {
  test('stores the verified status after fulfilled', async () => {
    mockedApi.post.mockResolvedValue({
      data: { status: 'verified', documentType: 'passport', verifiedAt: '2026-06-07T12:00:00+00:00' },
    });
    const store = buildStore();

    await store.dispatch(
      submitIdentityVerification({
        userId: 'u-1',
        documentType: 'passport',
        documentFile: aFile(),
        selfieFile: aFile(),
      })
    );

    const state = store.getState().userProfile;
    expect(state.status).toBe('succeeded');
    expect(state.verificationStatus).toBe('verified');
    expect(state.documentType).toBe('passport');
    expect(state.verifiedAt).toBe('2026-06-07T12:00:00+00:00');
    expect(mockedApi.post).toHaveBeenCalledWith(
      '/api/users/u-1/identity-verification',
      expect.any(FormData),
      expect.objectContaining({ headers: { 'Content-Type': 'multipart/form-data' } })
    );
  });

  test('moves to failed with the error message after rejected', async () => {
    mockedApi.post.mockRejectedValue({ response: { data: { detail: 'boom' } } });
    const store = buildStore();

    await store.dispatch(
      submitIdentityVerification({
        userId: 'u-1',
        documentType: 'id_card',
        documentFile: aFile(),
        selfieFile: aFile(),
      })
    );

    const state = store.getState().userProfile;
    expect(state.status).toBe('failed');
    expect(state.error).toBe('boom');
    expect(state.verificationStatus).toBe('not_started');
  });
});

describe('fetchVerificationStatus', () => {
  test('stores the fetched status after fulfilled', async () => {
    mockedApi.get.mockResolvedValue({
      data: { status: 'not_started', documentType: null, verifiedAt: null },
    });
    const store = buildStore();

    await store.dispatch(fetchVerificationStatus('u-1'));

    const state = store.getState().userProfile;
    expect(state.verificationStatus).toBe('not_started');
    expect(mockedApi.get).toHaveBeenCalledWith('/api/users/u-1/identity-verification');
  });

  test('moves to failed after rejected', async () => {
    mockedApi.get.mockRejectedValue({ response: { data: { detail: 'nope' } } });
    const store = buildStore();

    await store.dispatch(fetchVerificationStatus('u-1'));

    expect(store.getState().userProfile.status).toBe('failed');
    expect(store.getState().userProfile.error).toBe('nope');
  });
});

describe('resetVerification', () => {
  test('clears the operation state', () => {
    const store = buildStore();
    store.dispatch(resetVerification());
    const state = store.getState().userProfile;
    expect(state.status).toBe('idle');
    expect(state.uploadProgress).toBe(0);
    expect(state.error).toBeNull();
  });
});
