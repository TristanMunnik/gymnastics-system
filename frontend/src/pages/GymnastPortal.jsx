import { useEffect, useState } from 'react';
import { getGymnastProfile } from '../api.js';
import StatusBadge from '../components/StatusBadge.jsx';

const profileFields = [
  ['Full name', 'full_name'],
  ['Membership ID', 'membership_id'],
  ['Email', 'email'],
  ['Contact number', 'contact_no'],
  ['Date of birth', 'date_of_birth'],
  ['Training program', 'training_program'],
  ['Enrollment date', 'enrollment_date'],
  ['Status', 'status'],
];

function ProfileRows({ gymnast, fields }) {
  return fields.map(([label, key]) => (
    <div className="profile-row" key={key}>
      <span className="profile-label">{label}</span>
      <span className="profile-value">{gymnast[key]}</span>
    </div>
  ));
}

export default function GymnastPortal({ user, onLogout }) {
  const [gymnast, setGymnast] = useState(null);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);
  const page = new URLSearchParams(window.location.search).get('page') || 'profile';

  useEffect(() => {
    // The API looks up the profile from the signed-in session, never an ID from the URL.
    getGymnastProfile()
      .then(setGymnast)
      .catch((loadError) => setError(loadError.message))
      .finally(() => setLoading(false));
  }, []);

  if (loading) {
    return <main className="loading-screen"><span className="spinner" />Loading your profile…</main>;
  }

  return (
    <>
      <header className="navbar member-navbar">
        <a className="navbar-brand" href="?page=profile"><span className="brand-mark">G</span> Gymnastics Academy</a>
        <nav className="navbar-links" aria-label="Member navigation">
          <span className="user-name">{user.full_name}</span>
          <a className={`member-nav-link ${page === 'profile' ? 'active' : ''}`} href="?page=profile">My profile</a>
          <a className={`member-nav-link ${page === 'report' ? 'active' : ''}`} href="?page=report">Profile report</a>
          <a className={`member-nav-link ${page === 'slip' ? 'active' : ''}`} href="?page=slip">Enrollment slip</a>
          <button className="nav-button" type="button" onClick={onLogout}>Sign out</button>
        </nav>
      </header>
      <main className="container member-container member-page">
        {error && <p className="alert alert-error" role="alert">{error}</p>}
        {gymnast && page === 'profile' && (
          <>
            <section className="welcome-row">
              <div><p className="eyebrow">MEMBER PORTAL</p><h1>My profile</h1><p className="welcome-copy">Your membership and training details.</p></div>
              <StatusBadge status={gymnast.status} />
            </section>
            <section className="panel member-profile profile-panel">
              <div className="panel-heading"><div><h2>Personal details</h2><p>Contact your administrator if any information needs updating.</p></div></div>
              <div className="profile-list"><ProfileRows gymnast={gymnast} fields={profileFields} /></div>
              <div className="member-actions">
                <a className="btn btn-primary" href="?page=report">Profile report</a>
                <a className="btn btn-outline" href="?page=slip">Enrollment slip</a>
              </div>
            </section>
          </>
        )}
        {gymnast && (page === 'report' || page === 'slip') && (
          <section className={`panel printable-document document-card document-${page}`}>
            <div className="document-heading">
              <p className="eyebrow">{page === 'report' ? 'MEMBERSHIP RECORD' : 'ENROLLMENT RECORD'}</p>
              <h1>{page === 'report' ? 'Profile summary report' : 'Enrollment confirmation slip'}</h1>
              <p>Prepared for {gymnast.full_name}</p>
            </div>
            <div className="profile-list">
              <ProfileRows
                gymnast={gymnast}
                fields={page === 'report'
                  ? profileFields
                  : [['Member name', 'full_name'], ['Membership ID', 'membership_id'], ['Training program', 'training_program'], ['Enrollment date', 'enrollment_date'], ['Current status', 'status']]}
              />
              {page === 'slip' && (
                <div className="profile-row"><span className="profile-label">Course summary</span><span className="profile-value">{gymnast.training_program} program · Enrolled {gymnast.enrollment_date}</span></div>
              )}
              <div className="profile-row"><span className="profile-label">Generated</span><span className="profile-value">{new Date().toLocaleString()}</span></div>
            </div>
            <div className="member-actions no-print">
              <button className="btn btn-primary" type="button" onClick={() => window.print()}>Print / Save as PDF</button>
              <a className="btn btn-outline" href="?page=profile">Back to profile</a>
            </div>
          </section>
        )}
        {!error && gymnast && !['profile', 'report', 'slip'].includes(page) && (
          <p className="alert alert-error" role="alert">That page could not be found. <a href="?page=profile">Return to your profile.</a></p>
        )}
      </main>
    </>
  );
}
