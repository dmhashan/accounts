---
applyTo: "v2_frontend/**"
---

# v2_frontend — Verified Rules

## Current state
- `v2_frontend/` currently contains `.doc/` only. It has no `package.json`, `tsconfig.json`, source tree, dependencies, scripts, or path aliases.
- Do not assume React, Vue, TypeScript, a router, state/query/form library, UI kit, API client, or alias until v2 config files and source verify it.
- The repository-root app is a separate Laravel/Vue 3 app. Do not treat its dependencies or paths as v2 dependencies.

## Rules
- Read the actual `v2_frontend/package.json`, TypeScript config, Vite config, and source tree before using a library, command, alias, or folder path. If absent, ask before scaffolding; never invent one.
- Use only dependencies and versions declared by the v2 package manifest. Do not claim a version is installed based on a version range alone; check the lockfile when exact resolved versions matter.
- Follow configured aliases exactly. Do not introduce `@/` or other aliases unless configured and verified.
- Keep v2 APIs under `/api/*`; verify each endpoint, payload, response, and middleware in Laravel route/controller/service source before use. Do not invent endpoints or DTOs.
- Keep portal, tenant staff, and member authentication isolated. Verify the backend flow for the target surface; never assume shared cookies, tokens, or guards.
- Enforce tenant scope and permissions on the backend; client-side navigation or route guards are not authorization.
- Support phone, tablet, desktop, and large displays. Reflow content; do not remove business data, actions, or tenant navigation at smaller sizes.
- Follow `v2_frontend/.doc/design-system.md` for the supplied visual direction. Treat page layouts as undecided until specified.
- Add/update tests using the test runner actually declared by the v2 package. Do not invent scripts.
- Keep edits focused; omit unrelated files and placeholder code. Document verified architectural decisions in `v2_frontend/.doc/`.