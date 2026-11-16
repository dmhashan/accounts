# FitFlow v2 system overview

## Product identity

- Product: **FitFlow**
- Primary product domain: `fitflow.lk`
- Product landing page: `fitflow.lk`
- Super-admin portal: `portal.fitflow.lk`
- Tenant host pattern: `{tenant}.fitflow.lk`

FitFlow is a multi-tenant gym and fitness-center management SaaS. Tenant capabilities in the existing application include members, staff/users and roles, payments, sales/POS, accounts and expenses, inventory, workouts, events, notifications, reports, settings, and biometric access integrations.

## Required v2 application surfaces

The product is planned as five independently navigable application experiences. They may share UI primitives and API clients, but must keep routing, authorization, layouts, and deployment/build boundaries explicit.

| # | Experience | Host/path | Audience and scope | Authentication |
|---|---|---|---|---|
| 1 | FitFlow product landing | `fitflow.lk` | Public product information and conversion journey | Public |
| 2 | Super-admin panel | `portal.fitflow.lk` | FitFlow operators managing tenants and tenant users | Portal-specific auth; do not reuse tenant staff credentials/session |
| 3 | Tenant fitness-center landing | `{tenant}.fitflow.lk` | Public, tenant-branded gym pages and public calls to action | Public; public event flows may have their own verification |
| 4 | Tenant admin/staff/coach panel | `{tenant}.fitflow.lk/admin` | Tenant employees, administrators, and coaches; permission-gated management | Tenant staff auth |
| 5 | Tenant member panel | `{tenant}.fitflow.lk/member` | Signed-in gym members viewing self-service features | Separate member auth from staff auth |

### Auth boundary notes

There are at least three distinct trust contexts in the current backend: portal users, tenant staff, and member self-service. The request names two distinct tenant logins (staff and member); portal authentication is a third isolated context. Keep credentials, cookies/tokens, refresh/logout behavior, route guards, and API clients separated by audience. Never treat a client-side route guard as authorization; enforce access in the backend.

Existing mechanisms observed:

- **Tenant staff:** Laravel `web` session, CSRF-protected requests, `/api/auth/login`, `/api/auth/logout`, and `/api/auth/refresh`. Session behavior lives in `app/Services/AuthSessionService.php` and `app/Http/Controllers/Api/AuthApiController.php`; the shared legacy SPA client is `resources/js/spa/composables/useApiClient.js`.
- **Super-admin portal:** `/api/portal/auth/request-otp` and `/api/portal/auth/login`; authenticated routes use `auth:portal`. Mutations are grouped behind `VerifyPortalOtp`. See `routes/api/portal.php` and the portal auth controller.
- **Member self-service:** OTP request/verify endpoints issue a token passed in `X-PP-Token`; `pp.token` resolves it from cache. See `routes/api/public-profile.php` and `app/Http/Middleware/ResolvePpToken.php`. Confirm whether v2 `/member` adopts this mechanism or requires a new member-login flow before designing auth screens.

## Existing repository vs. v2 target

The current application is Laravel 12 + Vue 3 + Vite 7 + Tailwind CSS 4. It has a tenant management Vue SPA, a portal Vue app, and a member public-profile Vue app, along with public/tenant landing routes and legacy Blade behavior. This is not yet a verified implementation of five isolated SPAs. Existing UI entry points and build configuration:

- Tenant management: `resources/js/spa/main.js`, `resources/views/spa.blade.php`, `resources/js/spa/router.js`.
- Super-admin portal: `resources/js/portal/main.js`, `resources/views/portal-spa.blade.php`.
- Member profile: `resources/js/public-profile.js`, `resources/views/members/public-profile.blade.php`.
- Public/tenant web routing: `routes/web.php`.
- Vite entries: `vite.config.js`.

Keep the v2 frontend under `v2_frontend/` during planning and development. Do not assume v2 must preserve the current hash routing. Agree on path routing, host constraints, deployments, and session cookie scope before replacing or extending current route behavior.

## Tenant and request model

Tenant identification is server-side. `InitializeTenantConnection` initializes the tenant database context; `IdentifyTenant` resolves/validates the tenant for tenant routes. Tenant routes are loaded under `web` and `IdentifyTenant` in `routes/api.php`. Do not infer the tenant from an untrusted frontend parameter or rely on hidden navigation to isolate data. All server-side reads and writes must remain tenant-scoped.

The host model should remain explicit:

- `fitflow.lk` — product marketing surface.
- `portal.fitflow.lk` — platform operations.
- `{tenant}.fitflow.lk` — tenant public/admin/member experiences.

Confirm production DNS, wildcard certificates, cookie domain/samesite settings, cross-subdomain CSRF behavior, and whether API calls are same-origin before finalizing client configuration.

## Current tenant app capabilities (discovery baseline)

The existing route tree and APIs cover these areas; this list is a starting point for v2 feature mapping, not a final information architecture:

- Dashboard and app context.
- Members, staff/users, roles and permissions.
- Payments, payment plans/methods, wallets, sales, accounts, settlements, expenses and reports.
- Inventory and vouchers.
- Exercises, workout programs, assignments, employee/pay sheets.
- Events, registrations, campaigns, forms, notifications and member activity.
- General/configuration settings and external integrations (WhatsApp, GoWa, biometric devices).

Member-facing capabilities currently exposed include profile, wallet transaction history, body measurements, notifications, event registration, workouts/payments/attendance features where enabled. Confirm exact endpoint availability and permission rules against the current backend before mapping pages.

## Responsive and interaction principles

- Design mobile-first and provide explicit phone, tablet, laptop, and large-display layouts.
- Keep all business information and actions available at every breakpoint; reflow or change presentation instead of dropping data.
- Admin/staff navigation must preserve access to every module on mobile and tablet, even when using a compact bottom bar plus a full navigation drawer.
- Optimize common member tasks for touch; avoid hover-only actions and dense tables on narrow screens.
- Support keyboard navigation, visible focus, semantic labels, sufficient contrast, and reduced motion.
- Tablet is a primary form factor, not merely a stretched phone or compressed desktop.
- Use the reference visual direction in `design-system.md`, while waiting for user-provided page layouts before deciding dashboard arrangement.

## Architecture questions to resolve before implementation

1. Whether all five experiences ship from one Vite project with separate entries or from separately deployable frontend apps.
2. Whether the tenant admin and member SPAs use `/api/*` on the tenant origin, and how portal calls are hosted.
3. Member auth/session/token mechanism and account recovery requirements for `/member`.
4. Tenant public landing content, custom domains, branding/theme settings, and public route ownership.
5. Staff role taxonomy (administrator/staff/coach) and permissions needed for coach workflows.
6. Route migration/redirect plan from the existing SPA and `/profile` member portal.
7. Shared packages, design tokens, locale/currency/date conventions, offline expectations, and analytics requirements.
