import { useEffect, useState } from 'react';
import { api, downloadCv, getToken, saveToken } from './api.js';

const APPLICATION_STATUSES = ['pending', 'reviewing', 'interview', 'accepted', 'rejected'];
const STATUS_LABELS = {
  open: 'E hapur',
  draft: 'Draft',
  closed: 'E mbyllur',
  pending: 'Në pritje',
  reviewing: 'Në shqyrtim',
  interview: 'Intervistë',
  accepted: 'E pranuar',
  rejected: 'E refuzuar',
};
const ROLE_LABELS = {
  applicant: 'Aplikant/e',
  hr: 'Burime njerëzore',
  manager: 'Menaxher/e punësimi',
  reviewer: 'Shqyrtues/e aplikimesh',
  admin: 'Administrator/e',
};
const ROLE_OPTIONS = ['applicant', 'reviewer', 'hr', 'manager', 'admin'];

function messageFor(error) {
  return error instanceof Error ? error.message : 'Ndodhi një gabim i papritur.';
}

function formatDate(value, options = { day: 'numeric', month: 'long', year: 'numeric' }) {
  if (!value) return '—';
  const date = new Date(value.length === 10 ? `${value}T12:00:00` : value.replace(' ', 'T'));
  return Number.isNaN(date.getTime()) ? value : new Intl.DateTimeFormat('sq-AL', options).format(date);
}

function ErrorMessage({ children, onClose }) {
  if (!children) return null;
  return (
    <div className="notice notice-error" role="alert">
      <span>{children}</span>
      {onClose && <button className="icon-button" onClick={onClose} aria-label="Mbyll njoftimin" type="button">×</button>}
    </div>
  );
}

function SuccessMessage({ children }) {
  if (!children) return null;
  return <div className="notice notice-success" role="status">{children}</div>;
}

function AuthForms({ onAuthenticated, onError }) {
  const [mode, setMode] = useState('login');
  const [busy, setBusy] = useState(false);

  async function submit(event) {
    event.preventDefault();
    setBusy(true);
    onError('');
    const form = new FormData(event.currentTarget);
    try {
      const path = mode === 'login' ? '/auth/login' : '/auth/register';
      const result = await api(path, {
        method: 'POST',
        body: JSON.stringify(Object.fromEntries(form.entries())),
      });
      saveToken(result.token);
      await onAuthenticated(result.user);
    } catch (error) {
      onError(messageFor(error));
    } finally {
      setBusy(false);
    }
  }

  return (
    <section className="auth-card" id="auth" aria-labelledby="auth-title">
      <p className="eyebrow">PORTALI YT I KARRIERËS</p>
      <h2 id="auth-title">{mode === 'login' ? 'Mirë se u ktheve' : 'Krijo llogarinë tënde'}</h2>
      <p className="auth-intro">Kyçu për të aplikuar dhe për të ndjekur ecurinë e aplikimeve.</p>
      <div className="tabs" role="group" aria-label="Zgjidh mënyrën e hyrjes">
        <button className={mode === 'login' ? 'active' : ''} aria-pressed={mode === 'login'} onClick={() => setMode('login')} type="button">Kyçu</button>
        <button className={mode === 'register' ? 'active' : ''} aria-pressed={mode === 'register'} onClick={() => setMode('register')} type="button">Regjistrohu</button>
      </div>
      <form className="form-grid" onSubmit={submit}>
        {mode === 'register' && (
          <>
            <label>Emri dhe mbiemri
              <input name="full_name" maxLength="100" autoComplete="name" required />
            </label>
            <label>Telefoni <span className="optional">(opsional)</span>
              <input name="phone_number" maxLength="30" autoComplete="tel" type="tel" />
            </label>
          </>
        )}
        <label>Email
          <input name="email" type="email" maxLength="150" autoComplete="email" required />
        </label>
        <label>Fjalëkalimi
          <input name="password" type="password" minLength="8" autoComplete={mode === 'login' ? 'current-password' : 'new-password'} required />
        </label>
        <button type="submit" disabled={busy}>
          {busy ? 'Duke u lidhur…' : mode === 'login' ? 'Kyçu në llogari' : 'Krijo llogari'}
        </button>
      </form>
      <p className="muted small auth-footnote">Regjistrimi krijon llogari aplikuesi. Rolet e ekipit caktohen nga administratori.</p>
    </section>
  );
}

