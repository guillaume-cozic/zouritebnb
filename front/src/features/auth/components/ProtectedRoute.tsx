import React, { useEffect } from 'react';
import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { useAppDispatch, useAppSelector } from '../../../store/hooks';
import { selectAuthUser } from '../AuthSelectors';
import { logout } from '../AuthSlice';
import { hasRenewableSession } from '../../../services/api';

const ProtectedRoute: React.FC = () => {
  const user = useAppSelector(selectAuthUser);
  const dispatch = useAppDispatch();
  const location = useLocation();

  // A persisted user is a live session as long as the JWT is valid or a refresh
  // token can renew it silently. Only when neither remains (both cleared) is the
  // session dead, so we purge it rather than render pages that would 401.
  const isAuthenticated = user !== null && hasRenewableSession();

  useEffect(() => {
    if (user && !isAuthenticated) {
      dispatch(logout());
    }
  }, [user, isAuthenticated, dispatch]);

  if (!isAuthenticated) {
    const returnTo = encodeURIComponent(location.pathname + location.search);
    return <Navigate to={`/login?returnTo=${returnTo}`} replace />;
  }

  return <Outlet />;
};

export default ProtectedRoute;
