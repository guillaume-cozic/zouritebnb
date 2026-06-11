jest.mock('../../../services/api', () => ({
  __esModule: true,
  default: { get: jest.fn(), post: jest.fn(), put: jest.fn(), patch: jest.fn(), delete: jest.fn() },
  AUTH_USER_KEY: 'auth.user',
  clearStoredAuth: jest.fn(),
  setStoredToken: jest.fn(),
}));

import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { Provider } from 'react-redux';
import { MemoryRouter } from 'react-router-dom';
import { configureStore } from '@reduxjs/toolkit';
import '../../../i18n';
import teamReducer from '../TeamSlice';
import authReducer from '../../auth/AuthSlice';
import userProfileReducer from '../../userProfile/UserProfileSlice';
import solidarityProjectReducer from '../../solidarityProject/SolidarityProjectSlice';
import { listenerMiddleware } from '../../../store/listenerMiddleware';
import '../../../store/registerListeners';
import api from '../../../services/api';
import TeamSettingsPage from './TeamSettingsPage';

const mockedApi = api as jest.Mocked<typeof api>;

const USER = {
  id: 'u-1',
  email: 'host@example.com',
  teamId: 'team-1',
  firstName: 'Jane',
  lastName: 'Doe',
};

const TEAM = {
  id: 'team-1',
  favoriteSolidarityProjectId: null,
  iban: null,
  bic: null,
  bankAccountHolderName: null,
};

const buildStore = () =>
  configureStore({
    reducer: {
      team: teamReducer,
      auth: authReducer,
      userProfile: userProfileReducer,
      solidarityProject: solidarityProjectReducer,
    },
    middleware: (gdm) => gdm().prepend(listenerMiddleware.middleware),
    preloadedState: {
      auth: { user: USER, status: 'idle' as const, error: null, profileSaveState: 'idle' as const },
    },
  });

const renderPage = () => {
  const store = buildStore();
  render(
    <Provider store={store}>
      <MemoryRouter initialEntries={['/backoffice/team']}>
        <TeamSettingsPage />
      </MemoryRouter>
    </Provider>
  );
  return store;
};

beforeEach(() => {
  jest.clearAllMocks();
  localStorage.clear();
  mockedApi.get.mockImplementation((url: string) => {
    if (url === '/api/teams/team-1') return Promise.resolve({ data: TEAM });
    if (url === '/api/teams/team-1/invitations') return Promise.resolve({ data: { 'hydra:member': [] } });
    if (url === '/api/solidarity_projects') return Promise.resolve({ data: { 'hydra:member': [] } });
    return Promise.reject(new Error('unexpected url ' + url));
  });
  mockedApi.patch.mockResolvedValue({ data: {} });
});

test('hydrates the profile form from the authenticated user', async () => {
  renderPage();

  expect(await screen.findByDisplayValue('Jane')).toBeInTheDocument();
  expect(screen.getByDisplayValue('Doe')).toBeInTheDocument();
  expect(screen.getByDisplayValue('host@example.com')).toBeInTheDocument();
});

test('editing the first name auto-saves the profile after the debounce', async () => {
  renderPage();

  const firstNameInput = await screen.findByDisplayValue('Jane');
  fireEvent.change(firstNameInput, { target: { value: 'Janet' } });

  await waitFor(
    () =>
      expect(mockedApi.patch).toHaveBeenCalledWith(
        '/api/users/u-1/profile',
        { firstName: 'Janet', lastName: 'Doe', email: 'host@example.com' },
        expect.anything()
      ),
    { timeout: 3000 }
  );
});

test('editing the bank account auto-saves it once the holder name is set', async () => {
  renderPage();

  const ibanInput = await screen.findByPlaceholderText('FR76 3000 1007 9412 3456 7890 185');
  fireEvent.change(ibanInput, { target: { value: 'FR7630001007941234567890185' } });

  // IBAN without holder: incomplete, nothing is saved yet.
  await new Promise((resolve) => setTimeout(resolve, 1000));
  expect(mockedApi.patch).not.toHaveBeenCalledWith(
    '/api/teams/team-1/bank-account',
    expect.anything(),
    expect.anything()
  );

  const holderInput = screen.getAllByRole('textbox').find(
    (input) => (input as HTMLInputElement).value === '' && input !== ibanInput && (input as HTMLInputElement).type === 'text'
  ) as HTMLInputElement;
  fireEvent.change(holderInput, { target: { value: 'Jane Doe' } });

  await waitFor(
    () =>
      expect(mockedApi.patch).toHaveBeenCalledWith(
        '/api/teams/team-1/bank-account',
        { iban: 'FR7630001007941234567890185', bic: null, holderName: 'Jane Doe' },
        expect.anything()
      ),
    { timeout: 3000 }
  );
});
