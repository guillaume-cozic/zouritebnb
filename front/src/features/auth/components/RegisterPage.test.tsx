vi.mock('../../../services/api', () => ({
  __esModule: true,
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), patch: vi.fn(), delete: vi.fn() },
  AUTH_USER_KEY: 'auth.user',
  clearStoredAuth: vi.fn(),
  setStoredToken: vi.fn(),
}));

import React from 'react';
import { render, screen } from '@testing-library/react';
import { Provider } from 'react-redux';
import { MemoryRouter } from 'react-router-dom';
import { configureStore } from '@reduxjs/toolkit';
import '../../../i18n';
import authReducer from '../AuthSlice';
import RegisterPage from './RegisterPage';

const renderAt = (entry: string) => {
  render(
    <Provider store={configureStore({ reducer: { auth: authReducer } })}>
      <MemoryRouter initialEntries={[entry]}>
        <RegisterPage />
      </MemoryRouter>
    </Provider>
  );
};

beforeEach(() => {
  localStorage.clear();
});

test('shows the booking notice when the user was sent here from the booking flow', () => {
  renderAt('/register?returnTo=' + encodeURIComponent('/accommodations/abc/book'));

  expect(screen.getByText(/Plus qu'une étape avant votre réservation/)).toBeInTheDocument();
  // Register-specific wording + the no-charge reassurance.
  expect(screen.getByText(/créez votre compte — c'est gratuit et rapide/)).toBeInTheDocument();
  expect(screen.getByText(/Aucun montant ne sera prélevé/)).toBeInTheDocument();
});

test('does not show the booking notice on a regular registration', () => {
  renderAt('/register');

  expect(screen.queryByText(/Plus qu'une étape avant votre réservation/)).not.toBeInTheDocument();
});
