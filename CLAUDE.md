# Project Architecture — Claude AI Reference

## Overview

This is a **multi-tenant SaaS management platform** built for fitness-related businesses (gyms, fitness centres). It provides member management, POS sales, financial accounts, inventory, workout programming, event management, bulk SMS notifications, and daily cash reconciliation.

**Core stack:**
- Backend: Laravel 12 (PHP 8.2+) — session-based auth, REST API
- Frontend: Vue 3 SPA (hash routing) + Tailwind CSS v4
- Build: Vite 7 with laravel-vite-plugin
- Testing: PHPUnit (backend) + Vitest (frontend)
- SMS provider: SMSlenz (Sri Lanka)

---

## Multi-Tenancy

Every tenant maps to a subdomain (`{domain}.{APP_DOMAIN}`) or is identified via `APP_MULTITENANCY_BYPASS_DOMAIN` when multitenancy is disabled (single-tenant dev mode).

**Flow:**
1. `IdentifyTenant` middleware (`app/Http/Middleware/IdentifyTenant.php`) runs on every request under the `web` + `IdentifyTenant` group.
2. It resolves the `Tenant` model from the subdomain and binds it into the service container as `app('tenant')`.
3. All data is scoped by `tenant_id`. Every model that is tenant-specific has a `tenant_id` column and a `belongsTo(Tenant::class)` relationship.

**Bypass mode** (`.env`):
```
APP_MULTITENANCY_ENABLED=false
APP_MULTITENANCY_BYPASS_DOMAIN=gymname
```

---

## Directory Structure

```
app/
  Http/
    Controllers/Api/   — All API controllers (one per domain module)
    Controllers/Auth/  — Traditional web auth (login, register, social)
    Middleware/
      IdentifyTenant.php   — Resolves tenant from subdomain
      CheckPermission.php  — Gate: permission:slug middleware
      ResolvePpToken.php   — Public profile token auth (pp.token)
  Models/              — Eloquent models (one per domain entity)
  Services/            — Business logic layer (thin controllers, fat services)
  Rules/
    UniqueTenantEmail.php  — Scoped email uniqueness validation

resources/js/
  spa/                 — Vue SPA (main tenant app)
    pages/             — Route-level page components (lazy loaded)
    components/        — Shared UI components
    composables/
      useApiClient.js  — Axios wrapper with CSRF + auto token refresh
      useAppContext.js  — Injects app context (user, permissions, tenant)
      useNavigation.js — Navigation helpers
    router.js          — Vue Router (hash history)
    routeLoader.js     — Permission-gated route navigation
  public-profile.js    — Standalone public member portal (separate entry)

routes/
  api.php              — Loads all API route files under web+IdentifyTenant
  web.php              — SPA shell + landing page + social auth callbacks
  api/                 — Individual route files per module

database/migrations/   — Chronological migrations
```

---

## Authentication

**Staff/admin auth** uses Laravel session (`web` guard). Login accepts email or username scoped to the tenant.

Key files:
- `app/Services/AuthSessionService.php` — login attempt, session regeneration, logout
- `app/Http/Controllers/Api/AuthApiController.php` — POST `/api/auth/login`, `/api/auth/logout`, `/api/auth/refresh`
- Social auth via Laravel Socialite (`/auth/google/redirect`, `/auth/apple/redirect`) in `app/Http/Controllers/Auth/SocialAuthController.php`

The SPA calls `/api/auth/refresh` automatically (via `useApiClient.js`) on 401 responses before triggering logout.

**Public Profile (member self-service)** uses an OTP flow:
1. `POST /api/public/request-otp` — sends SMS OTP to member's phone
2. `POST /api/public/verify-otp` — verifies OTP, issues a short-lived token stored in Laravel Cache (`pp_token:{token}`)
3. Subsequent requests use `X-PP-Token` header, resolved by `ResolvePpToken` middleware (`pp.token`)

---

## Permission System

Roles are seeded globally (not per-tenant). Users are assigned one role. Permissions are slugs attached to roles via a `role_permission` pivot.

**Models:**
- `Role` — `name`, `slug`, `is_editable`; has many `User`; belongsToMany `Permission`
- `Permission` — `name`, `slug`, `feature`; belongsToMany `Role`
- `User::hasPermission(slug)` — delegates to `$this->role->hasPermission(slug)`

