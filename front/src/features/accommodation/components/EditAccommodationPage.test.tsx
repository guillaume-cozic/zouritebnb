import type { Mocked } from 'vitest';
vi.mock('../../../services/api', async (importOriginal) => ({
  ...((await importOriginal()) as Record<string, unknown>),
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), patch: vi.fn(), delete: vi.fn() },
}));

vi.mock('../../../components/MapSelector', () => ({
  __esModule: true,
  default: () => <div data-testid="map-selector" />,
}));

import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { Provider } from 'react-redux';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { configureStore } from '@reduxjs/toolkit';
import '../../../i18n';
import accommodationReducer from '../AccommodationSlice';
import { listenerMiddleware } from '../../../store/listenerMiddleware';
import '../AccommodationListeners';
import api from '../../../services/api';
import EditAccommodationPage from './EditAccommodationPage';

const mockedApi = api as Mocked<typeof api>;

const ACCOMMODATION = {
  id: 'a-1',
  title: 'Villa des tests',
  description: 'Une description suffisante',
  price: 100,
  weeklyPromotionPercentage: null,
  bedrooms: 2,
  bathrooms: 1,
  maxGuests: 4,
  singleBeds: 1,
  doubleBeds: 1,
  amenities: ['wifi'],
  checkIn: '16:00',
  checkOut: '12:00',
  street: '1 rue du Test',
  city: 'Paris',
  zipCode: '75001',
  country: 'France',
  photos: [],
};

const buildStore = () =>
  configureStore({
    reducer: { accommodation: accommodationReducer },
    middleware: (gdm) => gdm().prepend(listenerMiddleware.middleware),
  });

const renderPage = () => {
  const store = buildStore();
  render(
    <Provider store={store}>
      <MemoryRouter initialEntries={['/accommodations/a-1/edit']}>
        <Routes>
          <Route path="/accommodations/:id/edit" element={<EditAccommodationPage />} />
        </Routes>
      </MemoryRouter>
    </Provider>
  );
  return store;
};

beforeEach(() => {
  vi.clearAllMocks();
  mockedApi.get.mockResolvedValue({ data: ACCOMMODATION });
  mockedApi.patch.mockResolvedValue({ data: {} });
  mockedApi.put.mockResolvedValue({ data: {} });
});

test('loads the accommodation on mount and hydrates the form', async () => {
  renderPage();

  expect(await screen.findByDisplayValue('Villa des tests')).toBeInTheDocument();
  expect(mockedApi.get).toHaveBeenCalledWith('/api/accommodations/a-1');
  expect(screen.getByDisplayValue('Une description suffisante')).toBeInTheDocument();
  expect(screen.getByDisplayValue('100')).toBeInTheDocument();
});

test('editing the price auto-saves it after the debounce', async () => {
  renderPage();

  const priceInput = await screen.findByDisplayValue('100');
  fireEvent.change(priceInput, { target: { value: '150' } });

  await waitFor(
    () =>
      expect(mockedApi.patch).toHaveBeenCalledWith(
        '/api/accommodations/a-1/price',
        { price: 150 },
        expect.anything()
      ),
    { timeout: 3000 }
  );
});

test('toggling an amenity auto-saves the new selection', async () => {
  renderPage();

  await screen.findByDisplayValue('Villa des tests');
  const wifiButton = screen.getByRole('button', { name: /wi-?fi/i });
  fireEvent.click(wifiButton);

  await waitFor(
    () =>
      expect(mockedApi.put).toHaveBeenCalledWith(
        '/api/accommodations/a-1/amenities',
        { codes: [] },
        expect.anything()
      ),
    { timeout: 3000 }
  );
});

test('clearing the title does not trigger a save', async () => {
  renderPage();

  const titleInput = await screen.findByDisplayValue('Villa des tests');
  fireEvent.change(titleInput, { target: { value: '' } });

  // Wait past the debounce window: no save of an empty title.
  await new Promise((resolve) => setTimeout(resolve, 1600));
  expect(mockedApi.put).not.toHaveBeenCalledWith(
    '/api/accommodations/a-1/description',
    expect.anything(),
    expect.anything()
  );
});
