import React from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import Navbar from './components/Navbar';
import HomePage from './features/homepage/components/HomePage';
import AccommodationsListingPage from './features/homepage/components/AccommodationsListingPage';
import CreateAccommodationWizard from './features/accommodation/components/CreateAccommodationWizard';
import AccommodationDetailPage from './features/accommodation/components/AccommodationDetailPage';
import EditAccommodationPage from './features/accommodation/components/EditAccommodationPage';
import AccommodationPhotosPage from './features/accommodation/components/AccommodationPhotosPage';
import ManageAccommodationsPage from './features/accommodationManagement/components/ManageAccommodationsPage';
import BackofficeLayout from './features/accommodationManagement/components/BackofficeLayout';
import TeamSettingsPage from './features/team/components/TeamSettingsPage';
import LoginPage from './features/auth/components/LoginPage';
import RegisterPage from './features/auth/components/RegisterPage';
import SolidarityProjectsPage from './features/solidarityProject/components/SolidarityProjectsPage';
import SolidarityProjectDetailPage from './features/solidarityProject/components/SolidarityProjectDetailPage';
import AccommodationCalendarPage from './features/reservation/components/AccommodationCalendarPage';
import AllAccommodationsCalendarPage from './features/reservation/components/AllAccommodationsCalendarPage';

function App() {
  return (
    <BrowserRouter>
      <Navbar />
      <div className="pt-16 min-h-screen">
        <Routes>
          <Route path="/" element={<HomePage />} />
          <Route path="/solidarity-projects" element={<SolidarityProjectsPage />} />
          <Route path="/solidarity-projects/:id" element={<SolidarityProjectDetailPage />} />
          <Route path="/accommodations" element={<AccommodationsListingPage />} />
          <Route path="/accommodations/:id" element={<AccommodationDetailPage />} />
          <Route path="/create" element={<CreateAccommodationWizard />} />
          <Route path="/login" element={<LoginPage />} />
          <Route path="/register" element={<RegisterPage />} />
          <Route element={<BackofficeLayout />}>
            <Route path="/admin/accommodations" element={<ManageAccommodationsPage />} />
            <Route path="/admin/team" element={<TeamSettingsPage />} />
            <Route path="/admin/calendar" element={<AllAccommodationsCalendarPage />} />
            <Route path="/admin/accommodations/:id/calendar" element={<AccommodationCalendarPage />} />
            <Route path="/accommodations/:id/edit" element={<EditAccommodationPage />} />
            <Route path="/accommodations/:id/photos" element={<AccommodationPhotosPage />} />
          </Route>
        </Routes>
      </div>
    </BrowserRouter>
  );
}

export default App;