**Middleware alias:** `permission:slug` (or `permission:a,b` for OR logic, comma-separated in one string)

**Permission slugs (full list):**

| Slug | Feature area |
|---|---|
| `dashboard.view` | Dashboard |
| `users.view` / `users.create` / `users.edit` / `users.delete` | Users & Members |
| `roles.view` / `roles.permissions` | Roles |
| `settings.manage` | Settings |
| `reports.view` | Reports |
| `inventory.manage` | Products & Variations |
| `inventory.stock` | Stock entries |
| `inventory.display` | Display (release to shelf) |
| `sales.process` / `sales.create` / `sales.edit` / `sales.delete` | POS Sales |
| `accounts.manage` | Company Accounts, Transfers, Expenses |
| `payments.manage` | Member Payments |
| `workouts.manage` | Exercises & Programs |
| `notifications.send` | Bulk SMS Notifications |
| `events.manage` | Events & Registrations |
| `activity.view` | Member Activity Logs |
| `reconciliation.perform` | Daily cash open/close |
| `reconciliation.manage` | Reconciliation config & history |
| `member.profile.view` | Own profile (member role) |
| `member.workout.view` | Own workout (member role) |
| `member.payments.view` | Own payment history |
| `member.diet.view` | Diet page |
| `member.attendance.view` | Attendance page |

**App context** is served by `AppContextService` at `GET /api/context`, providing user info, tenant info, and a flat `permissions` map for the SPA.

---

## Domain Models

### Tenant
`tenants` — `name`, `domain`, `use_custom_landing_page`

### User (staff account)
`users` — `tenant_id`, `role_id`, `name`, `email`, `username`, `password`, `social_provider`, `social_provider_id`, `avatar`

### Member (gym member / customer)
`members` — `tenant_id`, `user_id` (nullable, links to User for self-service portal), `member_id` (format: `MEM-YYYY-XXXX`), `name`, `gender`, `email`, `phone_number`, `nic`, `date_of_birth`, `age`, `address`, `member_role`, `admission_fee`, `payment_plan`, `price`, `current_balance`, `joined_date`, `is_active`, `is_verified`, `is_temp`

`is_temp` = walk-in / temporary member (incomplete profile).

### Product / ProductVariation / StockEntry
`products` — `tenant_id`, `name`  
`product_variations` — `product_id`, `name`  
`stock_entries` — `tenant_id`, `product_id`, `product_variation_id`, `quantity`, `display_quantity`, `manufacturing_date`, `expiry_date`, `purchasing_price`, `local_selling_price`, `foreign_selling_price`

### Sale / SaleItem
`sales` — `tenant_id`, `customer_name`, `customer_member_id`, `account_id` (FK company_accounts), `customer_type`, `payment_method`, `reference_number`, `total_amount`, `paid_amount`, `balance`, `is_paid`; soft deletes  
`sale_items` — `sale_id`, `product_id`, `product_variation_id`, `quantity`, `unit_price`, `total_price`

A sale can be partially paid. `mark-as-paid` endpoint finalises it.

### CompanyAccount / CompanyAccountTransaction / CompanyAccountTransfer / Expense
`company_accounts` — `tenant_id`, `name`, `opening_balance`, `description`  
`company_account_transactions` — `tenant_id`, `company_account_id`, `model_name` (sale/payment/expense/transfer), `reference_id`, `type` (credit/debit), `amount`, `transaction_date`, `reference_number`, `notes`  
`company_account_transfers` — `source_account_id`, `destination_account_id`, `amount`, `transfer_date`, `notes`  
`expenses` — `tenant_id`, `company_account_id`, `category`, `amount`, `expense_date`, `reference_number`, `notes`

Transactions are auto-created when a sale is paid, a member payment is recorded, or an expense/transfer is saved.

### MemberPayment
`member_payments` — `tenant_id`, `member_id` (nullable), `company_account_id`, `amount`, `payment_date`, `reference_number`, `notes`

Deducts from / adds to member wallet (`current_balance` on Member).

