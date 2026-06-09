# Feature and Endpoint Inventory

Generated from the application routes and source tree on 2026-06-09, before
database-per-tenant work.

## Baseline

- 268 registered non-vendor route objects: 223 API and 45 non-API.
- 29 API controllers, 48 models, 34 services, 71 SPA pages, and 111 migrations.
- Backend baseline: 204 tests and 1,090 assertions pass after adding tenant,
  API, job, scheduled-command, financial, and media boundary tests.
- Frontend baseline: 3 workout-focused suites and 42 tests pass. The full
  frontend suite was failing before this inventory and has been repaired.
- PHP coverage is measured locally with PCOV: 68.80% lines, 54.66% methods,
  and 21.74% classes. PCOV does not measure branch coverage.
- Frontend V8 coverage: 63.97% lines, 51.46% functions, and 38.31% branches.
- `composer lint` currently reports 98 pre-existing repository-wide PHP style
  issues. `npm run lint` exits successfully with 77 warnings.

The route files under `routes/api/` and `routes/web.php` remain the canonical
source. Re-run `php artisan route:list --except-vendor` after route changes.

## Features

| Feature | Main capabilities | Dedicated backend feature tests |
|---|---|---|
| Tenant resolution | Subdomain/bypass lookup and request binding | Yes |
| Authentication | Session login, logout, refresh, social login, registration | Partial |
| App context/profile | Current user, permissions, tenant, profile | Yes |
| Dashboard | Overview and statistics | Yes |
| Users and roles | Staff CRUD, roles, permissions | Yes |
| Members | Member CRUD, status, verification, avatar, attendance, documents | Partial |
| Wallet and vouchers | Top-ups, transactions, voucher redemption | Yes |
| Payments and plans | Membership payments and payment plans | Yes |
| Inventory | Products, variations, stock, display, audit | Yes |
| Sales | POS sales, wallet use, payment completion | Yes |
| Accounts and expenses | Accounts, transfers, expenses, documents, transactions | Yes |
| Workouts | Exercises, programs, days, extras, assignments | Yes |
| Notifications | Bulk SMS/email/in-app notifications | Yes |
| Events | Events, registrations, payments, attendance | Yes |
| Public member portal | OTP, wallet, events, notifications, activity | Yes |
| Forms | Templates, submissions, PDFs | Yes |
| Reports | Overview and daily summary reports | Yes |
| Reconciliation | Configuration, open/close, comparison | Yes |
| Settings | General, tenant configuration, legacy tools, biometric device | Yes |
| Biometric integration | Device sync, access events, webhook | Yes |
| Member activity | Activity log and export | Yes |
| Legacy Blade pages | Inventory, sales, reports, member pages | Minimal |
| Queued/scheduled work | Notifications, reports, forms, biometric import | Tenant-ID boundaries covered; physical database routing still pending |

## Coverage Gate Progress

This test-first phase added 58 backend tests and increased the assertion count
from 655 to 1,090. New coverage includes tenant route resolution, cross-tenant
route model binding, wallet, vouchers, events, notifications, reconciliation,
public member portal, forms, member activity, settings, biometric
administration, member documents, expenses, daily summary reports, queued jobs,
legacy sync commands, delivery services, and scheduled notification commands.

The new tests exposed and now guard five production defects:

1. Reconciliation duplicate-day detection compared datetimes instead of dates.
2. Fresh databases rejected notification `processing` and `failed` statuses.
3. Form validation stripped stable field IDs, regenerating them on edits.
4. Legacy attendance sync accepted an end date before its start date.
5. Bulk notification jobs could deliver to a cross-tenant recipient referenced
   by a corrupted or stale recipient row.
6. Legacy member sync silently skipped wrapped API payloads such as
   `{data: {...}}`.
7. Legacy member sync converted formatted prices such as `Rs. 4,500.00` to
   zero.

All changed PHP files pass Pint. The complete backend and frontend suites pass.
The remaining confidence gaps are untested device-driver branches, broader
frontend coverage, and real MySQL multi-database integration tests.

