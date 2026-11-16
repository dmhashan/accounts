# API catalog — existing backend baseline

This catalog documents the API boundary the v2 frontend must integrate with. Routes are registered in Laravel and are the source of truth; request/response field schemas should be checked in the named controller, Form Request, and service before implementing each screen.

## API conventions

- Base prefix: `/api/`.
- Tenant API requests run through Laravel `web` middleware and `IdentifyTenant` (including session and CSRF behavior); the backend resolves the tenant from the host/bypass configuration.
- Tenant staff endpoints generally require `auth` and one or more `permission:*` middleware checks.
- Portal endpoints use a separate `/api/portal/*` namespace and `auth:portal` after login; mutations are protected by `VerifyPortalOtp`.
- Public member endpoints use `/api/public/*`. Member-only requests send `X-PP-Token` and pass `pp.token` middleware.
- Public device endpoints are exceptional: biometric webhook and ZKTeco ADMS routes are registered outside the normal tenant API group. Do not expose these as normal browser-app endpoints.
- GET routes may appear as `GET|HEAD` in Laravel output.
- Use JSON requests/responses unless a route handles an upload/download (for example multipart form data, CSV/PDF, or file streams). Verify individual controller behavior.
- Existing SPA uses Axios with cookies, `Accept: application/json`, CSRF token handling, and one refresh/retry on 401. Portal and member clients should not blindly share its authentication assumptions.

## Existing route groups

All paths below are relative to `/api`. The route source is given for each group. This is a module index rather than a copied 339-route dump; the route files contain the full endpoint definitions, including dynamic IDs, action endpoints, filters, and special flows. Generate the current exact URI/method/controller/middleware table with `php artisan route:list --path=api` whenever the backend changes.

| API area | Source | Endpoint family / purpose | Typical access |
|---|---|---|---|
| Health | `routes/api/health.php` | Health/status checks | Public/operational; inspect middleware |
| Tenant staff auth | `routes/api/auth.php` | `/auth/login`, `/auth/logout`, `/auth/refresh` | Login/refresh public as defined; logout authenticated |
| Tenant app context | `routes/api/context.php` | `/app/context` and context data | Tenant staff session |
| Dashboard | `routes/api/dashboard.php` | Dashboard summary/data | Authenticated tenant staff and dashboard permission |
| Users/staff | `routes/api/users.php` | User list/detail/meta/create/update/status/password reset/delete | `users.*` permissions |
| Members | `routes/api/members.php` | Member CRUD/meta, payments/plans, wallet, documents, measurements, biometric, member-linked features | User/member/payment or feature permissions; varies by endpoint |
| Roles | `routes/api/roles.php` | Role list/detail/create/update/permission assignment | `roles.view`, `roles.permissions` |
| Settings/integrations | `routes/api/settings.php` | General/configuration, logo, manager notifications, WhatsApp, GoWa, biometric controls/logs/events/queues | Settings and integration permissions |
| Reports | `routes/api/reports.php` | Overview, profit/daily summaries, member analysis, exports/PDF/email | `reports.*` |
| Inventory | `routes/api/inventory.php` | Products, variations, stock, display/release workflows | `inventory.*` permissions |
| Accounts/settlements | `routes/api/accounts.php` | Company accounts, transactions/transfers/expenses, settlements | `accounts.manage` or related finance permissions |
| Sales/POS | `routes/api/sales.php` | Sales CRUD, metadata, wallet, marking paid | Sales permissions |
| Workouts | `routes/api/workouts.php` | Exercises, programs, days, exercises/extras, assignments, member views | `workouts.*` permissions |
| Payments | `routes/api/payments.php` | Payment methods/plans/payments and payment workflows | `payments.manage` or related permissions |
| Employees/pay sheets | `routes/api/employees.php` | Employees, pay sheets, related biometric operations | Employee/payroll permissions; inspect route middleware |
| Notifications | `routes/api/notifications.php` | Tenant notifications and send/history operations | Notifications permissions |
| Events | `routes/api/events.php` | Event management and registrations | `events.manage` or registration permissions |
| Member activity | `routes/api/activity.php` | Member activity records | `activity.view` |
| Vouchers | `routes/api/vouchers.php` | Voucher CRUD and validation-related flows | `vouchers.manage` |
| Forms | `routes/api/forms.php` | Form definitions/responses | Form permissions; inspect route middleware |
| Campaigns | `routes/api/campaigns.php` | Campaign management, statuses, registrations/public campaign flows | Campaign permissions; some public actions |
| Chatbot | `routes/api/chatbot.php` | Chatbot integration endpoints | Inspect exact authentication/throttling |
| Portal auth/admin | `routes/api/portal.php` | Portal auth, dashboard stats, tenant/user management, job status | Public auth actions; then `auth:portal`; mutations also OTP verified |
| Member public profile | `routes/api/public-profile.php` | OTP, profile, wallet, notifications, body measurements, public events/registration, terms, upcoming events | Public routes and separate `pp.token` member routes |
| Biometric webhook | `routes/api/biometric-webhook.php` | Device callback endpoints | Device-specific public/token URL; not normal browser session |
| ZKTeco ADMS | `routes/api/zkteco-adms.php` | `/iclock/*` device protocol | Device-specific; not normal browser session |

## Key auth and member endpoints

### Tenant staff

- `POST /api/auth/login`
- `POST /api/auth/logout` (authenticated)
- `POST /api/auth/refresh`
- `GET /api/app/context` (authenticated; app context endpoint is `/app/context`, not the older documented `/context` path)

### Super-admin portal

- `POST /api/portal/auth/request-otp`
- `POST /api/portal/auth/login`
- `POST /api/portal/auth/logout` (portal-authenticated)
- `GET /api/portal/auth/me` (portal-authenticated)
- `POST /api/portal/auth/action-otp` (portal-authenticated)
- `GET /api/portal/dashboard/stats` (portal-authenticated)
- `GET /api/portal/tenants/{tenant}` and tenant user/job endpoints (portal-authenticated)
- Tenant/user mutations and tenant/user resource management require portal auth and OTP verification.

### Member self-service

- `POST /api/public/request-otp` — throttled.
- `POST /api/public/verify-otp` — throttled; returns/establishes the member token flow.
- `GET /api/public/member-profile` — requires `X-PP-Token` via `pp.token`.
- `GET /api/public/wallet/transactions` — member token.
- `POST /api/public/body-measurements` — member token.
- `GET /api/public/notifications` — member token.
- Public event details/registration: `/api/public/event/{slug}`, `/api/public/event/{slug}/register`; registration is throttled.
- Member event registration access/update: `/api/public/event/{slug}/my-registration` (member token).
- Public utilities include `/api/public/activity`, `/api/public/upcoming-events`, and `/api/public/terms-and-conditions`.

## Tenant feature endpoints to discover per screen

Do not infer a feature's complete CRUD shape from its module prefix. Route files include nested workflows and non-CRUD actions such as metadata, status changes, exports, downloads, payment settlement, wallet operations, attendance, sync jobs, and integration tests. Before implementation:

1. Search the relevant route file for the exact method and URI.
2. Inspect its controller validation and response shape.
3. Inspect the service for business rules and tenant scoping.
4. Check the route permission middleware and `AppContextService` permission key.
5. Add a screen-specific contract note (query params, request body, response, loading/empty/error cases) under this folder as page arrangements become available.

For an exhaustive current endpoint table, use `php artisan route:list --path=api --json` (or its human-readable form without `--json`). Laravel's runtime route registry is preferred over a stale manually maintained duplicate.
