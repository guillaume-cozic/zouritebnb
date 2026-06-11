import { BrowserRouter, Route, Routes } from 'react-router-dom';
import { AdminLayout } from './components/AdminLayout';
import { DashboardPage } from './components/DashboardPage';
import { LoginPage } from './features/auth/components/LoginPage';
import { ProtectedRoute } from './features/auth/components/ProtectedRoute';
import { ReservationsPage } from './features/reservations/components/ReservationsPage';
import { AccommodationsPage } from './features/accommodations/components/AccommodationsPage';
import { ReviewsPage } from './features/reviews/components/ReviewsPage';
import { UsersPage } from './features/users/components/UsersPage';

export function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/login" element={<LoginPage />} />
        <Route
          path="/"
          element={
            <ProtectedRoute>
              <AdminLayout />
            </ProtectedRoute>
          }
        >
          <Route index element={<DashboardPage />} />
          <Route path="reservations" element={<ReservationsPage />} />
          <Route path="accommodations" element={<AccommodationsPage />} />
          <Route path="reviews" element={<ReviewsPage />} />
          <Route path="users" element={<UsersPage />} />
        </Route>
      </Routes>
    </BrowserRouter>
  );
}
