import React from 'react';
import Footer from '../../../components/Footer';
import { AuthSolidarityPanel, AuthSolidarityTeaser } from './AuthSolidarityShowcase';
import { useFeaturedSolidarityProject } from '../../solidarityProject/useFeaturedSolidarityProject';

/**
 * Coquille des pages de connexion / inscription : formulaire à gauche, projet
 * solidaire tiré au sort mis en avant à droite (et en teaser compact sur mobile).
 */
const AuthLayout: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const featured = useFeaturedSolidarityProject();

  return (
    <div className="min-h-[calc(100vh-4rem)] flex flex-col">
      <div className="flex-1 grid lg:grid-cols-2">
        <div className="flex items-center justify-center px-4 py-16 relative overflow-hidden bg-gradient-to-b from-primary-50/40 via-white to-white">
          <div className="absolute -top-32 -right-32 w-96 h-96 rounded-full bg-primary-200/30 blur-3xl lg:hidden" aria-hidden="true" />
          <div className="absolute -bottom-32 -left-32 w-96 h-96 rounded-full bg-primary-100/40 blur-3xl" aria-hidden="true" />
          <div className="w-full max-w-md relative">
            <AuthSolidarityTeaser featured={featured} />
            {children}
          </div>
        </div>

        <AuthSolidarityPanel featured={featured} />
      </div>

      <Footer />
    </div>
  );
};

export default AuthLayout;
