import { useCallback, useEffect, useState } from 'react';
import { api, downloadCv, getToken, saveToken } from './api.js';

const APPLICATION_STATUSES = ['pending', 'reviewing', 'interview', 'accepted', 'rejected'];

function ErrorMessage({ children, onClose }) {
  if (!children) return null;
  return (
    <div className="notice" role="status">
      <span>{children}</span>
      {onClose && <button className="icon-button" onClick={onClose} aria-label="Mbyll">×</button>}
    </div>
  );
}

function AuthForms({ onAuthenticated, onError }) {
  const [mode, setMode] = useState('login');
  const [busy, setBusy] = useState(false);

  async function submit(event) {
    event.preventDefault();
    setBusy(true);
    const form = new FormData(event.currentTarget);
    try {
      const path = mode === 'login' ? '/auth/login' : '/auth/register';
      const result = await api(path, {
        method: 'POST',
        body: JSON.stringify(Object.fromEntries(form.entries())),
      });
      saveToken(result.token);
      onAuthenticated(result.user);
      onError('');
    } catch (error) {
      onError(error.message);
    } finally {
      setBusy(false);
    }
  }

  return (
    <section className="auth-card">
      <div className="tabs" role="tablist">
        <button className={mode === 'login' ? 'active' : ''} onClick={() => setMode('login')} type="button">Kyçu</button>
        <button className={mode === 'register' ? 'active' : ''} onClick={() => setMode('register')} type="button">Regjistrohu</button>
      </div>
      <form className="form-grid" onSubmit={submit}>
        {mode === 'register' && (
          <>
            <label>Emri dhe mbiemri
              <input name="full_name" maxLength="100" autoComplete="name" required />
            </label>
            <label>Telefoni (opsional)
              <input name="phone_number" maxLength="30" autoComplete="tel" />
            </label>
          </>
        )}
        <label>Email
          <input name="email" type="email" maxLength="150" autoComplete="email" required />
        </label>
        <label>Fjalëkalimi
          <input name="password" type="password" minLength="8" autoComplete={mode === 'login' ? 'current-password' : 'new-password'} required />
        </label>
        <button type="submit" disabled={busy}>{busy ? 'Duke pritur…' : mode === 'login' ? 'Kyçu' : 'Krijo llogari'}</button>
      </form>
      <p className="muted small">Regjistrimi krijon llogari aplikuesi. Rolin HR/admin e cakton administratori në databazë.</p>
    </section>
  );
}

function JobForm({ onCreated, onError }) {
  const [busy, setBusy] = useState(false);

  async function submit(event) {
    event.preventDefault();
    setBusy(true);
    const formElement = event.currentTarget;
    const form = new FormData(event.currentTarget);
    try {
      await api('/jobs', { method: 'POST', body: JSON.stringify(Object.fromEntries(form.entries())) });
      formElement.reset();
      onError('');
      onCreated();
    } catch (error) {
      onError(error.message);
    } finally {
      setBusy(false);
    }
  }

  return (
    <section className="panel">
      <h2>Publiko vend pune</h2>
      <form className="form-grid two-columns" onSubmit={submit}>
        <label>Titulli<input name="title" maxLength="150" required /></label>
        <label>Departamenti<input name="department" maxLength="100" required /></label>
        <label>Lokacioni<input name="location" maxLength="100" required /></label>
        <label>Afati<input name="deadline" type="date" required /></label>
        <label>Statusi
          <select name="status"><option value="open">E hapur</option><option value="draft">Draft</option><option value="closed">E mbyllur</option></select>
        </label>
        <label className="span-two">Përshkrimi<textarea name="description" rows="4" maxLength="10000" required /></label>
        <button type="submit" disabled={busy}>{busy ? 'Duke ruajtur…' : 'Ruaj shpalljen'}</button>
      </form>
    </section>
  );
}