### WorkoutProgram
`workout_programs` — `tenant_id`, `title`, `description`, `duration_weeks`, `created_by`  
`workout_program_days` — `program_id`, `day_number`, `title`  
`workout_day_exercises` — `program_day_id`, `exercise_id`, `sets`, `reps`, `duration`, `rest_seconds`, `notes`, `order`  
`workout_program_extras` — `program_id`, `title`, `content`, `order`  
`workout_program_assignments` — `program_id`, `member_id`, `tenant_id`, `start_date`, `end_date`, `notes`  
`exercises` — `tenant_id`, `name`, `category`, `muscle_group`, `default_sets`, `default_reps`, `default_duration`, `default_rest_seconds`, `description`, `video_url`  
`exercise_variations` — `exercise_id`, `name`

### Event / EventRegistration / EventRegistrationGuest
`events` — `tenant_id`, `name`, `slug`, `start_datetime`, `end_datetime`, `venue`, `venue_url`, `agenda`, `registration_process`, `ticket_fee`, `additional_ticket_fee`, `is_active`  
`event_registrations` — `event_id`, `tenant_id`, `member_id` (nullable), `name`, `email`, `phone`, `notes`, `total_fee`, `is_paid`, `paid_at`, `company_account_id`, `is_attended`, `attended_at`  
`event_registration_guests` — `registration_id`, `name`, `notes`

Events are publicly accessible via slug. Members can register via the public profile portal (no staff login required).

### BulkNotification / BulkNotificationRecipient
`bulk_notifications` — `tenant_id`, `created_by`, `name`, `message`, `status` (draft/sent), `sent_at`  
`bulk_notification_recipients` — `bulk_notification_id`, `member_id`, `phone_number`

SMS is sent via `SmsService` (SMSlenz API). Sending is fire-and-forget; errors are logged but never bubble up.

### ReconciliationSession / ReconciliationEntry / ReconciliationConfig
Daily cash reconciliation workflow:
1. Staff opens a session (`POST /api/reconciliation/open`) for today
2. Records actual cash collected per account
3. System compares against expected (transactions from that day)
4. Staff closes session with optional adjustment reason

`reconciliation_sessions` — `tenant_id`, `date`, `status` (open/closed), `opened_by`, `closed_by`, `closed_at`, `adjustment_reason`, `notes`  
`reconciliation_entries` — `session_id`, `tenant_id`, `type` (account/stock), `reference_id`, `expected_amount`, `actual_amount`, `variance`, `notes`  
`reconciliation_configs` — `tenant_id`, `role_id`, `type`, `reference_id`, `is_active`

### AuditLog
`audit_logs` — `tenant_id`, `user_id`, `action`, `auditable_type`, `auditable_id`, `before_data` (JSON), `after_data` (JSON), `created_at`

`AuditService` is injected into services to log create/update/delete operations.

### MemberActivityLog
`member_activity_logs` — `tenant_id`, `member_id`, `session_id`, `event_type`, `ip_address`, `user_agent`, `device_type`, `browser`, `os`, `screen_width`, `screen_height`, `metadata` (JSON)

Logged via public profile portal when members interact with their portal page.

---

## API Structure

All API routes live under `routes/api.php` and are loaded under `middleware(['web', IdentifyTenant::class])`. No token-based API auth — uses Laravel session (cookie-based).

**Base path:** `/api/`

| Route file | Prefix | Auth guard |
|---|---|---|
| `auth.php` | `/auth/*` | Public (login/logout/refresh) |
| `context.php` | `/context` | `auth` |
| `profile.php` | `/profile` | `auth` |
| `dashboard.php` | `/dashboard/*` | `auth` |
| `members.php` | `/members/*` | `auth` + `permission:users.view` |
| `users.php` | `/users/*` | `auth` + `permission:users.view` |
| `roles.php` | `/roles/*` | `auth` + `permission:roles.view` |
| `inventory.php` | `/inventory/*` | `auth` + sub-permissions |
| `accounts.php` | `/accounts/*` | `auth` + `permission:accounts.manage` |
| `sales.php` | `/sales/*` | `auth` + `permission:sales.*` |
| `payments.php` | `/payments/*` | `auth` + `permission:payments.manage` |
| `workouts.php` | `/exercises/*`, `/workout-programs/*` | `auth` + `permission:workouts.manage` |
| `events.php` | `/events/*` | `auth` + `permission:events.manage` |
| `notifications.php` | `/notifications/*` | `auth` + `permission:notifications.send` |
| `reconciliation.php` | `/reconciliation/*` | `auth` + `permission:reconciliation.*` |
| `reports.php` | `/reports/*` | `auth` + `permission:reports.view` |
| `activity.php` | `/member-activity/*` | `auth` + `permission:activity.view` |
| `public-profile.php` | `/public/*` | Public OTP / `pp.token` |

