import React from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import Navbar from './components/Navbar';
import HomePage from './features/homepage/components/HomePage';
import CreateAccommodationWizard from './features/accommodation/components/CreateAccommodationWizard';

function App() {
  return (
    <BrowserRouter>
      <Navbar />
      <div className="pt-16">
        <Routes>
          <Route path="/" element={<HomePage />} />
          <Route path="/create" element={<CreateAccommodationWizard />} />
        </Routes>
      </div>
    </BrowserRouter>
  );
}

export default App;
