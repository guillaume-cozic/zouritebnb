import React from 'react';
import ReactDOM from 'react-dom/client';
import { Provider } from 'react-redux';
import { store } from './store';
import { logout } from './features/auth/AuthSlice';
import { setUnauthorizedHandler } from './services/api';
import './index.css';
import './i18n';
import App from './App';

// On a 401 the API client already purged localStorage; here we also purge the
// Redux store and send the user back to the login screen.
setUnauthorizedHandler(() => {
  store.dispatch(logout());
  if (window.location.pathname !== '/login') {
    window.location.assign('/login');
  }
});

const root = ReactDOM.createRoot(
  document.getElementById('root') as HTMLElement
);
root.render(
  <React.StrictMode>
    <Provider store={store}>
      <App />
    </Provider>
  </React.StrictMode>
);
