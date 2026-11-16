const jsonHeaders = { Accept: 'application/json' };
let csrfToken = '';

export type TenantDateFormat = 'D MMM YYYY' | 'DD/MM/YYYY' | 'MM/DD/YYYY' | 'YYYY-MM-DD' | 'MMM D, YYYY';
export type TenantTimeFormat = 'HH:mm' | 'h:mm A';
export type TenantColorTheme = 'crimson' | 'ocean' | 'forest' | 'violet' | 'sunset' | 'slate';
export type TenantColorMode = 'system' | 'light' | 'dark';

export type StaffAppContext = {
  settings: {
    dateFormat: TenantDateFormat;
    timeFormat: TenantTimeFormat;
    colorTheme: TenantColorTheme;
    colorMode: TenantColorMode;
    whatsappEnabled: boolean;
  };
  user: { id: number; name: string; email: string };
  tenant: {
    id: number;
    name: string;
    domain: string;
    address: string | null;
    email: string | null;
    phone: string | null;
    logo_url: string | null;
    member_portal_url: string | null;
  };
  permissions: Record<string, boolean>;
};

const dateFormats: TenantDateFormat[] = ['D MMM YYYY', 'DD/MM/YYYY', 'MM/DD/YYYY', 'YYYY-MM-DD', 'MMM D, YYYY'];
const timeFormats: TenantTimeFormat[] = ['HH:mm', 'h:mm A'];
const colorThemes: TenantColorTheme[] = ['crimson', 'ocean', 'forest', 'violet', 'sunset', 'slate'];
const colorModes: TenantColorMode[] = ['system', 'light', 'dark'];

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function isOneOf<T extends string>(value: unknown, values: readonly T[], fallback: T): T {
  return typeof value === 'string' && values.includes(value as T) ? value as T : fallback;
}

function nullableString(value: unknown): string | null {
  return typeof value === 'string' && value.length > 0 ? value : null;
}

function parseStaffAppContext(value: unknown): StaffAppContext {
  if (!isRecord(value) || !isRecord(value.user) || !isRecord(value.tenant) || !isRecord(value.settings) || !isRecord(value.permissions)) {
    throw new ApiError('The workspace profile response was incomplete. Refresh the page and try again.');
  }

  const { user, tenant, settings, permissions } = value;
  if (typeof user.id !== 'number' || typeof user.name !== 'string' || typeof user.email !== 'string'
    || typeof tenant.id !== 'number' || typeof tenant.name !== 'string' || typeof tenant.domain !== 'string') {
    throw new ApiError('The workspace profile response was invalid. Refresh the page and try again.');
  }

  return {
    settings: {
      dateFormat: isOneOf(settings.dateFormat, dateFormats, 'D MMM YYYY'),
      timeFormat: isOneOf(settings.timeFormat, timeFormats, 'HH:mm'),
      colorTheme: isOneOf(settings.colorTheme, colorThemes, 'crimson'),
      colorMode: isOneOf(settings.colorMode, colorModes, 'system'),
      whatsappEnabled: settings.whatsappEnabled === true,
    },
    user: { id: user.id, name: user.name, email: user.email },
    tenant: {
      id: tenant.id,
      name: tenant.name,
      domain: tenant.domain,
      address: nullableString(tenant.address),
      email: nullableString(tenant.email),
      phone: nullableString(tenant.phone),
      logo_url: nullableString(tenant.logo_url),
      member_portal_url: nullableString(tenant.member_portal_url),
    },
    permissions: Object.fromEntries(Object.entries(permissions).map(([key, enabled]) => [key, enabled === true])),
  };
}

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

export async function getStaffAppContext(): Promise<StaffAppContext | null> {
  const response = await fetch('/api/app/context', {
    method: 'GET',
    credentials: 'include',
    headers: jsonHeaders,
  });

  if (response.status === 401) {
    return null;
  }

  if (!response.ok) {
    throw await responseError(response);
  }

  return parseStaffAppContext(await response.json() as unknown);
}