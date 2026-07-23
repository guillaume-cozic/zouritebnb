import React, { Suspense, useEffect } from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { useAppDispatch } from './store/hooks';
import { fetchWishlist } from './features/wishlist/WishlistSlice';
import Navbar from './components/Navbar';
import Seo from './components/Seo';
import NotFoundPage from './components/NotFoundPage';
import HomePage from './features/homepage/components/HomePage';
import EmailVerificationBanner from './features/auth/components/EmailVerificationBanner';
import ProtectedRoute from './features/auth/components/ProtectedRoute';
import { Spinner } from './components/ui';

// Tout sauf la page d'accueil est chargé à la demande : un visiteur anonyme
// ne télécharge ni le backoffice hôte, ni l'espace voyageur, ni Leaflet
// (bundle principal ~3× plus léger, LCP mobile).
const WishlistPage = React.lazy(() => import('./features/wishlist/components/WishlistPage'));
const AccommodationsListingPage = React.lazy(() => import('./features/homepage/components/AccommodationsListingPage'));
const CreateAccommodationWizard = React.lazy(() => import('./features/accommodation/components/CreateAccommodationWizard'));
const AccommodationDetailPage = React.lazy(() => import('./features/accommodation/components/AccommodationDetailPage'));
const EditAccommodationPage = React.lazy(() => import('./features/accommodation/components/EditAccommodationPage'));
const AccommodationPhotosPage = React.lazy(() => import('./features/accommodation/components/AccommodationPhotosPage'));
const ManageAccommodationsPage = React.lazy(() => import('./features/accommodationManagement/components/ManageAccommodationsPage'));
const BackofficeLayout = React.lazy(() => import('./features/accommodationManagement/components/BackofficeLayout'));
const HostHomePage = React.lazy(() => import('./features/accommodationManagement/components/HostHomePage'));
const TravelerHomePage = React.lazy(() => import('./features/account/components/TravelerHomePage'));
const RequireAccommodation = React.lazy(() => import('./features/accommodationManagement/components/RequireAccommodation'));
const TeamSettingsPage = React.lazy(() => import('./features/team/components/TeamSettingsPage'));
const LoginPage = React.lazy(() => import('./features/auth/components/LoginPage'));
const RegisterPage = React.lazy(() => import('./features/auth/components/RegisterPage'));
const ForgotPasswordPage = React.lazy(() => import('./features/auth/components/ForgotPasswordPage'));
const ResetPasswordPage = React.lazy(() => import('./features/auth/components/ResetPasswordPage'));
const VerifyEmailPage = React.lazy(() => import('./features/auth/components/VerifyEmailPage'));
const ActivitiesMapPage = React.lazy(() => import('./features/activityPoint/components/ActivitiesMapPage'));
const SolidarityProjectsPage = React.lazy(() => import('./features/solidarityProject/components/SolidarityProjectsPage'));
const SolidarityProjectDetailPage = React.lazy(() => import('./features/solidarityProject/components/SolidarityProjectDetailPage'));
const DonationPage = React.lazy(() => import('./features/donation/components/DonationPage'));
const AccommodationCalendarPage = React.lazy(() => import('./features/reservation/components/AccommodationCalendarPage'));
const AllAccommodationsCalendarPage = React.lazy(() => import('./features/reservation/components/AllAccommodationsCalendarPage'));
const MessagingPage = React.lazy(() => import('./features/conversation/components/MessagingPage'));
const AdminReservationsPage = React.lazy(() => import('./features/reservation/components/AdminReservationsPage'));
const HostRevenuePage = React.lazy(() => import('./features/hostRevenue/components/HostRevenuePage'));
const ReservationConfirmationPage = React.lazy(() => import('./features/reservation/components/ReservationConfirmationPage'));
const ReservationSuccessPage = React.lazy(() => import('./features/reservation/components/ReservationSuccessPage'));
const IdentityVerificationPage = React.lazy(() => import('./features/userProfile/components/IdentityVerificationPage'));
const TermsOfUsePage = React.lazy(() => import('./features/legal/components/TermsOfUsePage'));
const TermsOfSalePage = React.lazy(() => import('./features/legal/components/TermsOfSalePage'));
const LegalNoticePage = React.lazy(() => import('./features/legal/components/LegalNoticePage'));
const PrivacyPolicyPage = React.lazy(() => import('./features/legal/components/PrivacyPolicyPage'));