## API Endpoints

### Health, Auth, Context, Profile, Dashboard

```text
GET    /api/health
POST   /api/auth/login
POST   /api/auth/logout
POST   /api/auth/refresh
GET    /api/app/context
GET    /api/profile
GET    /api/dashboard/overview
GET    /api/dashboard/stats
```

### Users and Roles

```text
GET    /api/users/meta
GET    /api/users
GET    /api/users/{user}
POST   /api/users
PUT    /api/users/{user}
DELETE /api/users/{user}
GET    /api/roles
POST   /api/roles
GET    /api/roles/{role}
PUT    /api/roles/{role}
PATCH  /api/roles/{role}/permissions
```

### Members, Documents, Wallet, and Biometric Member Actions

```text
GET    /api/members/meta
GET    /api/members
GET    /api/members/export/google-contacts
GET    /api/members/{member}
POST   /api/members
POST   /api/members/temp
PUT    /api/members/{member}
PATCH  /api/members/{member}/toggle-status
PATCH  /api/members/{member}/toggle-verification
DELETE /api/members/{member}
POST   /api/members/{member}/avatar
PUT    /api/members/{member}/avatar
DELETE /api/members/{member}/avatar
GET    /api/members/form/payment-plans
GET    /api/members/{member}/documents
GET    /api/members/{member}/documents/{document}/url
POST   /api/members/{member}/documents
DELETE /api/members/{member}/documents/{document}
GET    /api/members/{member}/payments
GET    /api/members/{member}/sales
GET    /api/members/{member}/workouts
GET    /api/members/{member}/attendance
GET    /api/wallet/meta
GET    /api/wallet-topups/{topup}
POST   /api/members/{member}/wallet/topup
GET    /api/members/{member}/wallet/topup-history
GET    /api/members/{member}/wallet/transactions
POST   /api/members/{member}/biometric-assign-id
POST   /api/members/{member}/biometric-sync
POST   /api/members/{member}/biometric-setup-fingerprint
GET    /api/members/{member}/biometric-logs
GET    /api/members/{member}/biometric-device-info
GET    /api/members/{member}/biometric-face-image
POST   /api/members/{member}/biometric-upload-face-photo
```

### Settings and Biometric Administration

```text
GET    /api/settings/general
PUT    /api/settings/general
POST   /api/settings/general/logo
DELETE /api/settings/general/logo
POST   /api/settings/legacy-tools/run
GET    /api/settings/legacy-tools/logs
GET    /api/settings/configuration
GET    /api/settings/configuration/format-options
PUT    /api/settings/configuration
POST   /api/settings/biometric/test-connection
POST   /api/settings/biometric/sync-all
POST   /api/settings/biometric/unlock
POST   /api/settings/biometric/keep-unlock
POST   /api/settings/biometric/close
POST   /api/settings/biometric/keep-close
GET    /api/settings/biometric/door-status
GET    /api/settings/biometric/recent-logs
GET    /api/settings/biometric/access-events
POST   /api/settings/biometric/access-events/sync
POST   /api/settings/biometric/webhook/generate-token
POST   /api/settings/biometric/webhook/configure
GET    /api/settings/biometric/webhook/status
POST   /api/biometric/events/{tenantDomain}
```

### Reports

```text
GET    /api/reports/overview
GET    /api/reports/daily-summary
POST   /api/reports/daily-summary/generate
GET    /api/reports/daily-summary/history
GET    /api/reports/daily-summary/reports/{report}
GET    /api/reports/daily-summary/reports/{report}/pdf
```

### Inventory

