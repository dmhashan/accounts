# Tenant admin sign-in and navigation shell

## Scope

The v2 application is an isolated React + TypeScript SPA in `v2_frontend/`. It implements the tenant staff sign-in flow and a responsive, personalized workspace shell. No dashboard metrics, reports, legacy feature pages, or business data screens are included.

## Component structure

- `src/App.tsx` owns session restoration and login/logout orchestration, retaining the authenticated app-context payload for the workspace shell.
- `src/components/Brand.tsx` provides the shared FitFlow lockup and tenant logo/name lockup with a FitFlow attribution.
- `src/components/forms/FormField.tsx` provides labeled text and password fields, including the accessible password visibility toggle.
- `src/components/login/LoginScreen.tsx` composes the responsive sign-in layout; `LoginForm.tsx` owns form state, validation feedback, and submit/loading presentation.
- `src/components/workspace/WorkspaceShell.tsx` composes the authenticated shell and handles sign-out feedback.
- `WorkspaceSidebar.tsx`, `WorkspaceHeader.tsx`, and `WorkspaceNavigation.tsx` separate persistent desktop navigation and the shared generic destination from the header.
- `WorkspaceDrawer.tsx` provides tablet/phone navigation with Escape dismissal, focus trapping/restoration, backdrop dismissal, and sign-out.
- `Avatar.tsx` derives initials from the authenticated user's name; `ProfileMenu.tsx` displays the returned name, email, and tenant in a keyboard/touch accessible profile popover.
- `TenantDateTime.tsx`, `FormattedDate.tsx`, `FormattedTime.tsx`, and `src/lib/dateTime.ts` format local browser date/time according to the verified tenant `dateFormat` and `timeFormat` settings; invalid values use a safe fallback.
- `PoweredByFitFlow.tsx` provides the restrained product attribution in the desktop sidebar and mobile drawer.
- `WorkspaceWelcome.tsx` presents a personalized staff greeting and tenant connection state without inventing dashboard metrics or legacy feature navigation.
- `src/styles.css` defines the isolated light theme and responsive component styling; it does not depend on the legacy application stylesheet.

## Verified tenant staff auth contract

- `POST /api/auth/login` accepts `login` and `password`, returns JSON, and uses the Laravel web session.
- `POST /api/auth/logout` requires an authenticated session.
- Tenant API routes are in the Laravel `web` middleware group, so browser requests send cookies and a CSRF header.
- `GET /login` returns the tenant's session-bound `<meta name="csrf-token">`; the app reads this token before login/logout. Sending the encrypted `XSRF-TOKEN` cookie as a plain `X-CSRF-TOKEN` caused HTTP 419, so do not substitute the cookie value for the meta token.
- `GET /api/app/context` is the verified authenticated endpoint used to restore signed-in state and load user, tenant, logo, settings, and permission context. It does not render or fetch dashboard features.
- The context contract is the flat JSON returned by `AppContextService::build()`: `settings`, `user`, `tenant`, and `permissions`. Valid palette values are `crimson`, `ocean`, `forest`, `violet`, `sunset`, and `slate`; date/time formats use the backend enums.
- `settings.colorMode` is one of `system`, `light`, or `dark`; the shell follows that preference and tracks OS appearance changes for system mode. Unknown theme/mode values safely fall back to `crimson`/`system`.
- An authenticated `GET /login` intentionally redirects to `/dashboard`, and `/dashboard` redirects to the legacy SPA hash route. The v2 app checks `/api/app/context` before loading the login page, so it does not mistake this expected redirect for a login failure. To get a fresh CSRF meta token for logout, the Vite-only `/__tenant-root` proxy maps to Laravel `/`, which serves the authenticated tenant shell.
- The client sends `credentials: 'include'` and the `/login` meta token in `X-CSRF-TOKEN`. It does not call a Sanctum endpoint, store credentials, or implement an unverified password-recovery flow.

## Production API proxy

The Vite development proxy targets `https://cxfit.beforward.lk`; relative `/api/*`, `/login`, and `/__tenant-root` requests are forwarded to that production tenant host so the CSRF meta token and session cookies share one host/session. `/__tenant-root` is rewritten to `/` upstream. `VITE_API_PROXY_HOST=cxfit.beforward.lk` preserves host-based tenant identification. Vite rewrites the upstream cookie domain for local same-origin development; the browser application still uses credentialed relative requests and does not hardcode the API URL in its client bundle.

## Responsive navigation

The sidebar intentionally contains only the generic Workspace destination and sign-out; no legacy module items or dashboard metrics are included. Desktop uses a persistent labeled sidebar. Tablet and phone use a top bar with a menu button and user initials; the menu opens a drawer with the same Workspace entry and sign-out. The avatar opens an accessible profile popover. There is no bottom navigation. The authenticated main area is a responsive personalized welcome surface.

## Current visual direction

For this iteration, the latest light UI references supersede the earlier dark dashboard palette in the general design-system notes: warm light-gray canvas, white surfaces, soft gray borders, near-black text, and restrained tenant-colored actions/active states. Components retain visible focus, disabled, hover, loading, and error states, and use safe-area padding and reduced-motion handling on narrow screens. Tenant accent colors use the verified legacy palette values from `resources/js/spa/composables/useTheme.js`. Date and time use the browser's local timezone because the current context contract does not expose a tenant timezone.