function JobForm({ onCreated, onError, onNotice }) {
  const [busy, setBusy] = useState(false);
  const today = new Date().toLocaleDateString('sv-SE');

  async function submit(event) {
    event.preventDefault();
    setBusy(true);
    onError('');
    const formElement = event.currentTarget;
    const form = new FormData(formElement);
    try {
      await api('/jobs', { method: 'POST', body: JSON.stringify(Object.fromEntries(form.entries())) });
      formElement.reset();
      onNotice('Shpallja u publikua me sukses.');
      await onCreated();
    } catch (error) {
      onError(messageFor(error));
    } finally {
      setBusy(false);
    }
  }

  return (
    <section className="panel publish-panel" aria-labelledby="publish-title">
      <div className="section-title">
        <div><p className="eyebrow">EKIPI I REKRUTIMIT</p><h2 id="publish-title">Publiko një vend të ri pune</h2></div>
        <span className="panel-mark" aria-hidden="true">+</span>
      </div>
      <form className="form-grid two-columns" onSubmit={submit}>
        <label>Titulli i pozitës<input name="title" maxLength="150" placeholder="p.sh. Inxhinier/e softueri" required /></label>
        <label>Departamenti<input name="department" maxLength="100" placeholder="p.sh. Teknologji" required /></label>
        <label>Lokacioni<input name="location" maxLength="100" placeholder="Qyteti ose Remote" required /></label>
        <label>Afati i aplikimit<input name="deadline" type="date" min={today} required /></label>
        <label>Statusi i shpalljes
          <select name="status"><option value="open">E hapur</option><option value="draft">Draft</option><option value="closed">E mbyllur</option></select>
        </label>
        <label className="span-two">Përshkrimi i pozitës<textarea name="description" rows="4" maxLength="10000" placeholder="Përgjegjësitë, kërkesat dhe çfarë ofroni…" required /></label>
        <div className="span-two form-actions"><button type="submit" disabled={busy}>{busy ? 'Duke publikuar…' : 'Publiko shpalljen'}</button></div>
      </form>
    </section>
  );
}

function JobCard({ job, user, hasApplied, onApplied, onError, onNotice }) {
  const [busy, setBusy] = useState(false);
  const isApplicant = user?.role === 'applicant';
  const canApply = isApplicant && job.status === 'open' && !hasApplied;

  async function apply(event) {
    event.preventDefault();
    const formElement = event.currentTarget;
    const file = formElement.elements.cv.files?.[0];
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) {
      onError('CV-ja duhet të jetë më e vogël se 5 MB.');
      return;
    }
    if (!/\.(pdf|doc|docx)$/i.test(file.name)) {
      onError('Ngarko CV në formatin PDF, DOC ose DOCX.');
      return;
    }
    setBusy(true);
    onError('');
    const form = new FormData(formElement);
    form.set('job_id', String(job.id));
    try {
      await api('/applications', { method: 'POST', body: form });
      formElement.reset();
      onNotice('Aplikimi u dërgua me sukses. Mund ta ndjekësh statusin te aplikimet e tua.');
      await onApplied();
    } catch (error) {
      onError(messageFor(error));
    } finally {
      setBusy(false);
    }
  }

  return (
    <article className="job-card">
      <div className="job-card-top">
        <span className={`badge badge-${job.status}`}>{STATUS_LABELS[job.status] || job.status}</span>
        <span className="job-department">{job.department}</span>
      </div>
      <h3>{job.title}</h3>
      <div className="job-meta">
        <span><span aria-hidden="true">⌖</span> {job.location}</span>
        <span><span aria-hidden="true">◷</span> Afati: {formatDate(job.deadline)}</span>
      </div>
      <p className="description">{job.description}</p>
      {canApply && (
        <form className="apply-form" onSubmit={apply}>
          <label>CV-ja jote <span className="optional">(PDF, DOC ose DOCX · maks. 5 MB)</span>
            <input name="cv" type="file" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required />
          </label>
          <label>Letër motivimi <span className="optional">(opsionale)</span>
            <textarea name="cover_letter" rows="3" maxLength="10000" placeholder="Na trego shkurt pse je i/e përshtatshëm/me…" />
          </label>
          <button type="submit" disabled={busy}>{busy ? 'Duke dërguar aplikimin…' : 'Apliko për këtë pozitë'}</button>
        </form>
      )}
      {!user && job.status === 'open' && <a className="text-link apply-link" href="#auth">Kyçu për të aplikuar <span aria-hidden="true">→</span></a>}
      {hasApplied && <p className="applied-note"><span aria-hidden="true">✓</span> Ke aplikuar tashmë për këtë pozitë.</p>}
      {job.status !== 'open' && <p className="muted small">Kjo shpallje nuk pranon aplikime të reja.</p>}
    </article>
  );
}

