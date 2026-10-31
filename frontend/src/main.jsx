import React from 'react';
import { createRoot } from 'react-dom/client';
import App from './App.jsx';
import './css/app.css';
import './css/login.css';

// Entry point: mount the React app into <div id="root"> in index.html.
// StrictMode adds extra development-only checks and has no effect in production.
createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>,
);