```text
GET    /api/inventory/meta
GET    /api/inventory/products
GET    /api/inventory/products/{product}
POST   /api/inventory/products
PUT    /api/inventory/products/{product}
DELETE /api/inventory/products/{product}
GET    /api/inventory/variations
POST   /api/inventory/variations
PUT    /api/inventory/variations/{variation}
DELETE /api/inventory/variations/{variation}
GET    /api/inventory/stock
GET    /api/inventory/stock/{stock}
POST   /api/inventory/stock
PUT    /api/inventory/stock/{stock}
DELETE /api/inventory/stock/{stock}
GET    /api/inventory/display
POST   /api/inventory/stock/{stock}/release
GET    /api/inventory/audit-logs
```

### Accounts, Transfers, and Expenses

```text
GET    /api/accounts/meta
GET    /api/accounts
POST   /api/accounts
GET    /api/accounts/transactions
GET    /api/accounts/transfers
POST   /api/accounts/transfers
GET    /api/accounts/transfers/{transfer}
PUT    /api/accounts/transfers/{transfer}
DELETE /api/accounts/transfers/{transfer}
GET    /api/accounts/expenses
POST   /api/accounts/expenses
GET    /api/accounts/expenses/{expense}
PUT    /api/accounts/expenses/{expense}
DELETE /api/accounts/expenses/{expense}
GET    /api/accounts/expenses/{expense}/documents/{document}/url
DELETE /api/accounts/expenses/{expense}/documents/{document}
GET    /api/accounts/{account}
PUT    /api/accounts/{account}
DELETE /api/accounts/{account}
```

### Sales

```text
GET    /api/sales/meta
GET    /api/sales/member-wallet/{member}
GET    /api/sales
POST   /api/sales
GET    /api/sales/{sale}
POST   /api/sales/{sale}/mark-as-paid
PUT    /api/sales/{sale}
DELETE /api/sales/{sale}
```

### Workouts

```text
GET    /api/exercises
POST   /api/exercises
GET    /api/exercises/{exercise}
PUT    /api/exercises/{exercise}
DELETE /api/exercises/{exercise}
GET    /api/workout-programs
POST   /api/workout-programs
GET    /api/workout-programs/{program}
PUT    /api/workout-programs/{program}
DELETE /api/workout-programs/{program}
GET    /api/workout-programs/{program}/customer-view
POST   /api/workout-programs/{program}/days
PUT    /api/workout-program-days/{day}
DELETE /api/workout-program-days/{day}
POST   /api/workout-program-days/{day}/exercises
PUT    /api/workout-day-exercises/{dayExercise}
DELETE /api/workout-day-exercises/{dayExercise}
POST   /api/workout-programs/{program}/extras
PUT    /api/workout-program-extras/{extra}
DELETE /api/workout-program-extras/{extra}
GET    /api/workout-program-assignments
GET    /api/workout-program-assignment-members
POST   /api/workout-program-assignments
PUT    /api/workout-program-assignments/{assignment}
DELETE /api/workout-program-assignments/{assignment}
```

### Payments and Plans

```text
GET    /api/payments/meta
GET    /api/payments/member/{member}/payment-info
GET    /api/payments
POST   /api/payments
GET    /api/payments/{payment}
PUT    /api/payments/{payment}
DELETE /api/payments/{payment}
GET    /api/payment-plans
POST   /api/payment-plans
PUT    /api/payment-plans/{paymentPlan}
DELETE /api/payment-plans/{paymentPlan}
```

### Notifications

```text
GET    /api/notifications
GET    /api/notifications/members
GET    /api/notifications/{bulkNotification}
POST   /api/notifications
PUT    /api/notifications/{bulkNotification}
DELETE /api/notifications/{bulkNotification}
POST   /api/notifications/{bulkNotification}/send
```

### Events

```text
GET    /api/events
POST   /api/events
GET    /api/events/{event}
PUT    /api/events/{event}
DELETE /api/events/{event}
GET    /api/events/{event}/registrations
POST   /api/events/{event}/registrations
PUT    /api/events/{event}/registrations/{registration}
DELETE /api/events/{event}/registrations/{registration}
POST   /api/events/{event}/registrations/{registration}/mark-paid
POST   /api/events/{event}/registrations/{registration}/mark-attendance
```

