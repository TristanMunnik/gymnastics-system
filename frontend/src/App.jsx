import { useEffect, useState } from 'react';
import { getSession, login, logout } from './api.js';
import AdminDashboard from './pages/AdminDashboard.jsx';
import GymnastPortal from './pages/GymnastPortal.jsx';
import LoginPage from './pages/LoginPage.jsx';

// Root component: holds the signed-in user and CSRF token, then shows the right screen for their role.
export default function App() {
  const [user, setUser] = useState(null);
  const [csrfToken, setCsrfToken] = useState('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    // Restore the PHP login session when React starts or the page is refreshed.
    getSession()
      .then((session) => {
        setUser(session.user);
        setCsrfToken(session.csrfToken);
      })
      .catch((sessionError) => setError(sessionError.message))
      .finally(() => setLoading(false));
  }, []);

  async function handleLogin(credentials) {
    const session = await login(credentials, csrfToken);
    setUser(session.user);
    setCsrfToken(session.csrfToken);
    setError('');
  }

  async function handleLogout() {
    try {
      await logout(csrfToken);
      // The old CSRF token died with the session, so fetch a fresh one for the next login.
      const session = await getSession();
      setUser(null);
      setCsrfToken(session.csrfToken);
    } catch (logoutError) {
      setError(logoutError.message);
    }
  }

  if (loading) {
    return <main className="loading-screen"><span className="spinner" />Loading your account…</main>;
  }

  if (!user) {
    return <div className="login-page"><LoginPage onLogin={handleLogin} error={error} /></div>;
  }

  if (user.role === 'gymnast') {
    return <GymnastPortal user={user} onLogout={handleLogout} />;
  }

  return <AdminDashboard user={user} csrfToken={csrfToken} onLogout={handleLogout} />;
}
