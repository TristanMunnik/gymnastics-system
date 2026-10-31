import { useState } from 'react';

const programs = ['Beginner', 'Intermediate', 'Advanced'];
const statuses = ['Active', 'On Hold', 'Completed', 'Pending'];

// Starting values for a new member; editing overlays the chosen gymnast's data on top.
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

export default function GymnastForm({ gymnast, onClose, onSave, saving }) {
  // The function form of useState runs once, on first render. The password is always blank:
  // the API never returns it, and edits do not change it.
  const [form, setForm] = useState(() => ({
    ...emptyForm,
    ...(gymnast || {}),
    password: '',
  }));
  const [error, setError] = useState('');

  // One handler for every input: the input's name attribute matches its key in `form`.
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
    <div
      className="modal-backdrop"
      role="presentation"
      // Clicking the dark backdrop closes the form, but clicks inside the dialog do not.
      onMouseDown={(event) => {
        if (event.target === event.currentTarget) onClose();
      }}
    >
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
                <input
                  name="password"
                  type="password"
                  value={form.password}
                  onChange={updateField}
                  required
                  minLength="8"
                  autoComplete="new-password"
                />
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