function InterviewForm({ applicationId, onCreated, onError, onNotice }) {
  const [open, setOpen] = useState(false);
  const [busy, setBusy] = useState(false);
  const now = new Date();
  now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
  const minDateTime = now.toISOString().slice(0, 16);

  async function submit(event) {
    event.preventDefault();
    const formElement = event.currentTarget;
    setBusy(true);
    onError('');
    const form = new FormData(formElement);
    const data = Object.fromEntries(form.entries());
    data.application_id = applicationId;
    try {
      await api('/interviews', { method: 'POST', body: JSON.stringify(data) });
      formElement.reset();
      setOpen(false);
      onNotice('Intervista u planifikua dhe kandidati mund ta shohë në panelin e tij.');
      await onCreated();
    } catch (error) {
      onError(messageFor(error));
    } finally {
      setBusy(false);
    }
  }

  return (
    <div className="interview-editor">
      <button className="button-light" type="button" aria-expanded={open} onClick={() => setOpen(!open)}>
        {open ? 'Anulo planifikimin' : '＋ Planifiko intervistë'}
      </button>
      {open && (
        <form className="form-grid compact" onSubmit={submit}>
          <label>Data dhe ora<input type="datetime-local" name="scheduled_at" min={minDateTime} required /></label>
          <label>Lokacioni ose lidhja<input name="location_link" maxLength="255" placeholder="Zyrë, adresë ose URL takimi" required /></label>
          <label>Shënime për kandidatin <span className="optional">(opsionale)</span><textarea name="notes" rows="2" maxLength="10000" /></label>
          <button type="submit" disabled={busy}>{busy ? 'Duke ruajtur…' : 'Konfirmo intervistën'}</button>
        </form>
      )}
    </div>
  );
}

function ApplicationCard({ application, staff, canScheduleInterviews, onChanged, onError, onNotice }) {
  const [status, setStatus] = useState(application.status);
  const [savedStatus, setSavedStatus] = useState(application.status);
  const [busy, setBusy] = useState(false);
  const [downloading, setDownloading] = useState(false);
  useEffect(() => {
    setStatus(application.status);
    setSavedStatus(application.status);
  }, [application.status]);

  async function updateStatus() {
    if (status === savedStatus) return;
    setBusy(true);
    onError('');
    try {
      await api(`/applications/${application.id}/status`, {
        method: 'PATCH',
        body: JSON.stringify({ status }),
      });
      setSavedStatus(status);
      onNotice(`Statusi u ndryshua në “${STATUS_LABELS[status] || status}”.`);
      await onChanged();
    } catch (error) {
      setStatus(savedStatus);
      onError(messageFor(error));
    } finally {
      setBusy(false);
    }
  }

  async function getCv() {
    setDownloading(true);
    onError('');
    try {
      await downloadCv(application.id);
    } catch (error) {
      onError(messageFor(error));
    } finally {
      setDownloading(false);
    }
  }

  return (
    <article className="application-card">
      <div className="application-heading">
        <div><p className="eyebrow">{staff ? 'KANDIDATURA' : 'APLIKIMI YT'}</p><h3>{application.job_title}</h3></div>
        <span className={`badge badge-${savedStatus}`}>{STATUS_LABELS[savedStatus] || savedStatus}</span>
      </div>
      {staff && <p className="candidate-line">{application.applicant_name} <span>·</span> {application.applicant_email}</p>}
      <p className="muted small">Aplikuar më {formatDate(application.applied_at, { day: 'numeric', month: 'short', year: 'numeric' })}</p>
      {application.cover_letter && <div className="cover-letter"><h4>Letra e motivimit</h4><p>{application.cover_letter}</p></div>}
      <div className="application-actions">
        <button className="button-light" type="button" onClick={getCv} disabled={downloading}>
          {downloading ? 'Duke shkarkuar…' : '↓ Shkarko CV-në'}
        </button>
        {staff && (
          <div className="status-control">
            <label className="visually-hidden" htmlFor={`status-${application.id}`}>Statusi i aplikimit për {application.applicant_name}</label>
            <select id={`status-${application.id}`} value={status} onChange={(event) => setStatus(event.target.value)}>
              {APPLICATION_STATUSES.map((value) => <option value={value} key={value}>{STATUS_LABELS[value]}</option>)}
            </select>
            <button type="button" onClick={updateStatus} disabled={busy || status === savedStatus}>
              {busy ? 'Duke ruajtur…' : 'Ruaj statusin'}
            </button>
          </div>
        )}
      </div>
      {canScheduleInterviews && <InterviewForm applicationId={application.id} onCreated={onChanged} onError={onError} onNotice={onNotice} />}
    </article>
  );
}

function InterviewCard({ interview, staff }) {
  const isUrl = /^https?:\/\//i.test(interview.location_link || '');
  return (
    <article className="interview-card">
      <div className="interview-date" aria-hidden="true"><span>{formatDate(interview.scheduled_at, { day: '2-digit' })}</span><small>{formatDate(interview.scheduled_at, { month: 'short' })}</small></div>
      <div className="interview-details">
        <p className="eyebrow">{staff ? interview.applicant_name : 'TAKIMI YT'}</p>
        <h3>{interview.job_title}</h3>
        <p className="interview-time">{formatDate(interview.scheduled_at, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })}</p>
        {isUrl ? <a href={interview.location_link} target="_blank" rel="noreferrer">Hap lidhjen e takimit <span aria-hidden="true">↗</span></a> : <p>{interview.location_link}</p>}
        {staff && <p className="muted small">Intervistues: {interview.interviewer_name}</p>}
        {interview.notes && <p className="interview-notes">{interview.notes}</p>}
      </div>
    </article>
  );
}

