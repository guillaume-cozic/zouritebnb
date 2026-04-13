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
import SolidarityProjectsPage from './features/solidarityProject/components/SolidarityProjectsPage';
import SolidarityProjectDetailPage from './features/solidarityProject/components/SolidarityProjectDetailPage';

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
          <Route element={<BackofficeLayout />}>
            <Route path="/admin/accommodations" element={<ManageAccommodationsPage />} />
            <Route path="/accommodations/:id/edit" element={<EditAccommodationPage />} />
            <Route path="/accommodations/:id/photos" element={<AccommodationPhotosPage />} />
          </Route>
        </Routes>
      </div>
    </BrowserRouter>
  );
}

export default App;
