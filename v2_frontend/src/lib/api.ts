const jsonHeaders = { Accept: 'application/json' };
let csrfToken = '';

export class ApiError extends Error {
  constructor(
    message: string,
    readonly status?: number,
  ) {
    super(message);
    this.name = 'ApiError';
  }
}

async function responseError(response: Response): Promise<ApiError> {
  let payload: { message?: string; errors?: Record<string, string[]> } = {};

  try {
    payload = await response.json();
  } catch {
    // Keep a useful status-based message for non-JSON proxy/server responses.
  }

  const validationMessage = payload.errors
    ? Object.values(payload.errors).flat().find(Boolean)
    : undefined;
  const message = validationMessage || payload.message || `Request failed (${response.status}).`;

  return new ApiError(message, response.status);
}

export async function warmCsrfToken(authenticated = false): Promise<void> {
  const response = await fetch(authenticated ? '/__tenant-root' : '/login', {
    method: 'GET',
    credentials: 'include',
    headers: { Accept: 'text/html' },
  });

  if (!response.ok) {
    throw await responseError(response);
  }

  const html = await response.text();
  const csrf = html.match(/<meta[^>]+name=["']csrf-token["'][^>]+content=["']([^"']+)["']/i)?.[1];

  if (!csrf) {
    throw new ApiError('A security token could not be initialized. Check the tenant connection and try again.');
  }

  csrfToken = csrf;
}

async function postJson(path: string, body?: Record<string, string>): Promise<void> {
  if (!csrfToken) {
    throw new ApiError('Your security token is missing. Refresh the page and try again.');
  }

  const response = await fetch(path, {
    method: 'POST',
    credentials: 'include',
    headers: {
      ...jsonHeaders,
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
    },
    body: body ? JSON.stringify(body) : undefined,
  });

  if (!response.ok) {
    throw await responseError(response);
  }
}

export async function loginStaff(login: string, password: string): Promise<void> {
  await postJson('/api/auth/login', { login, password });
}

export async function logoutStaff(): Promise<void> {
  await postJson('/api/auth/logout');
}

export async function hasStaffSession(): Promise<boolean> {
  const response = await fetch('/api/app/context', {
    method: 'GET',
    credentials: 'include',
    headers: jsonHeaders,
  });

  if (response.status === 401) {
    return false;
  }

  if (!response.ok) {
    throw await responseError(response);
  }

  return true;
}