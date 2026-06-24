import React from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import Navbar from './components/Navbar';
import DocumentTitle from './components/DocumentTitle';
import HomePage from './features/homepage/components/HomePage';
import AccommodationsListingPage from './features/homepage/components/AccommodationsListingPage';
import CreateAccommodationWizard from './features/accommodation/components/CreateAccommodationWizard';
import AccommodationDetailPage from './features/accommodation/components/AccommodationDetailPage';
import EditAccommodationPage from './features/accommodation/components/EditAccommodationPage';
import AccommodationPhotosPage from './features/accommodation/components/AccommodationPhotosPage';
import ManageAccommodationsPage from './features/accommodationManagement/components/ManageAccommodationsPage';
import BackofficeLayout from './features/accommodationManagement/components/BackofficeLayout';
import HostHomePage from './features/accommodationManagement/components/HostHomePage';
import TravelerHomePage from './features/account/components/TravelerHomePage';
import RequireAccommodation from './features/accommodationManagement/components/RequireAccommodation';
import TeamSettingsPage from './features/team/components/TeamSettingsPage';
import LoginPage from './features/auth/components/LoginPage';
import RegisterPage from './features/auth/components/RegisterPage';
import SolidarityProjectsPage from './features/solidarityProject/components/SolidarityProjectsPage';
import SolidarityProjectDetailPage from './features/solidarityProject/components/SolidarityProjectDetailPage';
import AccommodationCalendarPage from './features/reservation/components/AccommodationCalendarPage';
import AllAccommodationsCalendarPage from './features/reservation/components/AllAccommodationsCalendarPage';
import ProtectedRoute from './features/auth/components/ProtectedRoute';
import MessagingPage from './features/conversation/components/MessagingPage';
import AdminReservationsPage from './features/reservation/components/AdminReservationsPage';
import ReservationConfirmationPage from './features/reservation/components/ReservationConfirmationPage';
import ReservationSuccessPage from './features/reservation/components/ReservationSuccessPage';
import IdentityVerificationPage from './features/userProfile/components/IdentityVerificationPage';

function App() {
  return (
    <BrowserRouter>
      <DocumentTitle />
      <Navbar />
      <div className="pt-16 min-h-screen">
        <Routes>
          <Route path="/" element={<HomePage />} />
          <Route path="/solidarity-projects" element={<SolidarityProjectsPage />} />
          <Route path="/solidarity-projects/:id" element={<SolidarityProjectDetailPage />} />
          <Route path="/accommodations" element={<AccommodationsListingPage />} />
          <Route path="/accommodations/:id" element={<AccommodationDetailPage />} />
          <Route element={<ProtectedRoute />}>
            <Route path="/accommodations/:id/book" element={<ReservationConfirmationPage />} />
            <Route path="/reservation-confirmed" element={<ReservationSuccessPage />} />
          </Route>
          <Route path="/login" element={<LoginPage />} />
          <Route path="/register" element={<RegisterPage />} />
          <Route path="/conversations" element={<Navigate to="/account/conversations" replace />} />
          <Route path="/conversations/:id" element={<Navigate to="/account/conversations" replace />} />
          <Route element={<ProtectedRoute />}>
            <Route path="/create" element={<CreateAccommodationWizard />} />
            <Route element={<BackofficeLayout />}>
              <Route path="/admin" element={<HostHomePage />} />
              {/* Pages useless without a listing: gated behind owning ≥1 accommodation */}
              <Route element={<RequireAccommodation />}>
                <Route path="/admin/accommodations" element={<ManageAccommodationsPage />} />
                <Route path="/admin/calendar" element={<AllAccommodationsCalendarPage />} />
                <Route path="/admin/accommodations/:id/calendar" element={<AccommodationCalendarPage />} />
                <Route path="/admin/reservations" element={<AdminReservationsPage />} />
              </Route>
              <Route path="/admin/team" element={<TeamSettingsPage />} />
              <Route path="/admin/conversations" element={<MessagingPage role="host" />} />
              <Route path="/admin/conversations/:id" element={<MessagingPage role="host" />} />
              <Route path="/accommodations/:id/edit" element={<EditAccommodationPage />} />
              <Route path="/accommodations/:id/photos" element={<AccommodationPhotosPage />} />
              <Route path="/account" element={<TravelerHomePage />} />
              <Route path="/account/conversations" element={<MessagingPage role="guest" />} />
              <Route path="/account/conversations/:id" element={<MessagingPage role="guest" />} />
              <Route path="/account/settings" element={<TeamSettingsPage />} />
              <Route path="/account/verification" element={<IdentityVerificationPage />} />
            </Route>
          </Route>
        </Routes>
      </div>
    </BrowserRouter>
  );
}

export default App;