function JobCard({ job, user, onApplied, onError }) {
  const [busy, setBusy] = useState(false);
  const isApplicant = user?.role === 'applicant';

  async function apply(event) {
    event.preventDefault();
    setBusy(true);
    const form = new FormData(event.currentTarget);
    form.set('job_id', String(job.id));
    try {
      await api('/applications', { method: 'POST', body: form });
      onError('');
      onApplied();
    } catch (error) {
      onError(error.message);
    } finally {
      setBusy(false);
    }
  }

  return (
    <article className="card">
      <div className="card-heading">
        <span className="badge">{job.status}</span>
        <h3>{job.title}</h3>
      </div>
      <p className="muted">{job.department} · {job.location} · Afati: {job.deadline}</p>
      <p className="description">{job.description}</p>
      {isApplicant && job.status === 'open' && (
        <form className="form-grid compact" onSubmit={apply}>
          <label>CV (PDF, DOC ose DOCX; maksimum 5 MB)
            <input name="cv" type="file" accept=".pdf,.doc,.docx" required />
          </label>
          <label>Letër motivimi (opsionale)
            <textarea name="cover_letter" rows="3" maxLength="10000" />
          </label>
          <button type="submit" disabled={busy}>{busy ? 'Duke dërguar…' : 'Apliko'}</button>
        </form>
      )}
    </article>
  );
}

function ApplicationCard({ application, staff, onChanged, onError }) {
  const [status, setStatus] = useState(application.status);
  const [busy, setBusy] = useState(false);
  const [downloading, setDownloading] = useState(false);

  useEffect(() => setStatus(application.status), [application.status]);

  async function updateStatus() {
    setBusy(true);
    try {
      await api(`/applications/${application.id}/status`, {
        method: 'PATCH',
        body: JSON.stringify({ status }),
      });
      onError('');
      onChanged();
    } catch (error) {
      onError(error.message);
    } finally {
      setBusy(false);
    }
  }

  async function getCv() {
    setDownloading(true);
    try {
      await downloadCv(application.id);
      onError('');
    } catch (error) {
      onError(error.message);
    } finally {
      setDownloading(false);
    }
  }

  return (
    <article className="card">
      <div className="card-heading">
        <h3>{application.job_title}</h3>
        <span className="badge">{application.status}</span>
      </div>
      {staff && <p className="muted">{application.applicant_name} · {application.applicant_email}</p>}
      <p className="muted">Aplikuar: {application.applied_at}</p>
      {application.cover_letter && <p>{application.cover_letter}</p>}
      <div className="actions">
        <button className="button-light" type="button" onClick={getCv} disabled={downloading}>
          {downloading ? 'Duke shkarkuar…' : 'Shkarko CV-në'}
        </button>
        {staff && (
          <>
            <select aria-label="Statusi i aplikimit" value={status} onChange={(event) => setStatus(event.target.value)}>
              {APPLICATION_STATUSES.map((value) => <option value={value} key={value}>{value}</option>)}
            </select>
            <button type="button" onClick={updateStatus} disabled={busy}>Përditëso statusin</button>
          </>
        )}
      </div>
      {staff && <InterviewForm applicationId={application.id} onCreated={onChanged} onError={onError} />}
    </article>
  );
}

function InterviewForm({ applicationId, onCreated, onError }) {
  const [open, setOpen] = useState(false);
  const [busy, setBusy] = useState(false);

  async function submit(event) {
    event.preventDefault();
    setBusy(true);
    const formElement = event.currentTarget;
    const form = new FormData(event.currentTarget);
    const data = Object.fromEntries(form.entries());
    data.application_id = applicationId;
    try {
      await api('/interviews', { method: 'POST', body: JSON.stringify(data) });
      formElement.reset();
      onError('');
      onCreated();
      setOpen(false);
    } catch (error) {
      onError(error.message);
    } finally {
      setBusy(false);
    }
  }

  return (
    <div className="interview-editor">
      <button className="button-light" type="button" onClick={() => setOpen(!open)}>
        {open ? 'Mbyll' : 'Planifiko intervistë'}
      </button>
      {open && (
        <form className="form-grid compact" onSubmit={submit}>
          <label>Data dhe ora<input type="datetime-local" name="scheduled_at" required /></label>
          <label>Lokacioni ose lidhja<input name="location_link" maxLength="255" required /></label>
          <label>Shënime (opsionale)<textarea name="notes" rows="2" maxLength="10000" /></label>
          <button type="submit" disabled={busy}>{busy ? 'Duke ruajtur…' : 'Ruaj intervistën'}</button>
        </form>
      )}
    </div>
  );
}