---

## Frontend SPA

**Entry:** `resources/js/spa/main.js`  
**Router:** Hash history (`/#/path`). All routes defined in `resources/js/spa/router.js`.  
**Auth gate:** `routeLoader.js` checks app context permissions before navigation.

**Key composables:**
- `useAppContext()` — inject app context (user, tenant, permissions) provided by `App.vue`
- `useApiClient.js` — wraps Axios: attaches CSRF token (`X-CSRF-TOKEN`), handles 401 → auto refresh → retry → logout redirect

**Permissions in SPA** are a flat object from `/api/context`:
```js
ctx.permissions.members      // boolean
ctx.permissions.accounts     // boolean
ctx.permissions.salesCreate  // boolean
// ... etc
```

**Page naming convention:** `{Domain}Page.vue` (list), `{Domain}FormPage.vue` (create/edit), `{Domain}ViewPage.vue` (detail/read-only)

**Public Profile** is a separate bundle (`resources/js/public-profile.js`) — a standalone Vue app for the member self-service portal accessible at `/{tenant}/profile` (or `/profile` in bypass mode).

---

## Service Layer Pattern

Controllers are thin. All business logic lives in `app/Services/`. Services are injected via constructor DI.

**Standard service methods:** `index()`, `show()`, `store()`, `update()`, `destroy()`, `meta()` (dropdown/select data)

Services return plain arrays (not models) to controllers, which then return `response()->json(...)`.

**Audit logging** is done inside services using `AuditService::log()` for sensitive mutations (member create/update/delete, payment changes, etc.).

---

## Development Commands

```bash
# Full dev server (Laravel + queue + Pail logs + Vite)
composer dev

# Backend tests
composer test

# Frontend tests
npm test

# Build assets
npm run build

# Fresh setup
composer setup
```

---

## Environment Variables (Key)

```
APP_MULTITENANCY_ENABLED=true/false
APP_MULTITENANCY_BYPASS_DOMAIN=gymname     # single-tenant override
APP_DOMAIN=yourdomain.com                  # base domain for subdomain resolution
APP_URL=https://yourdomain.com

SMSLENZ_USER_ID=
SMSLENZ_API_KEY=
SMSLENZ_SENDER_ID=SMSlenzDEMO
```

---

## Conventions & Rules

1. **All tenant-scoped data** is filtered by `tenant_id` — never trust route params alone, always verify `tenant_id` matches `app('tenant')->id`.
2. **Permissions** are enforced both in route middleware (`permission:slug`) and in `AppContextService` for the SPA context.
3. **`is_temp` members** are walk-ins with incomplete data — treated separately from full members in lists and reports.
4. **Transactions** are created automatically whenever a financial action occurs (sale paid, member payment, expense, transfer). Do not create standalone transactions manually — always go through the relevant service.
5. **Soft deletes** on `Sales` only — other models use hard delete.
6. **SMS sending** via `SmsService` must never throw — failures are logged silently.
7. **Frontend responsive** — all pages must support mobile, tablet, and large display (Tailwind responsive classes). Navigation parity across breakpoints is required per project rules.
8. **Audit logs** — use `AuditService::log()` for any destructive or sensitive mutations.
9. **Public Profile token** — stored in Laravel Cache, not DB. Key: `pp_token:{token}` → `['tenant_id', 'member_id']`.
10. **Member ID format** — auto-generated as `MEM-{YEAR}-{0001}` by `Member::generateMemberId()`.

---

## Biometric Device Integration

Per-tenant biometric access control system. Supports HikVision devices via ISAPI (HTTP Digest Auth). Configuration stored in `tenant_configurations` under `biometric.*` keys. All device communication is backend-only.

### Architecture Overview

