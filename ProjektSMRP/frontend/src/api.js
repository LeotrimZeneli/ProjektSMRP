const API_BASE = import.meta.env.PROD
  ? new URL('../../../backend/api/v1', window.location.href).toString()
  : '/api/v1';

export function getToken() {
  return sessionStorage.getItem('smrp_token');
}

export function saveToken(token) {
  if (token) sessionStorage.setItem('smrp_token', token);
  else sessionStorage.removeItem('smrp_token');
}

export async function api(path, options = {}) {
  const headers = new Headers(options.headers || {});
  const token = getToken();
  if (token) headers.set('Authorization', `Bearer ${token}`);

  if (options.body && !(options.body instanceof FormData)) {
    headers.set('Content-Type', 'application/json');
  }

  const response = await fetch(`${API_BASE}${path}`, {
    ...options,
    headers,
  });
  const contentType = response.headers.get('content-type') || '';
  if (!contentType.includes('application/json')) {
    throw new Error('API nuk u përgjigj me JSON. Kontrollo Apache, PHP dhe adresën e backend-it.');
  }
  const body = await response.json();

  if (!response.ok) {
    throw new Error(body?.error || `Kërkesa dështoi (${response.status}).`);
  }

  return body;
}

export async function downloadCv(applicationId) {
  const token = getToken();
  const response = await fetch(`${API_BASE}/applications/${applicationId}/cv`, {
    headers: token ? { Authorization: `Bearer ${token}` } : {},
  });

  if (!response.ok) {
    const body = await response.json().catch(() => null);
    throw new Error(body?.error || 'Shkarkimi i CV-së dështoi.');
  }

  const blob = await response.blob();
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = `cv-${applicationId}`;
  document.body.append(link);
  link.click();
  link.remove();
  window.setTimeout(() => URL.revokeObjectURL(url), 1000);
}
