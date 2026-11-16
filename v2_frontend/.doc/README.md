# FitFlow v2 frontend documentation

This folder is the working source of truth for planning and implementing the FitFlow v2 frontend. It documents the existing Laravel application as observed on 2026-11-16, separates existing behavior from proposed v2 product boundaries, and records the visual direction supplied for the new UI. Detailed page layouts and component arrangements will be added after they are provided.

## Documents

- [System overview and SPA boundaries](system-overview.md) — product/domain map, current architecture, authentication boundaries, tenant routing, and implementation principles.
- [API catalog](api-catalog.md) — existing API route groups, controllers, authorization boundaries, integration guidance, and how to inspect the authoritative live route inventory.
- [Visual direction and responsive rules](design-system.md) — reference-image palette, typography, surface treatments, responsive behavior, and decisions intentionally left open.
- [Copilot instructions](copilot-instructions.md) — working constraints for future agents contributing to v2.

## Status and source precedence

1. Existing behavior and endpoints are verified against the Laravel routes and frontend files in this repository; linked source files are authoritative where this documentation is abbreviated.
2. The five-SPA split and FitFlow product identity are requirements for v2, not a claim that the current code already implements five independent SPAs.
3. The supplied reference images establish a visual direction, not final page composition or interaction requirements.
4. Before implementing a page, confirm the applicable API payloads and permissions in source. Refresh this catalog when routes change.
5. Keep the v2 code isolated in `v2_frontend/`; do not move or replace legacy application code unless explicitly requested.

## Useful verification commands

- `php artisan route:list --path=api` — live API method/URI/action/middleware inventory.
- `npm run build` — current Vite build.
- `npm test` — current frontend tests.
- `npm run lint` — current frontend lint.
- `composer test` — backend tests.
