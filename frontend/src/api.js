const API_BASE = import.meta.env.PROD
  ? new URL('../../../backend/api/v1', window.location.href).toString().replace(/\/$/, '')
  : '/api/v1';

function apiError(message, status) {
  const error = new Error(message);
  error.status = status;
  return error;
}

export function getToken() {
  return sessionStorage.getItem('smrp_token');
}

export function saveToken(token) {
  if (token) sessionStorage.setItem('smrp_token', token);
  else sessionStorage.removeItem('smrp_token');
}

async function readError(response) {
  const contentType = response.headers.get('content-type') || '';
  if (contentType.includes('application/json')) {
    const body = await response.json().catch(() => null);
    if (body?.error) return body.error;
  }
  if (response.status === 401) return 'Sesioni ka përfunduar. Kyçu sërish për të vazhduar.';
  if (response.status === 403) return 'Nuk ke leje për ta kryer këtë veprim.';
  if (response.status >= 500) return 'Serveri hasi një problem. Provo sërish pas pak.';
  return `Kërkesa dështoi (${response.status}).`;
}

export async function api(path, options = {}) {
  const headers = new Headers(options.headers || {});
  const token = getToken();
  if (token) headers.set('Authorization', `Bearer ${token}`);

  if (options.body && !(options.body instanceof FormData)) {
    headers.set('Content-Type', 'application/json');
  }

  let response;
  try {
    response = await fetch(`${API_BASE}${path}`, {
      ...options,
      headers,
    });
  } catch {
    throw new Error('Nuk mund të lidhemi me serverin. Kontrollo lidhjen dhe provo sërish.');
  }

  const contentType = response.headers.get('content-type') || '';
  if (!contentType.includes('application/json')) {
    if (!response.ok) throw apiError(await readError(response), response.status);
    throw new Error('API nuk u përgjigj me JSON. Kontrollo Apache, PHP dhe adresën e backend-it.');
  }

  const body = await response.json().catch(() => null);
  if (!response.ok) {
    throw apiError(body?.error || await readError(response), response.status);
  }
  if (body === null) throw new Error('Serveri ktheu një përgjigje të pavlefshme.');
  return body;
}

export async function downloadCv(applicationId) {
  const token = getToken();
  let response;
  try {
    response = await fetch(`${API_BASE}/applications/${applicationId}/cv`, {
      headers: token ? { Authorization: `Bearer ${token}` } : {},
    });
  } catch {
    throw new Error('Nuk mund të lidhemi me serverin për të shkarkuar CV-në.');
  }

  if (!response.ok) throw new Error(await readError(response));

  const blob = await response.blob();
  const disposition = response.headers.get('content-disposition') || '';
  const match = disposition.match(/filename\*?=(?:UTF-8''|")?([^";]+)/i);
  const filename = match?.[1]
    ? decodeURIComponent(match[1].replace(/["']/g, '').trim())
    : `cv-${applicationId}`;
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = filename.replace(/[\\/:*?"<>|]/g, '_');
  document.body.append(link);
  link.click();
  link.remove();
  window.setTimeout(() => URL.revokeObjectURL(url), 30000);
}