```
BiometricSettingsPage.vue   →  BiometricApiController  →  BiometricSyncService  →  HikvisionService
MemberBiometricTab.vue      →  BiometricApiController  →  BiometricSyncService  →  HikvisionService
MemberService (hooks)       →  BiometricSyncService    →  HikvisionService      →  Device ISAPI
Device (real-time push)     →  BiometricWebhookController →  BiometricSyncService::handleIncomingEvent()
ImportBiometricAccessEventsJob (queued) → BiometricSyncService::importDeviceEvents()
```

### Database

**`biometric_sync_logs`** — audit trail for all device sync events:
- `tenant_id` (FK), `member_id` (FK nullable)
- `direction` enum(`up`, `down`)
- `action` enum(`create`, `update`, `delete`, `attendance`, `manual_sync`, `test`)
- `status` enum(`success`, `failed`)
- `device_maker`, `device_model` (strings)
- `payload` JSON nullable — request sent to device
- `response` JSON nullable — raw device response
- `error_message` text nullable
- `synced_at`, `created_at` timestamps

**`members` table additions:**
- `biometric_last_synced_at` — nullable timestamp, updated silently (`$member->timestamps = false`) to avoid polluting `updated_at`

**`biometric_access_events`** — real-time authentication event log (one row per scan):
- `tenant_id` (FK), `member_id` (FK nullable — null for unrecognised/stranger attempts)
- `biometric_member_id`, `employee_no`, `person_name` (device-reported)
- `auth_method` — face | card | fingerprint | password | unknown
- `result` enum(`success`, `failed`) — `success` also marks `MemberAttendance`; `failed` = "attempted"
- `minor_code` int — raw device minor event code
- `picture_path` — stored snapshot captured by the device at authentication time (media disk)
- `event_time`, `raw` JSON, `created_at`

**Model:** `app/Models/BiometricAccessEvent.php` — `$timestamps = false`, casts `raw` as array, `event_time`/`created_at` as datetime.

**Model:** `app/Models/BiometricSyncLog.php` — `$timestamps = false`, casts `payload`/`response` as array, `synced_at`/`created_at` as datetime.

### Tenant Configuration Keys (`biometric.*`)

Stored in `tenant_configurations` via `TenantConfigurationService`. All keys start with `biometric.`:

| Key | Default | Purpose |
|---|---|---|
| `biometric.enabled` | `0` | Master on/off switch |
| `biometric.device_maker` | `''` | e.g. `hikvision` |
| `biometric.device_model` | `''` | e.g. `DS-K1T320MFWX-B` |
| `biometric.device_ip` | `''` | IP or hostname |
| `biometric.device_port` | `80` | TCP port |
| `biometric.device_username` | `admin` | Device admin username |
| `biometric.device_password` | `''` | Device admin password |
| `biometric.sync_members` | `0` | Auto push members to device on create/update/delete |
| `biometric.access_control` | `0` | Restrict device access by payment validity |
| `biometric.grace_period_days` | `0` | Extra days past payment end date to allow access |

These keys are read/written via the standard `GET/PUT /api/settings/configuration` endpoint. Biometric validation rules are in `ConfigurationApiController::update()`.

### Services

**`app/Services/HikvisionService.php`** — raw ISAPI client:
- Constructor: `(string $ip, int $port, string $username, string $password)`
- Uses `Http::withDigestAuth($user, $pass)->withoutVerifying()->timeout(15)`
- Methods: `testConnection()`, `addPerson(array $payload)`, `updatePerson(array $payload)`, `deletePerson(string $employeeNo)`, `getAccessEvents(string $startTime, string $endTime, int $offset, int $maxResults)` — paginated history import
- All methods return `['success' => bool, 'data' => array, 'message' => string]` — never throw
- Success detection: `statusCode === 1` in ISAPI response body (not HTTP status alone)
- ISAPI base: `http://{ip}:{port}`; key endpoints:
  - `POST /ISAPI/AccessControl/UserInfo/Record?format=json` — add person
  - `PUT /ISAPI/AccessControl/UserInfo/Modify?format=json` — update person
  - `PUT /ISAPI/AccessControl/UserInfo/Delete?format=json` — delete person
  - `POST /ISAPI/AccessControl/AcsEvent` — fetch access-control events (`AcsEventCond`, major=5)
  - `GET /ISAPI/System/deviceInfo?format=json` — connection test

