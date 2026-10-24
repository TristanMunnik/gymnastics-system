import { useEffect, useMemo, useState } from 'react';
import {
  createGymnast,
  deleteGymnast,
  getGymnasts,
  getSession,
  updateGymnast,
} from './api.js';

const programs = ['Beginner', 'Intermediate', 'Advanced'];
const statuses = ['Active', 'On Hold', 'Completed', 'Pending'];
const phpAppUrl = import.meta.env.DEV
  ? `${import.meta.env.VITE_PHP_APP_URL || 'http://localhost/gymnastics/php'}/`
  : '../';
const emptyForm = {
  full_name: '',
  email: '',
  membership_id: '',
  contact_no: '',
  date_of_birth: '',
  training_program: 'Beginner',
  enrollment_date: new Date().toISOString().slice(0, 10),
  password: '',
};

function StatusBadge({ status }) {
  const className = status.toLowerCase().replace(/\s+/g, '');
  return <span className={`badge badge-${className}`}>{status}</span>;
}

function GymnastForm({ gymnast, onClose, onSave, saving }) {
  const [form, setForm] = useState(() => ({
    ...emptyForm,
    ...(gymnast || {}),
    password: '',
  }));
  const [error, setError] = useState('');

  function updateField(event) {
    setForm((current) => ({ ...current, [event.target.name]: event.target.value }));
  }

  async function handleSubmit(event) {
    event.preventDefault();
    setError('');
    try {
      await onSave(form);
    } catch (saveError) {
      setError(saveError.message);
    }
  }

  return (
    <div className="modal-backdrop" role="presentation" onMouseDown={(event) => {
      if (event.target === event.currentTarget) onClose();
    }}>
      <section className="modal card" role="dialog" aria-modal="true" aria-labelledby="form-title">
        <div className="modal-heading">
          <div>
            <p className="eyebrow">{gymnast ? 'MEMBER DETAILS' : 'NEW ENROLLMENT'}</p>
            <h2 id="form-title">{gymnast ? 'Edit gymnast' : 'Register a gymnast'}</h2>
          </div>
          <button className="icon-button" type="button" onClick={onClose} aria-label="Close form">×</button>
        </div>
        {error && <p className="alert alert-error" role="alert">{error}</p>}
        <form onSubmit={handleSubmit}>
          <div className="form-grid">
            <label className="form-group">
              Full name
              <input name="full_name" value={form.full_name} onChange={updateField} required maxLength="120" />
            </label>
            <label className="form-group">
              Membership ID
              <input name="membership_id" value={form.membership_id} onChange={updateField} required maxLength="40" />
            </label>
            <label className="form-group">
              Email
              <input name="email" type="email" value={form.email} onChange={updateField} required maxLength="190" />
            </label>
            <label className="form-group">
              Contact number
              <input name="contact_no" value={form.contact_no} onChange={updateField} required maxLength="40" />
            </label>
            <label className="form-group">
              Date of birth
              <input name="date_of_birth" type="date" value={form.date_of_birth} onChange={updateField} required />
            </label>
            <label className="form-group">
              Enrollment date
              <input name="enrollment_date" type="date" value={form.enrollment_date} onChange={updateField} required />
            </label>
            <label className="form-group">
              Training program
              <select name="training_program" value={form.training_program} onChange={updateField} required>
                {programs.map((program) => <option key={program}>{program}</option>)}
              </select>
            </label>
            {gymnast ? (
              <label className="form-group">
                Membership status
                <select name="status" value={form.status} onChange={updateField} required>
                  {statuses.map((status) => <option key={status}>{status}</option>)}
                </select>
              </label>
            ) : (
              <label className="form-group form-group-wide">
                Temporary password
                <input name="password" type="password" value={form.password} onChange={updateField} required minLength="8" autoComplete="new-password" />
                <span className="field-hint">Use at least 8 characters, then share it securely with the member.</span>
              </label>
            )}
          </div>
          <div className="modal-actions">
            <button className="btn btn-outline" type="button" onClick={onClose}>Cancel</button>
            <button className="btn btn-primary" type="submit" disabled={saving}>
              {saving ? 'Saving…' : gymnast ? 'Save changes' : 'Register member'}
            </button>
          </div>
        </form>
      </section>
    </div>
  );
}

function App() {
  const [user, setUser] = useState(null);
  const [gymnasts, setGymnasts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [notice, setNotice] = useState('');
  const [search, setSearch] = useState('');
  const [programFilter, setProgramFilter] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [selected, setSelected] = useState(null);
  const [formOpen, setFormOpen] = useState(false);

  useEffect(() => {
    async function loadDashboard() {
      try {
        const session = await getSession();
        setUser(session.user);
        setGymnasts(await getGymnasts());
      } catch (loadError) {
        setError(loadError.message);
      } finally {
        setLoading(false);
      }
    }
    loadDashboard();
  }, []);

  const filteredGymnasts = useMemo(() => {
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
        const updated = await updateGymnast(selected.id, data, user.csrfToken);
        setGymnasts((current) => current.map((item) => item.id === updated.id ? updated : item));
        setNotice('Gymnast details have been updated.');
      } else {
        const created = await createGymnast(form, user.csrfToken);
        setGymnasts((current) => [created, ...current]);
        setNotice('Gymnast has been registered.');
      }
      setFormOpen(false);
      setSelected(null);
    } catch (saveError) {
      throw saveError;
    } finally {
      setSaving(false);
    }
  }

  async function removeGymnast(gymnast) {
    if (!window.confirm(`Delete ${gymnast.full_name}'s membership? This cannot be undone.`)) return;
    setError('');
    setNotice('');
    try {
      await deleteGymnast(gymnast.id, user.csrfToken);
      setGymnasts((current) => current.filter((item) => item.id !== gymnast.id));
      setNotice(`${gymnast.full_name} has been deleted.`);
    } catch (deleteError) {
      setError(deleteError.message);
    }
  }

  function openCreateForm() {
    setSelected(null);
    setFormOpen(true);
  }

  function openEditForm(gymnast) {
    setSelected(gymnast);
    setFormOpen(true);
  }

  if (loading) {
    return <main className="loading-screen"><span className="spinner" />Loading your dashboard…</main>;
  }

  return (
    <>
      <header className="navbar">
        <a className="navbar-brand" href="./index.html"><span className="brand-mark">G</span> Gymnastics Academy</a>
        <nav className="navbar-links" aria-label="Main navigation">
          <span className="user-name">{user?.full_name}</span>
          <a href={`${phpAppUrl}gymnast.php`}>Member portal</a>
          <a className="logout-link" href={`${phpAppUrl}logout.php`}>Sign out</a>
        </nav>
      </header>
      <main className="container">
        <section className="welcome-row">
          <div>
            <p className="eyebrow">ACADEMY MANAGEMENT</p>
            <h1>Gymnast dashboard</h1>
            <p className="welcome-copy">Manage memberships, programs, and enrollment details.</p>
          </div>
          <button className="btn btn-primary" type="button" onClick={openCreateForm}>
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
                <tr>
                  <th>Gymnast</th>
                  <th>Membership ID</th>
                  <th>Program</th>
                  <th>Enrolled</th>
                  <th>Status</th>
                  <th><span className="visually-hidden">Actions</span></th>
                </tr>
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
                        <button className="text-button" type="button" onClick={() => openEditForm(gymnast)}>Edit</button>
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

export default App;
