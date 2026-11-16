# GitHub Copilot instructions — FitFlow v2 frontend

These instructions apply to work inside `v2_frontend/`. Read [README.md](README.md), [system-overview.md](system-overview.md), [api-catalog.md](api-catalog.md), and [design-system.md](design-system.md) before making architectural or UI changes.

## Product and app boundaries

- Product name is **FitFlow**; product domain is `fitflow.lk`.
- Keep the five target experiences distinct: product landing (`fitflow.lk`), super-admin (`portal.fitflow.lk`), tenant landing (`{tenant}.fitflow.lk`), tenant staff/admin/coach (`{tenant}.fitflow.lk/admin`), and tenant member (`{tenant}.fitflow.lk/member`).
- Separate portal, tenant staff, and member authentication state and authorization. Never reuse a session/token across audiences by assumption.
- Keep v2 implementation isolated in this folder unless the user explicitly authorizes changes to legacy application code.
- Use backend APIs under `/api/*`; do not connect browser code directly to tenant databases or device integrations.

## API and security

- Verify endpoint methods, payloads, response shapes, and middleware in current Laravel route/controller/service code before relying on them; docs can age.
- Treat backend authorization and tenant scoping as mandatory. Hiding a link or guarding a client route does not secure an operation.
- Keep credentials, OTPs, CSRF values, and member tokens out of logs, source control, and URLs.
- Do not silently downgrade errors or retry unsafe mutations. Follow the auth flow documented for the specific SPA.
- Preserve the existing backend's validation and business rules; surface API validation errors in the relevant form.

## Visual and responsive rules

- Follow the evolving FitFlow tokens and component guidance in `design-system.md`.
- Support phones, tablets (portrait and landscape), desktop, and large displays. Reflow content; never remove business data/actions on small screens.
- Preserve complete tenant navigation access on mobile and tablet.
- Design touch-first controls alongside keyboard access, visible focus, semantic labels, WCAG AA contrast, and reduced-motion support.
- Do not finalize page information architecture or dashboard arrangement until the user supplies page-level UI direction.

## Engineering workflow

- Prefer reusable, accessible Vue components and typed/centralized API contracts when the implementation stack is confirmed.
- Keep app-specific shells, route guards, API clients, and session handling isolated while sharing design tokens and safe UI primitives.
- Add or update tests for behavior, authorization-dependent rendering, and responsive data parity where practical.
- Document new endpoint contracts and architecture decisions in `.doc/` when they are established; distinguish verified backend facts from proposals.