function WorkspaceOverview({ user, jobs, applications, interviews, users }) {
  const staff = ['admin', 'hr', 'manager'].includes(user.role);
  const metrics = user.role === 'admin'
    ? [
      { label: 'Llogari gjithsej', value: users.length, note: 'Të gjitha rolet' },
      { label: 'Pozita aktive', value: jobs.filter((job) => job.status === 'open').length, note: 'Pranojnë aplikime' },
      { label: 'Kandidatura', value: applications.length, note: 'Në të gjitha fazat' },
      { label: 'Intervista', value: interviews.length, note: 'Të planifikuara' },
    ]
    : staff
      ? [
        { label: 'Pozita aktive', value: jobs.filter((job) => job.status === 'open').length, note: 'Shpallje të hapura' },
        { label: 'Në shqyrtim', value: applications.filter((application) => ['pending', 'reviewing'].includes(application.status)).length, note: 'Kërkojnë vëmendje' },
        { label: 'Intervista', value: interviews.length, note: 'Takime të planifikuara' },
        { label: 'Të pranuar', value: applications.filter((application) => application.status === 'accepted').length, note: 'Kandidatura të suksesshme' },
      ]
      : [
        { label: 'Aplikime gjithsej', value: applications.length, note: 'Rrugëtimi yt i punësimit' },
        { label: 'Në proces', value: applications.filter((application) => ['pending', 'reviewing'].includes(application.status)).length, note: 'Në shqyrtim' },
        { label: 'Intervista', value: interviews.length, note: 'Takime të planifikuara' },
        { label: 'Të pranuar', value: applications.filter((application) => application.status === 'accepted').length, note: 'Oferta të pranuara' },
      ];

  return (
    <section className="overview-panel" aria-label="Përmbledhje e panelit">
      <div className="overview-heading">
        <div><p className="eyebrow">{ROLE_LABELS[user.role]}</p><h2>{staff ? 'Përmbledhje e rekrutimit' : 'Përmbledhje e aplikimeve'}</h2></div>
        <span className="overview-period">Të dhëna aktuale</span>
      </div>
      <div className="metric-grid">
        {metrics.map((metric) => (
          <article className="metric-card" key={metric.label}>
            <span className="metric-value">{metric.value}</span>
            <span className="metric-label">{metric.label}</span>
            <span className="metric-note">{metric.note}</span>
          </article>
        ))}
      </div>
    </section>
  );
}

function UserRoleEditor({ account, currentUserId, onChanged, onError, onNotice }) {
  const [role, setRole] = useState(account.role);
  const [busy, setBusy] = useState(false);
  const isCurrentUser = Number(account.id) === Number(currentUserId);
  useEffect(() => setRole(account.role), [account.role]);

  async function saveRole() {
    if (role === account.role || isCurrentUser) return;
    setBusy(true);
    onError('');
    try {
      await api(`/admin/users/${account.id}/role`, {
        method: 'PATCH',
        body: JSON.stringify({ role }),
      });
      onNotice(`Roli i ${account.full_name} u ndryshua në “${ROLE_LABELS[role]}”.`);
      await onChanged();
    } catch (error) {
      setRole(account.role);
      onError(messageFor(error));
    } finally {
      setBusy(false);
    }
  }

  return (
    <div className="role-editor">
      <label className="visually-hidden" htmlFor={`role-${account.id}`}>Roli i {account.full_name}</label>
      <select id={`role-${account.id}`} value={role} disabled={isCurrentUser} onChange={(event) => setRole(event.target.value)}>
        {ROLE_OPTIONS.map((value) => <option value={value} key={value}>{ROLE_LABELS[value]}</option>)}
      </select>
      <button className="button-light" type="button" onClick={saveRole} disabled={busy || isCurrentUser || role === account.role}>
        {busy ? 'Duke ruajtur…' : 'Ruaj'}
      </button>
      {isCurrentUser && <span className="role-hint">Llogaria jote admin mbrohet.</span>}
    </div>
  );
}