export default function App() {
  const [user, setUser] = useState(null);
  const [jobs, setJobs] = useState([]);
  const [applications, setApplications] = useState([]);
  const [interviews, setInterviews] = useState([]);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);

  const refresh = useCallback(async (currentUser = user) => {
    const [jobResult, applicationResult, interviewResult] = await Promise.all([
      api('/jobs'),
      currentUser ? api('/applications') : Promise.resolve({ data: [] }),
      currentUser ? api('/interviews') : Promise.resolve({ data: [] }),
    ]);
    setJobs(jobResult.data);
    setApplications(applicationResult.data);
    setInterviews(interviewResult.data);
  }, [user]);

  useEffect(() => {
    let active = true;
    async function initialize() {
      let currentUser = null;
      if (getToken()) {
        try {
          const result = await api('/auth/me');
          currentUser = result.user;
          if (active) setUser(currentUser);
        } catch (requestError) {
          saveToken(null);
          if (active) setError(requestError.message);
        }
      }
      try {
        await refresh(currentUser);
      } catch (requestError) {
        if (active) setError(requestError.message);
      }
      if (active) setLoading(false);
    }
    initialize();
    return () => { active = false; };
  }, []);

  async function reload() {
    try {
      await refresh();
    } catch (requestError) {
      setError(requestError.message);
    }
  }

  function signOut() {
    saveToken(null);
    setUser(null);
    setApplications([]);
    setInterviews([]);
    refresh(null).catch((requestError) => setError(requestError.message));
  }

  const staff = user && ['admin', 'hr'].includes(user.role);

  return (
    <>
      <header className="site-header">
        <div className="header-inner">
          <a className="brand" href="./">SMRP <span>Menaxhimi i Rekrutimit</span></a>
          <div className="account">
            {user ? <><span>{user.full_name} <small>({user.role})</small></span><button className="button-light" onClick={signOut}>Dil</button></> : <span>Portal i punësimit</span>}
          </div>
        </div>
      </header>
      <main className="container">
        <ErrorMessage>{error}</ErrorMessage>
        {!user && !loading && <AuthForms onAuthenticated={(value) => { setUser(value); refresh(value).catch((e) => setError(e.message)); }} onError={setError} />}
        {staff && <JobForm onCreated={reload} onError={setError} />}
        <section>
          <div className="section-title">
            <div><p className="eyebrow">MUNDËSITË E PUNËS</p><h1>{staff ? 'Shpalljet e punës' : 'Shpalljet e hapura'}</h1></div>
            <button className="button-light" onClick={reload}>Rifresko</button>
          </div>
          {loading ? <p className="muted">Duke ngarkuar…</p> : jobs.length
            ? <div className="card-grid">{jobs.map((job) => <JobCard key={job.id} job={job} user={user} onApplied={reload} onError={setError} />)}</div>
            : <p className="empty-state">Nuk ka shpallje të hapura për momentin.</p>}
        </section>
        {user && (
          <>
            <section>
              <div className="section-title"><div><p className="eyebrow">{staff ? 'MENAXHIMI' : 'PROCESI YT'}</p><h2>{staff ? 'Aplikimet e kandidatëve' : 'Aplikimet e mia'}</h2></div></div>
              {applications.length
                ? <div className="card-grid">{applications.map((application) => <ApplicationCard key={application.id} application={application} staff={Boolean(staff)} onChanged={reload} onError={setError} />)}</div>
                : <p className="empty-state">Nuk ka aplikime për momentin.</p>}
            </section>
            <section>
              <div className="section-title"><div><p className="eyebrow">TAKIMET</p><h2>Intervistat</h2></div></div>
              {interviews.length
                ? <div className="card-grid">{interviews.map((interview) => (
                    <article className="card" key={interview.id}>
                      <h3>{interview.job_title}</h3>
                      <p><strong>{interview.scheduled_at}</strong></p>
                      <p>{/^https?:\/\//i.test(interview.location_link)
                        ? <a href={interview.location_link} target="_blank" rel="noreferrer">{interview.location_link}</a>
                        : interview.location_link}</p>
                      <p className="muted">Kandidat: {interview.applicant_name} · Intervistues: {interview.interviewer_name}</p>
                      {interview.notes && <p>{interview.notes}</p>}
                    </article>
                  ))}</div>
                : <p className="empty-state">Nuk ka intervista të planifikuara.</p>}
            </section>
          </>
        )}
      </main>
      <footer className="site-footer">SMRP · Sistemi për Menaxhimin e Rekrutimit të Punonjësve</footer>
    </>
  );
}
