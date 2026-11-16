---
applyTo: "v2_frontend/**"
---

# FitFlow v2 frontend rules

## Verified stack
- Use React 19.3.1, TypeScript 7.0.2, Vite 8.3.8, and `lucide-react` 1.73.0 as pinned in `v2_frontend/package.json`.
- Use `src/App.tsx` for app/session orchestration; place shared UI in `src/components/`, API/context code in `src/lib/api.ts`, date/time formatting in `src/lib/dateTime.ts`, and isolated styles in `src/styles.css`.
- Do not assume a router, CSS framework, component kit, server-state library, test runner, or path alias. None is configured. Import via relative paths.
- Use the v2 package scripts only: `npm run dev`, `npm run build`, and `npm run typecheck`.

## API and security
- Use the centralized native `fetch` helpers in `src/lib/api.ts`; send credentialed `/api/*` requests and follow its Laravel session/CSRF handling. Do not add a second client or persist credentials/tokens.
- Verify endpoint methods, payloads, response shapes, and middleware in Laravel route/controller/service source before use. Do not invent endpoints or DTO fields.
- Keep portal, tenant-staff, and member authentication isolated. Enforce tenant scope and authorization on the backend; client navigation is not authorization.
- Do not log secrets or retry unsafe mutations automatically.

## UI rules
- Use small typed, accessible React components; keep components focused and reuse shared form, branding, profile, navigation, and date/time components.
- Read tenant, user, permissions, and settings from the verified app-context response. Use tenant theme values only after validating them against supported options; format dates/times with `src/lib/dateTime.ts`.
- Follow `v2_frontend/.doc/design-system.md`. Support phone, tablet, desktop, and large displays; retain every action/data point, use a mobile/tablet drawer and profile menu, and never add bottom navigation.
- Keep legacy Laravel/Vue files unchanged unless explicitly requested. Document verified contract or design changes in `v2_frontend/.doc/`.