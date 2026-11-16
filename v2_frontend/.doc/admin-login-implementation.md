# Tenant admin sign-in and navigation shell

## Scope

The v2 application is an isolated React + TypeScript SPA in `v2_frontend/`. It implements the tenant staff sign-in flow and a minimal responsive navigation shell only. The shell contains one generic Workspace placeholder; no dashboard, reports, legacy feature pages, or business data screens are included.

## Component structure

- `src/App.tsx` owns only session restoration and login/logout orchestration.
- `src/components/Brand.tsx` provides the shared FitFlow lockup.
- `src/components/forms/FormField.tsx` provides labeled text and password fields, including the accessible password visibility toggle.
- `src/components/login/LoginScreen.tsx` composes the responsive sign-in layout; `LoginForm.tsx` owns form state, validation feedback, and submit/loading presentation.
- `src/components/workspace/WorkspaceShell.tsx` composes the authenticated shell and handles sign-out feedback.
- `WorkspaceSidebar.tsx`, `WorkspaceHeader.tsx`, and `WorkspaceNavigation.tsx` separate persistent desktop navigation and the shared generic destination from the header.
- `WorkspaceDrawer.tsx` provides tablet/phone navigation with Escape dismissal, focus trapping/restoration, backdrop dismissal, and sign-out.
- `Avatar.tsx` provides a reusable neutral person-icon avatar; `ProfileMenu.tsx` composes it with the keyboard/touch accessible profile popover. It does not expose fabricated personal profile data.
- `src/styles.css` defines the isolated light theme and responsive component styling; it does not depend on the legacy application stylesheet.

## Verified tenant staff auth contract

- `POST /api/auth/login` accepts `login` and `password`, returns JSON, and uses the Laravel web session.
- `POST /api/auth/logout` requires an authenticated session.
- Tenant API routes are in the Laravel `web` middleware group, so browser requests send cookies and a CSRF header.
- `GET /login` returns the tenant's session-bound `<meta name="csrf-token">`; the app reads this token before login/logout. Sending the encrypted `XSRF-TOKEN` cookie as a plain `X-CSRF-TOKEN` caused HTTP 419, so do not substitute the cookie value for the meta token.
- `GET /api/app/context` is the verified authenticated endpoint used only to restore the shell's signed-in state after reload. It does not render or fetch dashboard features.
- An authenticated `GET /login` intentionally redirects to `/dashboard`, and `/dashboard` redirects to the legacy SPA hash route. The v2 app checks `/api/app/context` before loading the login page, so it does not mistake this expected redirect for a login failure. To get a fresh CSRF meta token for logout, the Vite-only `/__tenant-root` proxy maps to Laravel `/`, which serves the authenticated tenant shell.
- The client sends `credentials: 'include'` and the `/login` meta token in `X-CSRF-TOKEN`. It does not call a Sanctum endpoint, store credentials, or implement an unverified password-recovery flow.

## Production API proxy

The Vite development proxy targets `https://cxfit.beforward.lk`; relative `/api/*`, `/login`, and `/__tenant-root` requests are forwarded to that production tenant host so the CSRF meta token and session cookies share one host/session. `/__tenant-root` is rewritten to `/` upstream. `VITE_API_PROXY_HOST=cxfit.beforward.lk` preserves host-based tenant identification. Vite rewrites the upstream cookie domain for local same-origin development; the browser application still uses credentialed relative requests and does not hardcode the API URL in its client bundle.

## Responsive navigation

The sidebar intentionally contains only the generic Workspace destination and sign-out; no legacy module items or dashboard content are included. Desktop uses a persistent labeled sidebar. Tablet and phone use a top bar with a menu button and generic initials avatar; the menu opens a drawer with the same Workspace entry and sign-out. The avatar opens an accessible profile popover. There is no bottom navigation. The authenticated main area remains a minimal empty placeholder.

## Current visual direction

For this iteration, the latest light UI references supersede the earlier dark dashboard palette in the general design-system notes: warm light-gray canvas, white surfaces, soft gray borders, near-black text, and restrained blue actions/active states. Components retain visible focus, disabled, hover, loading, and error states, and use safe-area padding and reduced-motion handling on narrow screens.