import { useState } from 'react';

export default function LoginPage({ onLogin, error }) {
  const [credentials, setCredentials] = useState({ email: '', password: '' });
  const [submitting, setSubmitting] = useState(false);
  const [formError, setFormError] = useState('');

  async function handleSubmit(event) {
    event.preventDefault();
    setSubmitting(true);
    setFormError('');
    try {
      await onLogin(credentials);
    } catch (loginError) {
      setFormError(loginError.message);
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <main className="login-layout">
      <section className="login-card">
        <a className="login-brand" href="/" aria-label="Gymnastics Academy home">
          <span className="login-brand-mark" aria-hidden="true">G</span>
          <span>Gymnastics Academy</span>
        </a>
        <div className="login-heading">
          <p className="login-eyebrow">WELCOME BACK</p>
          <h1>Sign in to your account</h1>
          <p>Manage your membership and training details.</p>
        </div>
        {(formError || error) && <p className="alert alert-error" role="alert">{formError || error}</p>}
        <form className="login-form" onSubmit={handleSubmit}>
          <div className="form-group">
            <label htmlFor="login-email">Email address</label>
            <input
              id="login-email"
              type="email"
              autoComplete="username"
              placeholder="you@example.com"
              value={credentials.email}
              onChange={(event) => setCredentials({ ...credentials, email: event.target.value })}
              required
            />
          </div>
          <div className="form-group">
            <label htmlFor="login-password">Password</label>
            <input
              id="login-password"
              type="password"
              autoComplete="current-password"
              placeholder="Enter your password"
              value={credentials.password}
              onChange={(event) => setCredentials({ ...credentials, password: event.target.value })}
              required
            />
          </div>
          <button className="login-submit" type="submit" disabled={submitting}>
            {submitting ? 'Signing in…' : 'Sign in'} <span aria-hidden="true">→</span>
          </button>
        </form>
        <p className="login-footer">Gymnastics Academy <span>·</span> Member portal</p>
      </section>
      <aside className="login-aside" aria-label="Academy welcome">
        <div className="aside-decoration aside-decoration-one" />
        <div className="aside-decoration aside-decoration-two" />
        <div className="aside-content">
          <span className="aside-label">MOVE WITH PURPOSE</span>
          <span className="aside-glyph" aria-hidden="true">G</span>
          <h2>Strength, focus,<br />and a little more lift.</h2>
          <p>Your training journey, all in one place.</p>
        </div>
        <span className="aside-caption">GYMNASTICS ACADEMY <span>—</span> EST. 2024</span>
      </aside>
    </main>
  );
}