function AdminUsersPanel({ users, currentUserId, onChanged, onError, onNotice }) {
  const [query, setQuery] = useState('');
  const [roleFilter, setRoleFilter] = useState('all');
  const normalizedQuery = query.trim().toLocaleLowerCase('sq');
  const filteredUsers = users.filter((account) => {
    const matchesRole = roleFilter === 'all' || account.role === roleFilter;
    const searchable = `${account.full_name} ${account.email}`.toLocaleLowerCase('sq');
    return matchesRole && (!normalizedQuery || searchable.includes(normalizedQuery));
  });

  return (
    <section className="workspace-section admin-users-section" id="users" aria-labelledby="users-title">
      <div className="section-heading">
        <div><p className="eyebrow">KONTROLLI I QASJES</p><h2 id="users-title">Llogaritë dhe rolet</h2><p className="section-description">Cakto qasje sipas përgjegjësive. Fjalëkalimet nuk shfaqen dhe llogaria jote mbrohet nga ndryshimi i rolit.</p></div>
        <span className="section-count">{users.length}</span>
      </div>
      <div className="user-filters">
        <label className="search-field">
          <span className="visually-hidden">Kërko llogari</span>
          <span className="search-icon" aria-hidden="true">⌕</span>
          <input type="search" value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Kërko sipas emrit ose email-it…" />
        </label>
        <label className="filter-field">
          <span className="visually-hidden">Filtro sipas rolit</span>
          <select value={roleFilter} onChange={(event) => setRoleFilter(event.target.value)}>
            <option value="all">Të gjitha rolet</option>
            {ROLE_OPTIONS.map((value) => <option value={value} key={value}>{ROLE_LABELS[value]}</option>)}
          </select>
        </label>
        <span className="result-count" aria-live="polite">{filteredUsers.length} llogari</span>
      </div>
      {filteredUsers.length ? (
        <div className="user-table-wrap">
          <table className="user-table">
            <thead><tr><th scope="col">Llogaria</th><th scope="col">Roli aktual</th><th scope="col">Anëtarësuar</th><th scope="col">Ndrysho rolin</th></tr></thead>
            <tbody>{filteredUsers.map((account) => (
              <tr key={account.id}>
                <td><strong>{account.full_name}</strong><span>{account.email}</span></td>
                <td><span className={`role-badge role-${account.role}`}>{ROLE_LABELS[account.role] || account.role}</span></td>
                <td>{formatDate(account.created_at, { day: 'numeric', month: 'short', year: 'numeric' })}</td>
                <td><UserRoleEditor account={account} currentUserId={currentUserId} onChanged={onChanged} onError={onError} onNotice={onNotice} /></td>
              </tr>
            ))}</tbody>
          </table>
        </div>
      ) : (
        <div className="empty-state empty-inline"><h3>Nuk u gjet asnjë llogari</h3><p>Ndrysho fjalën e kërkimit ose filtrin e rolit.</p></div>
      )}
    </section>
  );
}

async function loadWorkspace(currentUser) {
  const [jobResult, applicationResult, interviewResult, userResult] = await Promise.all([
    api('/jobs'),
    currentUser ? api('/applications') : Promise.resolve({ data: [] }),
    currentUser ? api('/interviews') : Promise.resolve({ data: [] }),
    currentUser?.role === 'admin' ? api('/admin/users') : Promise.resolve({ data: [] }),
  ]);
  return {
    jobs: Array.isArray(jobResult.data) ? jobResult.data : [],
    applications: Array.isArray(applicationResult.data) ? applicationResult.data : [],
    interviews: Array.isArray(interviewResult.data) ? interviewResult.data : [],
    users: Array.isArray(userResult.data) ? userResult.data : [],
  };
}