### Public Member Portal and Activity

```text
POST   /api/public/request-otp
POST   /api/public/verify-otp
POST   /api/public/activity
GET    /api/public/event/{slug}
POST   /api/public/event/{slug}/register
GET    /api/public/upcoming-events
GET    /api/public/member-profile
GET    /api/public/wallet/transactions
GET    /api/public/notifications
GET    /api/public/event/{slug}/my-registration
PUT    /api/public/event/{slug}/my-registration
GET    /api/member-activity
GET    /api/member-activity/export
```

### Reconciliation

```text
GET    /api/reconciliation/config
POST   /api/reconciliation/config
GET    /api/reconciliation
GET    /api/reconciliation/sessions/{session}
GET    /api/reconciliation/today
GET    /api/reconciliation/form-config
POST   /api/reconciliation/open
POST   /api/reconciliation/sessions/{session}/save-close
GET    /api/reconciliation/sessions/{session}/preview
POST   /api/reconciliation/sessions/{session}/close
```

### Vouchers

```text
GET    /api/vouchers
POST   /api/vouchers
GET    /api/vouchers/{voucher}
PUT    /api/vouchers/{voucher}
DELETE /api/vouchers/{voucher}
POST   /api/members/{member}/wallet/redeem-voucher
GET    /api/members/{member}/wallet/voucher-redemptions
```

### Forms

```text
GET    /api/forms/templates
GET    /api/forms/templates/active
POST   /api/forms/templates
GET    /api/forms/templates/{template}
PUT    /api/forms/templates/{template}
DELETE /api/forms/templates/{template}
GET    /api/forms/templates/{template}/submissions
GET    /api/forms/submissions/{submission}
GET    /api/forms/submissions/{submission}/pdf-url
DELETE /api/forms/submissions/{submission}
POST   /api/forms/templates/{template}/members/{member}/submit
GET    /api/members/{member}/form-submissions
```

## Non-API Endpoints

```text
GET    /
GET    /up
GET    /login
POST   /login
POST   /logout
GET    /register
POST   /register
GET    /auth/{provider}/redirect
GET    /auth/{provider}/callback
GET    /profile
GET    /profile/workout
GET    /profile/transactions
GET    /profile/profile
GET    /profile/event/{slug}
GET    /profile/notifications
GET    /profile/{username}
GET    /dashboard
GET    /workout-schedule
GET    /diet-plan
GET    /payments
GET    /attendance
GET    /settings
POST   /settings/landing-page
GET    /reports
GET    /inventory/products
GET    /inventory/products/create
POST   /inventory/products
GET    /inventory/products/{product}/edit
PUT|PATCH /inventory/products/{product}
DELETE /inventory/products/{product}
GET    /inventory/variations
GET    /inventory/variations/create
POST   /inventory/variations
GET    /inventory/variations/{variation}/edit
PUT|PATCH /inventory/variations/{variation}
DELETE /inventory/variations/{variation}
GET    /inventory/stock
GET    /inventory/stock/create
GET    /inventory/stock/{stock}/edit
POST   /inventory/stock
PUT    /inventory/stock/{stock}
DELETE /inventory/stock/{stock}
GET    /sales
GET    /sales/create
POST   /sales
DELETE /sales/{sale}
GET    /storage/{path}
PUT    /storage/{path}
```

## Coverage Gate Before Database Cutover

Database cutover must not begin until all of these are true:

1. Install PCOV or Xdebug in CI and record line and branch coverage.
2. Add behavioral tests for every "No" and "Partial" feature above.
3. Add two physical-database integration tests proving that a request, queue
   job, scheduled command, webhook, and migration cannot read/write another
   tenant database.
4. Test MySQL, not only in-memory SQLite, because 13 current migrations contain
   MySQL-specific behavior.
5. Make backend, frontend, lint, and production build green in CI.
6. Define acceptable coverage thresholds. A literal 100% line metric is useful
   as a target but does not prove zero defects or complete behavioral coverage.
