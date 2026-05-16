import React from 'react';
import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { useAppSelector } from '../../../store/hooks';
import { selectAuthUser } from '../AuthSelectors';

const ProtectedRoute: React.FC = () => {
  const user = useAppSelector(selectAuthUser);
  const location = useLocation();

  if (!user) {
    const returnTo = encodeURIComponent(location.pathname + location.search);
    return <Navigate to={`/login?returnTo=${returnTo}`} replace />;
  }

  return <Outlet />;
};

export default ProtectedRoute;