export default function App() {
  const [user, setUser] = useState(null);
  const [jobs, setJobs] = useState([]);
  const [applications, setApplications] = useState([]);
  const [interviews, setInterviews] = useState([]);
  const [users, setUsers] = useState([]);
  const [error, setError] = useState('');
  const [notice, setNotice] = useState('');
  const [loading, setLoading] = useState(true);
  const [workspaceLoaded, setWorkspaceLoaded] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [query, setQuery] = useState('');
  const [department, setDepartment] = useState('all');
  const [applicationQuery, setApplicationQuery] = useState('');
  const [applicationStatus, setApplicationStatus] = useState('all');

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
          if (requestError.status === 401) {
            saveToken(null);
            if (active) setError('Sesioni yt ka përfunduar. Kyçu sërish për të vazhduar.');
          } else if (active) {
            setError(messageFor(requestError));
          }
        }
      }
      try {
        const data = await loadWorkspace(currentUser);
        if (active) {
          setJobs(data.jobs);
          setApplications(data.applications);
          setInterviews(data.interviews);
          setUsers(data.users);
          setWorkspaceLoaded(true);
        }
      } catch (requestError) {
        if (active) setError(messageFor(requestError));
      } finally {
        if (active) setLoading(false);
      }
    }
    initialize();
    return () => { active = false; };
  }, []);

  async function refresh(currentUser = user) {
    setRefreshing(true);
    setError('');
    try {
      const data = await loadWorkspace(currentUser);
      setJobs(data.jobs);
      setApplications(data.applications);
      setInterviews(data.interviews);
      setUsers(data.users);
      setWorkspaceLoaded(true);
    } catch (requestError) {
      setError(messageFor(requestError));
      throw requestError;
    } finally {
      setRefreshing(false);
    }
  }

  async function reload() {
    try {
      await refresh(user);
    } catch {
      // The error is shown in the page banner.
    }
  }

  async function signIn(nextUser) {
    setUser(nextUser);
    setNotice(`Mirë se erdhe, ${nextUser.full_name}.`);
    await refresh(nextUser);
  }

  async function signOut() {
    saveToken(null);
    setUser(null);
    setApplications([]);
    setInterviews([]);
    setUsers([]);
    setNotice('Ke dalë me sukses nga llogaria.');
    try {
      await refresh(null);
    } catch {
      // Public jobs remain visible even if refreshing them fails.
    }
  }

  const staff = user && ['admin', 'hr', 'manager', 'reviewer'].includes(user.role);
  const canManageJobs = user && ['admin', 'hr', 'manager'].includes(user.role);
  const canScheduleInterviews = canManageJobs;
  const administrator = user?.role === 'admin';
  const reviewer = user?.role === 'reviewer';
  const departments = [...new Set(jobs.map((job) => job.department).filter(Boolean))].sort((a, b) => a.localeCompare(b, 'sq'));
  const normalizedQuery = query.trim().toLocaleLowerCase('sq');
  const visibleJobs = jobs.filter((job) => {
    const matchesDepartment = department === 'all' || job.department === department;
    const searchable = `${job.title} ${job.department} ${job.location} ${job.description}`.toLocaleLowerCase('sq');
    return matchesDepartment && (!normalizedQuery || searchable.includes(normalizedQuery));
  });
  const appliedJobIds = new Set(applications.map((application) => String(application.job_id)));
  const normalizedApplicationQuery = applicationQuery.trim().toLocaleLowerCase('sq');
  const visibleApplications = applications.filter((application) => {
    const matchesStatus = applicationStatus === 'all' || application.status === applicationStatus;
    const searchable = `${application.job_title} ${application.applicant_name || ''} ${application.applicant_email || ''}`.toLocaleLowerCase('sq');
    return matchesStatus && (!normalizedApplicationQuery || searchable.includes(normalizedApplicationQuery));
  });
  const openCount = jobs.filter((job) => job.status === 'open').length;

  return (
    <div className="app-shell">
      <header className="site-header">
        <div className="header-inner">
          <a className="brand" href="#top" aria-label="SMRP, faqja kryesore">
            <span className="brand-mark" aria-hidden="true">S</span>
            <span className="brand-copy">SMRP <small>CAREERS</small></span>
          </a>
          <nav className="main-nav" aria-label="Navigimi kryesor">
            <a href="#jobs">Mundësitë</a>
            {user && <a href="#applications">{staff ? 'Kandidatët' : 'Aplikimet e mia'}</a>}
            {user && !reviewer && <a href="#interviews">Intervistat</a>}
            {administrator && <a href="#users">Llogaritë</a>}
          </nav>
          <div className="account">
            {user ? (
              <>
                <span className="account-name">{user.full_name}<small>{ROLE_LABELS[user.role] || user.role}</small></span>
                <button className="button-light sign-out" onClick={signOut} type="button">Dil nga llogaria</button>
              </>
            ) : <a className="header-login" href="#auth">Kyçu <span aria-hidden="true">→</span></a>}
          </div>
        </div>
      </header>

      <main id="top">
        <div className="page-container">
          <ErrorMessage onClose={() => setError('')}>{error}</ErrorMessage>
          <SuccessMessage>{notice}</SuccessMessage>

          <section className={`hero ${user ? 'hero-compact' : ''}`} aria-labelledby="hero-title">
            <div className="hero-copy">
              <p className="eyebrow hero-eyebrow"><span className="eyebrow-dot" /> KARRIERA JOTE, HAPI TJETËR</p>
              <h1 id="hero-title">{staff ? <>Gjeni talentin<br /><em>që çon përpara.</em></> : <>Talenti i duhur.<br /><em>Mundësia e radhës.</em></>}</h1>
              <p className="hero-description">{reviewer ? 'Shqyrto kandidaturat, shkarko CV-të dhe përditëso fazat e përzgjedhjes.' : staff ? 'Menaxho shpalljet, shqyrto kandidaturat dhe organizo intervista — gjithçka në një vend.' : 'Zbulo mundësi të reja, apliko me lehtësi dhe ndiq çdo hap të rrugëtimit tënd profesional.'}</p>
              <div className="hero-highlights">
                <span><strong>{openCount}</strong> pozita të hapura</span>
                <span className="highlight-divider" aria-hidden="true" />
                <span>Proces transparent, nga fillimi te oferta</span>
              </div>
            </div>
            {!user && !loading && <AuthForms onAuthenticated={signIn} onError={setError} />}
            {user && (
              <div className="welcome-panel">
                <p className="eyebrow">PANELI PERSONAL</p>
                <h2>Përshëndetje, {user.full_name.split(' ')[0]}.</h2>
                <p>{staff ? 'Paneli yt i rekrutimit është gati.' : 'Vazhdo kërkimin për mundësinë tënde të radhës.'}</p>
                <a className="text-link" href={staff ? '#applications' : '#jobs'}>{staff ? 'Shko te kandidatët' : 'Shiko pozitat e hapura'} <span aria-hidden="true">→</span></a>
              </div>
            )}
          </section>

          {user && <WorkspaceOverview user={user} jobs={jobs} applications={applications} interviews={interviews} users={users} />}
          {canManageJobs && <JobForm onCreated={reload} onError={setError} onNotice={setNotice} />}
          {administrator && <AdminUsersPanel users={users} currentUserId={user.id} onChanged={reload} onError={setError} onNotice={setNotice} />}

          <section className="jobs-section" id="jobs" aria-labelledby="jobs-title">
            <div className="section-heading">
              <div><p className="eyebrow">MUNDËSI TË REJA</p><h2 id="jobs-title">{canManageJobs ? 'Shpalljet e punës' : 'Pozitat e hapura'}</h2><p className="section-description">{canManageJobs ? 'Menaxho dhe ndiq statusin e shpalljeve aktive.' : reviewer ? 'Shiko mundësitë dhe përqendrohu te shqyrtimi i kandidatëve.' : 'Gjej vendin ku aftësitë dhe ambiciet e tua bëjnë diferencën.'}</p></div>
              <button className="button-light refresh-button" type="button" onClick={reload} disabled={refreshing} aria-label="Rifresko përmbajtjen">
                <span aria-hidden="true">↻</span> {refreshing ? 'Duke rifreskuar…' : 'Rifresko'}
              </button>
            </div>
            {!loading && jobs.length > 0 && (
              <div className="job-filters">
                <label className="search-field">
                  <span className="visually-hidden">Kërko pozita pune</span>
                  <span className="search-icon" aria-hidden="true">⌕</span>
                  <input type="search" value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Kërko sipas pozitës, fjalës kyçe…" />
                </label>
                <label className="filter-field">
                  <span className="visually-hidden">Filtro sipas departamentit</span>
                  <select value={department} onChange={(event) => setDepartment(event.target.value)}>
                    <option value="all">Të gjitha departamentet</option>
                    {departments.map((value) => <option value={value} key={value}>{value}</option>)}
                  </select>
                </label>
                <span className="result-count" aria-live="polite">{visibleJobs.length} {visibleJobs.length === 1 ? 'pozitë' : 'pozita'}</span>
              </div>
            )}
            {loading ? (
              <div className="loading-state" role="status"><span className="spinner" aria-hidden="true" /> Po ngarkohen mundësitë…</div>
            ) : !workspaceLoaded ? (
              <div className="empty-state"><h3>Nuk u ngarkuan mundësitë</h3><p>Kontrollo lidhjen me serverin dhe provo përsëri.</p><button className="button-light" type="button" onClick={reload}>Provo përsëri</button></div>
            ) : visibleJobs.length ? (
              <div className="job-grid">
                {visibleJobs.map((job) => <JobCard key={job.id} job={job} user={user} hasApplied={appliedJobIds.has(String(job.id))} onApplied={reload} onError={setError} onNotice={setNotice} />)}
              </div>
            ) : (
              <div className="empty-state">
                <span className="empty-icon" aria-hidden="true">⌕</span>
                <h3>{jobs.length ? 'Nuk u gjet asnjë pozitë' : 'Nuk ka pozita të hapura për momentin'}</h3>
                <p>{jobs.length ? 'Provo një kërkim tjetër ose ndrysho filtrin e departamentit.' : 'Kthehu së shpejti — mundësi të reja publikohen rregullisht.'}</p>
                {jobs.length > 0 && <button className="button-light" type="button" onClick={() => { setQuery(''); setDepartment('all'); }}>Pastro filtrat</button>}
              </div>
            )}
          </section>

          {user && (
            <div className="workspace-sections">
              <section id="applications" className="workspace-section" aria-labelledby="applications-title">
                <div className="section-heading">
                  <div><p className="eyebrow">{staff ? 'MENAXHIMI I TALENTIT' : 'PROCESI YT'}</p><h2 id="applications-title">{staff ? 'Aplikimet e kandidatëve' : 'Aplikimet e mia'}</h2><p className="section-description">{staff ? 'Shqyrto aplikimet dhe mbaj procesin në lëvizje.' : 'Statusi më i fundit për mundësitë që ke zgjedhur.'}</p></div>
                  <span className="section-count">{applications.length}</span>
                </div>
                {applications.length > 0 && (
                  <div className="application-filters">
                    <label className="search-field">
                      <span className="visually-hidden">{staff ? 'Kërko kandidatura' : 'Kërko aplikimet e mia'}</span>
                      <span className="search-icon" aria-hidden="true">⌕</span>
                      <input type="search" value={applicationQuery} onChange={(event) => setApplicationQuery(event.target.value)} placeholder={staff ? 'Kërko emër, email ose pozitë…' : 'Kërko sipas pozitës…'} />
                    </label>
                    <label className="filter-field">
                      <span className="visually-hidden">Filtro aplikimet sipas statusit</span>
                      <select value={applicationStatus} onChange={(event) => setApplicationStatus(event.target.value)}>
                        <option value="all">Të gjitha fazat</option>
                        {APPLICATION_STATUSES.map((value) => <option value={value} key={value}>{STATUS_LABELS[value]}</option>)}
                      </select>
                    </label>
                    <span className="result-count" aria-live="polite">{visibleApplications.length} nga {applications.length}</span>
                  </div>
                )}
                {loading ? <div className="loading-state"><span className="spinner" aria-hidden="true" /> Po ngarkohen aplikimet…</div> : !workspaceLoaded ? <div className="empty-state empty-inline"><h3>Nuk u ngarkuan aplikimet</h3><p>Provo përsëri pas kontrollit të lidhjes.</p><button className="button-light" type="button" onClick={reload}>Provo përsëri</button></div> : applications.length ? (
                  visibleApplications.length ? <div className="application-grid">{visibleApplications.map((application) => <ApplicationCard key={application.id} application={application} staff={Boolean(staff)} canScheduleInterviews={Boolean(canScheduleInterviews)} onChanged={reload} onError={setError} onNotice={setNotice} />)}</div> : (
                    <div className="empty-state empty-inline"><h3>Nuk ka aplikime në këtë filtër</h3><p>Ndrysho kriteret e kërkimit ose pastro filtrin e statusit.</p><button className="button-light" type="button" onClick={() => { setApplicationQuery(''); setApplicationStatus('all'); }}>Pastro filtrat</button></div>
                  )
                ) : (
                  <div className="empty-state empty-inline"><h3>{staff ? 'Ende nuk ka kandidatura' : 'Ende nuk ke aplikuar'}</h3><p>{staff ? 'Aplikimet e reja do të shfaqen këtu.' : 'Eksploro pozitat e hapura dhe nis rrugëtimin tënd.'}</p>{!staff && <a className="text-link" href="#jobs">Shiko pozitat <span aria-hidden="true">→</span></a>}</div>
                )}
              </section>

              {!reviewer && <section id="interviews" className="workspace-section" aria-labelledby="interviews-title">
                <div className="section-heading">
                  <div><p className="eyebrow">HAPI TJETËR</p><h2 id="interviews-title">Intervistat</h2><p className="section-description">{staff ? 'Intervistat e planifikuara me kandidatët.' : 'Takimet e ardhshme gjatë procesit të përzgjedhjes.'}</p></div>
                  <span className="section-count">{interviews.length}</span>
                </div>
                {loading ? <div className="loading-state"><span className="spinner" aria-hidden="true" /> Po ngarkohen intervistat…</div> : !workspaceLoaded ? <div className="empty-state empty-inline"><h3>Nuk u ngarkuan intervistat</h3><p>Provo përsëri pas kontrollit të lidhjes.</p><button className="button-light" type="button" onClick={reload}>Provo përsëri</button></div> : interviews.length ? (
                  <div className="interview-grid">{interviews.map((interview) => <InterviewCard key={interview.id} interview={interview} staff={Boolean(staff)} />)}</div>
                ) : <div className="empty-state empty-inline"><h3>Nuk ka intervista të planifikuara</h3><p>{staff ? 'Planifiko një intervistë nga aplikimi i kandidatit përkatës.' : 'Nëse ftohesh për intervistë, detajet do të shfaqen këtu.'}</p></div>}
              </section>}
            </div>
          )}
        </div>
      </main>

      <footer className="site-footer">
        <div className="footer-inner">
          <a className="brand footer-brand" href="#top"><span className="brand-mark" aria-hidden="true">S</span><span className="brand-copy">SMRP <small>CAREERS</small></span></a>
          <p>Një proces më i qartë. Një mundësi më e mirë.</p>
          <span>© {new Date().getFullYear()} SMRP</span>
        </div>
      </footer>
    </div>
  );
}
