import { afterEach, test } from 'node:test';
import assert from 'node:assert/strict';
import { ApiError, getAppContext, requestApi } from '../src/lib/api.ts';
import { formatDate, formatTime } from '../src/lib/dateTime.ts';
import { dateRange, initialPaymentTab, parseSaleDate } from '../src/features/payments/types.ts';

const originalFetch = globalThis.fetch;
afterEach(() => { globalThis.fetch = originalFetch; });

const tenantContext = {
  settings: { dateFormat: 'DD/MM/YYYY', timeFormat: 'h:mm A', colorTheme: 'ocean', colorMode: 'light', whatsappEnabled: false },
  tenant: { id: 1, name: 'Example Fitness', domain: 'example', logo_url: null },
};

function mockJson(payload: unknown, status = 200) {
  globalThis.fetch = async () => new Response(JSON.stringify(payload), { status, headers: { 'Content-Type': 'application/json' } });
}

test('public context is not treated as an authenticated staff session', async () => {
  mockJson(tenantContext);
  const context = await getAppContext();
  assert.equal(context?.tenant.name, 'Example Fitness');
  assert.equal(context?.user, null);
  assert.equal(context?.permissions, null);
});

test('authenticated context retains the user and permission flags', async () => {
  mockJson({ ...tenantContext, user: { id: 5, name: 'Sam', email: 'sam@example.test' }, permissions: { members: true, paymentsManage: false } });
  const context = await getAppContext();
  assert.equal(context?.user?.name, 'Sam');
  assert.equal(context?.permissions?.members, true);
  assert.equal(context?.permissions?.paymentsManage, false);
});

test('API helper preserves Laravel field validation errors', async () => {
  mockJson({ message: 'Invalid input.', errors: { name: ['Name is required.'] } }, 422);
  await assert.rejects(() => requestApi('/api/members'), (error: unknown) => {
    assert.ok(error instanceof ApiError);
    assert.equal(error.status, 422);
    assert.deepEqual(error.errors?.name, ['Name is required.']);
    return true;
  });
});

test('tenant date and time formatting honors the configured format', () => {
  const value = new Date(2026, 4, 28, 14, 30);
  assert.equal(formatDate(value, 'DD/MM/YYYY'), '28/05/2026');
  assert.equal(formatDate(value, 'MMM D, YYYY'), 'May 28, 2026');
  assert.equal(formatTime(value, 'HH:mm'), '14:30');
  assert.equal(formatTime(value, 'h:mm A'), '2:30 PM');
  assert.equal(formatDate('not a date', 'YYYY-MM-DD'), '—');
});

test('payment date ranges retain custom boundaries and validate sale dates', () => {
  assert.deepEqual(dateRange('custom', '2026-01-03', '2026-02-01'), ['2026-01-03', '2026-02-01']);
  assert.equal(parseSaleDate('29 Feb 2024, 14:30')?.getDate(), 29);
  assert.equal(parseSaleDate('29 Feb 2026, 14:30'), null);
  assert.equal(parseSaleDate('invalid'), null);
});

test('default payment tab follows available permissions', () => {
  assert.equal(initialPaymentTab(true, true), 'membership');
  assert.equal(initialPaymentTab(true, false), 'membership');
  assert.equal(initialPaymentTab(false, true), 'sales');
  assert.equal(initialPaymentTab(false, false), 'membership');
});
