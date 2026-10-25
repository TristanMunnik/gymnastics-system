import { useEffect, useMemo, useState } from 'react';
import GymnastForm from '../components/GymnastForm.jsx';
import StatusBadge from '../components/StatusBadge.jsx';
import {
  createGymnast,
  deleteGymnast,
  getGymnasts,
  updateGymnast,
} from '../api.js';

const programs = ['Beginner', 'Intermediate', 'Advanced'];
const statuses = ['Active', 'On Hold', 'Completed', 'Pending'];

export default function AdminDashboard({ user, csrfToken, onLogout }) {
  const [gymnasts, setGymnasts] = useState([]);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [notice, setNotice] = useState('');
  const [search, setSearch] = useState('');
  const [programFilter, setProgramFilter] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [selected, setSelected] = useState(null);
  const [formOpen, setFormOpen] = useState(false);

  useEffect(() => {
    // The API checks the administrator role; the browser never decides access permissions.
    getGymnasts()
      .then(setGymnasts)
      .catch((loadError) => setError(loadError.message));
  }, []);

  const filteredGymnasts = useMemo(() => {
    // Search and filters affect what is displayed, not which records the API authorizes.
    const term = search.trim().toLocaleLowerCase();
    return gymnasts.filter((gymnast) => {
      const matchesSearch = !term
        || gymnast.full_name.toLocaleLowerCase().includes(term)
        || gymnast.membership_id.toLocaleLowerCase().includes(term)
        || gymnast.email.toLocaleLowerCase().includes(term);
      return matchesSearch
        && (!programFilter || gymnast.training_program === programFilter)
        && (!statusFilter || gymnast.status === statusFilter);
    });
  }, [gymnasts, search, programFilter, statusFilter]);

  async function saveGymnast(form) {
    setSaving(true);
    setError('');
    try {
      if (selected) {
        const data = { ...form };
        delete data.password;
        const updated = await updateGymnast(selected.id, data, csrfToken);
        setGymnasts((current) => current.map((item) => item.id === updated.id ? updated : item));
        setNotice('Gymnast details have been updated.');
      } else {
        const created = await createGymnast(form, csrfToken);
        setGymnasts((current) => [created, ...current]);
        setNotice('Gymnast has been registered.');
      }
      setFormOpen(false);
      setSelected(null);
    } finally {
      setSaving(false);
    }
  }

  async function removeGymnast(gymnast) {
    if (!window.confirm(`Delete ${gymnast.full_name}'s membership? This cannot be undone.`)) return;
    setError('');
    setNotice('');
    try {
      await deleteGymnast(gymnast.id, csrfToken);
      setGymnasts((current) => current.filter((item) => item.id !== gymnast.id));
      setNotice(`${gymnast.full_name} has been deleted.`);
    } catch (deleteError) {
      setError(deleteError.message);
    }
  }

  return (
    <>
      <header className="navbar">
        <a className="navbar-brand" href="/"><span className="brand-mark">G</span> Gymnastics Academy</a>
        <nav className="navbar-links" aria-label="Main navigation">
          <span className="user-name">{user.full_name}</span>
          <button className="nav-button" type="button" onClick={onLogout}>Sign out</button>
        </nav>
      </header>
      <main className="container">
        <section className="welcome-row">
          <div>
            <p className="eyebrow">ACADEMY MANAGEMENT</p>
            <h1>Gymnast dashboard</h1>
            <p className="welcome-copy">Manage memberships, programs, and enrollment details.</p>
          </div>
          <button className="btn btn-primary" type="button" onClick={() => { setSelected(null); setFormOpen(true); }}>
            <span aria-hidden="true">+</span> Register gymnast
          </button>
        </section>

        {error && <p className="alert alert-error" role="alert">{error}</p>}
        {notice && <p className="alert alert-success" role="status">{notice}</p>}

        <section className="summary-grid" aria-label="Membership summary">
          <article className="summary-card">
            <span className="summary-label">Total gymnasts</span>
            <strong>{gymnasts.length}</strong>
            <span className="summary-detail">Registered members</span>
          </article>
          <article className="summary-card">
            <span className="summary-label">Active members</span>
            <strong>{gymnasts.filter((item) => item.status === 'Active').length}</strong>
            <span className="summary-detail">Currently training</span>
          </article>
          <article className="summary-card">
            <span className="summary-label">Programs</span>
            <strong>{new Set(gymnasts.map((item) => item.training_program)).size}</strong>
            <span className="summary-detail">Training levels in use</span>
          </article>
        </section>

        <section className="panel">
          <div className="panel-heading">
            <div>
              <h2>All gymnasts</h2>
              <p>{filteredGymnasts.length} of {gymnasts.length} members</p>
            </div>
            <div className="filter-bar">
              <label className="search-field">
                <span className="visually-hidden">Search gymnasts</span>
                <input
                  type="search"
                  placeholder="Search name, ID, or email"
                  value={search}
                  onChange={(event) => setSearch(event.target.value)}
                />
              </label>
              <label>
                <span className="visually-hidden">Filter by program</span>
                <select value={programFilter} onChange={(event) => setProgramFilter(event.target.value)}>
                  <option value="">All programs</option>
                  {programs.map((program) => <option key={program}>{program}</option>)}
                </select>
              </label>
              <label>
                <span className="visually-hidden">Filter by status</span>
                <select value={statusFilter} onChange={(event) => setStatusFilter(event.target.value)}>
                  <option value="">All statuses</option>
                  {statuses.map((status) => <option key={status}>{status}</option>)}
                </select>
              </label>
            </div>
          </div>
          <div className="table-scroll">
            <table>
              <thead>
                <tr><th>Gymnast</th><th>Membership ID</th><th>Program</th><th>Enrolled</th><th>Status</th><th><span className="visually-hidden">Actions</span></th></tr>
              </thead>
              <tbody>
                {filteredGymnasts.map((gymnast) => (
                  <tr key={gymnast.id}>
                    <td>
                      <div className="member-cell">
                        <span className="avatar">{gymnast.full_name.trim().charAt(0).toUpperCase()}</span>
                        <span><strong>{gymnast.full_name}</strong><small>{gymnast.email}</small></span>
                      </div>
                    </td>
                    <td>{gymnast.membership_id}</td>
                    <td>{gymnast.training_program}</td>
                    <td>{gymnast.enrollment_date}</td>
                    <td><StatusBadge status={gymnast.status} /></td>
                    <td>
                      <div className="table-actions">
                        <button className="text-button" type="button" onClick={() => { setSelected(gymnast); setFormOpen(true); }}>Edit</button>
                        <button className="text-button danger-text" type="button" onClick={() => removeGymnast(gymnast)}>Delete</button>
                      </div>
                    </td>
                  </tr>
                ))}
                {filteredGymnasts.length === 0 && (
                  <tr><td className="empty-state" colSpan="6">
                    {gymnasts.length === 0 ? 'No gymnasts are registered yet.' : 'No members match these filters.'}
                  </td></tr>
                )}
              </tbody>
            </table>
          </div>
        </section>
        <footer className="page-footer">Gymnastics Academy <span>·</span> Membership management</footer>
      </main>
      {formOpen && (
        <GymnastForm
          key={selected?.id || 'new'}
          gymnast={selected}
          onClose={() => { setFormOpen(false); setSelected(null); }}
          onSave={saveGymnast}
          saving={saving}
        />
      )}
    </>
  );
}
