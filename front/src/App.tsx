import React from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import Navbar from './components/Navbar';
import HomePage from './features/homepage/components/HomePage';
import CreateAccommodationWizard from './features/accommodation/components/CreateAccommodationWizard';
import AccommodationDetailPage from './features/accommodation/components/AccommodationDetailPage';
import EditAccommodationPage from './features/accommodation/components/EditAccommodationPage';
import AccommodationPhotosPage from './features/accommodation/components/AccommodationPhotosPage';

function App() {
  return (
    <BrowserRouter>
      <Navbar />
      <div className="pt-16 min-h-screen">
        <Routes>
          <Route path="/" element={<HomePage />} />
          <Route path="/accommodations/:id" element={<AccommodationDetailPage />} />
          <Route path="/accommodations/:id/edit" element={<EditAccommodationPage />} />
          <Route path="/accommodations/:id/photos" element={<AccommodationPhotosPage />} />
          <Route path="/create" element={<CreateAccommodationWizard />} />
        </Routes>
      </div>
    </BrowserRouter>
  );
}

export default App;