**`app/Services/BiometricSyncService.php`** — orchestration layer:
- Constructor injects `TenantConfigurationService $config`
- `buildDriver(int $tenantId): ?HikvisionService` — reads config, instantiates `HikvisionService`; returns `null` if not configured
- `isEnabled()`, `isMemberSyncEnabled()`, `isAccessControlEnabled()`, `getGracePeriodDays()` — helpers reading `biometric.*` config
- `syncMember(Member $member, string $action)`: action = `create|update|delete|manual_sync`
  - Calls `addPerson()` on create; on `deviceUserAlreadyExist` subStatusCode falls back to `updatePerson()`
  - Calls `updatePerson()` on update; `deletePerson()` on delete
  - Payload built by `buildPersonPayload()` — sets `Valid` block if `access_control` is ON
  - Updates `biometric_last_synced_at` with `$member->timestamps = false`
  - Logs every attempt to `biometric_sync_logs`
- `syncAllMembers(Tenant $tenant)`: loops all non-temp active members, calls `syncMember(..., 'manual_sync')`, returns `['synced' => int, 'failed' => int, 'message' => string]`
- `testConnection(Tenant $tenant)`: calls `HikvisionService::testConnection()`, logs result, returns `['success' => bool, 'message' => string]`
- `handleIncomingEvent(Tenant $tenant, array $event)`: real-time webhook entry point — records a `BiometricAccessEvent` (with the captured picture) via `recordAccessEvent()` first, then writes a best-effort `webhook_event` sync log (wrapped in try/catch — the `webhook_event` action enum value only exists in the MySQL-only enum-extension migration, so it must never block core logic under SQLite)
- `importDeviceEvents(Tenant $tenant): array`: queued manual import — paginates `HikvisionService::getAccessEvents()` over a 1-year window (`maxResults=50`), maps each `AcsEvent.InfoList` item to an event array, resolves the member, and calls `recordAccessEvent()` counting `imported`/`skipped`/`errors`; writes a best-effort `attendance` sync log; returns `['imported','skipped','errors','message']`. Dispatched by `ImportBiometricAccessEventsJob`.
- `recordAccessEvent(Tenant, array $event, ?Member $member): bool`: classifies the minor code via `classifyAuthEvent()`; returns `false` for non-auth events or duplicates (dedup on `tenant_id`+`minor_code`+`event_time`+`employee_no` when `event_time` present); stores the snapshot via `storeEventPicture()`; creates a `BiometricAccessEvent` (`success`/`failed`); on `success` with a resolved member also `firstOrCreate`s a `MemberAttendance` for the day; returns `true` when a new event row was created.
- `classifyAuthEvent(int $minor)`: maps minor codes to `['method', 'result']` using `SUCCESS_AUTH_MINORS` (75 face, 38 card, 113 fingerprint) and `FAILED_AUTH_MINORS` (76/77 face, 39 card, 114 fingerprint, 22 password) — edit these constants to tune per firmware
- `writeLogSafely(array $data)`: wraps `writeLog()` in try/catch (Log::warning on failure) so sync-log writes never block core logic
- `buildPersonPayload(Member $member, int $tenantId)`: constructs HikVision person DTO; when `access_control` is ON, calls `getMemberValidUntil()` to set `Valid.enable` and `Valid.endTime`
- `getMemberValidUntil(Member $member, int $graceDays)`: queries `PaymentMembership` joined via `payment.member_id`, takes `max(end_date)`, adds grace days; returns Carbon or null

### Controller & Routes

**`app/Http/Controllers/Api/BiometricApiController.php`**

Settings routes (guard: `auth` + `permission:settings.manage`):

| Method | Path | Action |
|---|---|---|
| `POST` | `/api/settings/biometric/test-connection` | `testConnection()` |
| `POST` | `/api/settings/biometric/sync-all` | `syncAllMembers()` |
| `GET` | `/api/settings/biometric/recent-logs` | `recentLogs()` — last 20 logs with member info |
| `GET` | `/api/settings/biometric/access-events` | `accessEvents()` — real-time auth events (success/attempted) with captured pictures; optional `result` filter |
| `POST` | `/api/settings/biometric/access-events/sync` | `syncAccessEvents()` — dispatches `ImportBiometricAccessEventsJob` to import all device-held events (queued) |

Member routes (guard: `auth` + `permission:users.edit`):