const RouteFallback: React.FC = () => (
  <div className="flex justify-center pt-24">
    <Spinner size={32} className="text-primary-600" />
  </div>
);

function App() {
  const dispatch = useAppDispatch();

  // Load the wishlist once on boot (anonymous via cookie, or the account's) so
  // the heart toggles reflect saved state across the app.
  useEffect(() => {
    dispatch(fetchWishlist());
  }, [dispatch]);

  return (
    <BrowserRouter>
      <Seo />
      <Navbar />
      <main className="pt-16 min-h-screen">
        <EmailVerificationBanner />
        <Suspense fallback={<RouteFallback />}>
          <Routes>
            <Route path="/" element={<HomePage />} />
            <Route path="/wishlist" element={<WishlistPage />} />
            <Route path="/activites" element={<ActivitiesMapPage />} />
            <Route path="/solidarity-projects" element={<SolidarityProjectsPage />} />
            <Route path="/solidarity-projects/:id" element={<SolidarityProjectDetailPage />} />
            <Route path="/solidarity-projects/:id/donate" element={<DonationPage />} />
            <Route path="/cgu" element={<TermsOfUsePage />} />
            <Route path="/cgv" element={<TermsOfSalePage />} />
            <Route path="/mentions-legales" element={<LegalNoticePage />} />
            <Route path="/confidentialite" element={<PrivacyPolicyPage />} />
            <Route path="/accommodations" element={<AccommodationsListingPage />} />
            {/* Détail d'une annonce : /hebergements/<slug>--<uuid> (l'UUID final
                résout, le slug est purement SEO ; un UUID nu est accepté) */}
            <Route path="/hebergements/:slug" element={<AccommodationDetailPage />} />
            {/* Publique : un futur hôte peut découvrir le formulaire sans compte ;
                la connexion n'est exigée qu'à la soumission de la première étape. */}
            <Route path="/create" element={<CreateAccommodationWizard />} />
            <Route element={<ProtectedRoute />}>
              <Route path="/accommodations/:id/book" element={<ReservationConfirmationPage />} />
              <Route path="/reservation-confirmed" element={<ReservationSuccessPage />} />
            </Route>
            <Route path="/login" element={<LoginPage />} />
            <Route path="/register" element={<RegisterPage />} />
            <Route path="/forgot-password" element={<ForgotPasswordPage />} />
            <Route path="/reset-password" element={<ResetPasswordPage />} />
            <Route path="/verify-email" element={<VerifyEmailPage />} />
            <Route path="/conversations" element={<Navigate to="/account/conversations" replace />} />
            <Route path="/conversations/:id" element={<Navigate to="/account/conversations" replace />} />
            <Route element={<ProtectedRoute />}>
              <Route element={<BackofficeLayout />}>
                <Route path="/admin" element={<HostHomePage />} />
                {/* Pages useless without a listing: gated behind owning ≥1 accommodation */}
                <Route element={<RequireAccommodation />}>
                  <Route path="/admin/accommodations" element={<ManageAccommodationsPage />} />
                  <Route path="/admin/calendar" element={<AllAccommodationsCalendarPage />} />
                  <Route path="/admin/accommodations/:id/calendar" element={<AccommodationCalendarPage />} />
                  <Route path="/admin/reservations" element={<AdminReservationsPage />} />
                  <Route path="/admin/revenue" element={<HostRevenuePage />} />
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
            <Route path="*" element={<NotFoundPage />} />
          </Routes>
        </Suspense>
      </main>
    </BrowserRouter>
  );
}

export default App;
