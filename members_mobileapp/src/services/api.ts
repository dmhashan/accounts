import type { PublicProfilePayload } from '../types/profile';
import { normalizeBaseUrl } from '../utils/format';

export class ApiError extends Error {
  constructor(
    message: string,
    public readonly status?: number
  ) {
    super(message);
  }
}

type JsonRecord = Record<string, unknown>;

function endpoint(baseUrl: string, path: string) {
  const normalized = normalizeBaseUrl(baseUrl);
  if (!normalized) throw new ApiError('Enter your gym member portal base URL.');
  return `${normalized}${path}`;
}

async function readJson(response: Response): Promise<JsonRecord> {
  try {
    return (await response.json()) as JsonRecord;
  } catch {
    return {};
  }
}

async function assertOk(response: Response) {
  const data = await readJson(response);
  if (!response.ok) {
    throw new ApiError(String(data.message || 'Something went wrong.'), response.status);
  }
  return data;
}

export async function requestOtp(baseUrl: string, phoneNumber: string) {
  if (!phoneNumber.trim()) throw new ApiError('Enter your registered mobile number.');

  const response = await fetch(endpoint(baseUrl, '/api/public/request-otp'), {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: JSON.stringify({ phone_number: phoneNumber.trim() })
  });

  await assertOk(response);
}

export async function verifyOtp(baseUrl: string, phoneNumber: string, otp: string) {
  if (otp.trim().length !== 6) throw new ApiError('Enter the 6-digit OTP code.');

  const response = await fetch(endpoint(baseUrl, '/api/public/verify-otp'), {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: JSON.stringify({
      phone_number: phoneNumber.trim(),
      otp: otp.trim()
    })
  });

  const data = await assertOk(response);
  return { token: String(data.token || '') };
}

export async function fetchProfile(baseUrl: string, token: string): Promise<PublicProfilePayload> {
  const response = await fetch(endpoint(baseUrl, '/api/public/member-profile'), {
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-PP-Token': token
    }
  });

  const data = await assertOk(response);
  return {
    meta: (data.meta ?? {}) as PublicProfilePayload['meta'],
    workouts: (data.workouts ?? []) as PublicProfilePayload['workouts'],
    sales: (data.sales ?? []) as PublicProfilePayload['sales'],
    wallet_transactions: (data.wallet_transactions ?? []) as PublicProfilePayload['wallet_transactions'],
    wallet_tx_meta: data.wallet_tx_meta as PublicProfilePayload['wallet_tx_meta']
  };
}
