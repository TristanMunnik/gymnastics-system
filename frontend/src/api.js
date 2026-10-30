const apiUrl = import.meta.env.DEV ? '/api.php' : '../api.php';

// Keep API calls in one place so credentials, CSRF headers, and error handling stay consistent.
async function request(action, { csrfToken, headers, redirectOnUnauthorized = true, ...options } = {}, params = {}) {
  const query = new URLSearchParams({ action, ...params });
  const response = await fetch(`${apiUrl}?${query}`, {
    credentials: 'same-origin',
    ...options,
    headers: {
      Accept: 'application/json',
      ...(options.body ? { 'Content-Type': 'application/json' } : {}),
      ...(csrfToken ? { 'X-CSRF-Token': csrfToken } : {}),
      ...headers,
    },
  });

  const result = await response.json();

  if (response.status === 401) {
    if (redirectOnUnauthorized) {
      window.location.assign(import.meta.env.DEV ? '/' : './');
      throw new Error('Your session has expired. Sign in again.');
    }
    throw new Error(result.error || 'Invalid email or password.');
  }

  if (!response.ok) {
    throw new Error(result.error || 'The request could not be completed.');
  }

  return result;
}

export function getSession() {
  return request('session');
}

export function login(credentials, csrfToken) {
  return request('login', {
    method: 'POST',
    body: JSON.stringify(credentials),
    csrfToken,
    redirectOnUnauthorized: false,
  });
}

export function logout(csrfToken) {
  return request('logout', {
    method: 'POST',
    csrfToken,
  });
}

export function getGymnastProfile() {
  return request('profile');
}

export function getGymnasts() {
  return request('gymnasts');
}

export function createGymnast(data, csrfToken) {
  return request('gymnasts', {
    method: 'POST',
    body: JSON.stringify(data),
    csrfToken,
  });
}

export function updateGymnast(id, data, csrfToken) {
  return request('gymnasts', {
    method: 'PUT',
    body: JSON.stringify(data),
    csrfToken,
  }, { id });
}

export function deleteGymnast(id, csrfToken) {
  return request('gymnasts', {
    method: 'DELETE',
    csrfToken,
  }, { id });
}