| Method | Path | Action |
|---|---|---|
| `POST` | `/api/members/{member}/biometric-sync` | `syncMember()` — manual sync, returns `biometric_last_synced_at` |
| `GET` | `/api/members/{member}/biometric-logs` | `memberLogs()` — last 20 logs for this member |

### Member Sync Hooks

`MemberService` injects `BiometricSyncService` alongside `MediaStorageService`. Hooks are fire-and-forget (errors never bubble up):

- `store()`: after `Member::create()` → `$this->biometric->syncMember($member, 'create')`
- `update()`: after member save + user sync → `$this->biometric->syncMember($member, 'update')`
- `destroy()`: **before** delete → `$this->biometric->syncMember($member, 'delete')`
- `show()`: includes `'biometric_last_synced_at'` in the returned array

### Queue Job

**`app/Jobs/ImportBiometricAccessEventsJob.php`**
- Implements `ShouldQueue` (`$tries = 1`, `$timeout = 600`); constructor `(private readonly int $tenantId)`
- `handle(BiometricSyncService $biometric)`: resolves the `Tenant`, binds it via `app()->instance('tenant', $tenant)` (REQUIRED so `MediaStorageService` can namespace stored snapshots), then calls `$biometric->importDeviceEvents($tenant)`
- Dispatched by `BiometricApiController::syncAccessEvents()` (the "Sync from Device" button in Card 5)
- Replaces the old 30-minute attendance-polling scheduler (removed); real-time push + on-demand import now cover device events

### Frontend

**`resources/js/spa/pages/BiometricSettingsPage.vue`** — settings page at `/#/settings/biometric`:
- Card 1 — **Device Setup**: master enable toggle + conditional config form (device maker select, model select, IP, port, username, password). "Test Connection" saves config first, then calls `POST /api/settings/biometric/test-connection`.
- Card 2 — **Sync Settings** (shown only when `biometric.enabled = 1`): member up-sync toggle + "Sync All Members" button; access control toggle + grace period input.
- Card 3 — **Recent Sync Events**: table of last 20 log entries from `GET /api/settings/biometric/recent-logs`.
- Card 5 — **Recent Authentication Events**: real-time face/card/fingerprint scans from `GET /api/settings/biometric/access-events`, each showing the captured snapshot, the person, the credential method, and a `success` (attendance marked) / `failed` (attempted) badge; filterable by result. A "Sync from Device" button posts to `POST /api/settings/biometric/access-events/sync` to queue a full device-event import, then refreshes the list.
- `DEVICE_REGISTRY` constant defines supported makers/models — add new devices here: `{ hikvision: { label: 'HikVision', models: [{ value: 'DS-K1T320MFWX-B', label: '...' }] } }`

**`resources/js/spa/components/member/MemberBiometricTab.vue`** — biometric tab on member detail view:
- Props: `memberId` (Number), `lastSyncedAt` (String nullable), `canSync` (Boolean)
- Emits: `synced(biometric_last_synced_at)` — parent updates member record
- On mount: checks if `biometric.sync_members === '1'` via `/api/settings/configuration`; shows "not configured" notice if false
- Shows last synced timestamp + "Sync to Device" button → `POST /api/members/{id}/biometric-sync`
- Shows sync history table from `GET /api/members/{id}/biometric-logs`

**Router entry** (`resources/js/spa/router.js`):
```js
{ path: '/settings/biometric', component: BiometricSettingsPage, meta: { title: 'Biometric Device' } }
```

**Navigation** (`resources/js/spa/composables/useNavigation.js`):
- "Biometric" entry added to settings children, guarded by `context.permissions?.settings`

### Adding a New Device Maker

1. Create `app/Services/{Maker}Service.php` implementing the same interface as `HikvisionService` (methods: `testConnection`, `addPerson`, `updatePerson`, `deletePerson`, `getAccessEvents`)
2. Add to `BiometricSyncService::DRIVERS`: `'newmaker' => NewMakerService::class`
3. Update `BiometricSyncService::buildDriver()` to instantiate the new service
4. Add models to `DEVICE_REGISTRY` in `BiometricSettingsPage.vue`
5. Map authentication event minor codes in `SUCCESS_AUTH_MINORS` / `FAILED_AUTH_MINORS` used by `classifyAuthEvent()` (covers both real-time push and queued import)
