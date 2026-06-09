# Tenant Database Isolation Plan

## Decision

Do not switch the live application directly from row-scoped tenancy to
database-per-tenant tenancy.

The current application has one default database, resolves a tenant by
subdomain, and filters records with `tenant_id`. The database contains both the
tenant registry and tenant-owned data. Of 110 migrations, 48 reference
`tenant_id`, and 13 directly create foreign keys to `tenants`. Queue jobs,
scheduled commands, the biometric webhook, sessions, cache, and auth also
assume the current default connection.

The safe design is a staged, feature-flagged migration with per-tenant
verification and rollback.

## Target Architecture

### Central Database

The central database owns platform-level records only:

- `tenants`
- tenant subdomain/domain mapping
- `tenant_uuid`
- `database_name`
- tenant lifecycle status
- schema/template version and migration status
- central queue, failed jobs, cache, sessions, and migration audit records

Proposed tenant registry fields:

```text
id
tenant_uuid
name
domain
database_name
status
schema_version
provisioned_at
last_migrated_at
created_at
updated_at
```

`Tenant` must always use the `central` connection.

### Tenant Databases

- One physical database per tenant.
- Database name: `tenant_<uuid_without_hyphens>`.
- Fresh template database: `_blank`.
- Tenant databases contain users, roles, permissions, members, payments,
  inventory, sales, accounts, forms, events, notifications, settings, audit
  logs, and all other tenant-owned data.
- Keep `tenant_id` during the first cutover. It is defense in depth and avoids
  rewriting every query and unique key during the risky phase.
- Each tenant database temporarily keeps one shadow `tenants` row with the
  same central tenant ID/UUID so existing foreign keys remain valid. Runtime
  tenant lookup still comes only from the central database.

### Connection Rules

- `central`: fixed connection from environment.
- `tenant`: runtime connection whose database is selected from the central
  tenant registry.
- Before auth or tenant model access, a tenant resolver must:
  1. query `Tenant` on `central`;
  2. validate `database_name` against the UUID-derived allowlist format;
  3. configure and purge the `tenant` connection;
  4. set tenant-owned models/default queries to `tenant`;
  5. bind the central `Tenant` object as `app('tenant')`.
- Never accept a database name from a request parameter.

## `_blank` Template

`_blank` is a schema/template database, not a tenant and not a production data
store.

Preparation workflow:

1. Recreate `_blank` in a controlled maintenance job.
2. Run all tenant migrations against `_blank`.
3. Seed only required reference data, such as permissions and default roles.
4. Verify that all business tables are empty.
5. Record a schema fingerprint and application release version centrally.
6. Provision a new tenant by cloning `_blank`, inserting its shadow tenant row,
   running any pending migrations, and then registering the database mapping.

MySQL has no general-purpose `CREATE DATABASE ... CLONE ...` command. Cloning
must use an approved mechanism such as a managed database snapshot/restore,
`mysqldump`/restore, or carefully controlled `CREATE TABLE LIKE` plus reference
data copy. The production mechanism must be tested with the actual hosting
platform before implementation.

## Required Commands

Implement these only after the coverage gate in
`docs/feature-endpoint-inventory.md` is met:

```text
php artisan tenants:prepare-blank
php artisan tenants:provision {tenant}
php artisan tenants:migrate {--tenant=} {--pretend} {--force}
php artisan tenants:verify {tenant}
php artisan tenants:cutover {tenant} {--dry-run}
php artisan tenants:rollback-cutover {tenant}
```

`tenants:migrate` requirements:

- Read tenant mappings from `central`.
- Acquire a central per-tenant migration lock.
- Migrate `_blank` first.
- Migrate tenants one at a time, with clear success/failure audit records.
- Stop on failure by default; support an explicit continue option only for
  operators.
- Support `--pretend`, a single tenant canary, and resumable execution.
- Never run concurrent schema changes against the same tenant.

## Existing Tenant Data Cutover

For each tenant:

1. Take and verify a restorable central database backup.
2. Put only that tenant into write maintenance mode.
3. Clone `_blank` into its UUID-derived database.
4. Insert the tenant shadow row.
5. Copy only rows owned by that tenant, preserving primary keys.
6. Copy dependent rows without `tenant_id` through their parent relationship.
7. Verify row counts, key financial totals, foreign keys, and checksums.
8. Run the complete tenant acceptance suite against the new database.
9. Change only that tenant's central database mapping.
10. Observe logs, queues, webhooks, and financial writes before the next tenant.

Tables without a direct `tenant_id` need explicit parent-based copy rules,
including sale items, workout child tables, event guests, notification
recipients, reconciliation entries, and role-permission pivots.

## Runtime Paths That Must Activate a Tenant

- `IdentifyTenant` middleware
- root landing route after tenant lookup
- biometric webhook
- all queue jobs before tenant-owned model queries
- all scheduled notification/report commands
- legacy sync commands
- tenant provisioning, migration, verification, and seeding commands
- any CLI/Tinker operation that reads tenant data

Database-backed sessions, cache, and queue must stay central because session
middleware and queue reservation happen before tenant resolution.

## Verification

Minimum automated verification:

- Two real MySQL tenant databases plus a central database in CI.
- Same primary IDs in two tenant databases to prove physical routing.
- Cross-subdomain request isolation.
- Cross-tenant authentication rejection.
- Route model binding isolation.
- Queue job and failed-job isolation.
- Scheduled command iteration across every tenant.
- Biometric webhook isolation.
- File/media namespace isolation.
- `_blank` contains no tenant business data.
- New tenant provisioning and rollback.
- All-tenant migration success, partial failure, retry, and lock behavior.
- Financial row counts and totals before/after cutover.

## Rollout and Rollback

- Ship connection and resolver code disabled by a feature flag.
- Cut over a non-production clone, then an internal tenant, then one live
  canary tenant.
- Keep the original central rows read-only during the rollback window.
- Roll back by disabling the tenant mapping/feature flag for that tenant.
- Do not delete old rows until backups and the agreed retention window expire.

## Current Stop Condition

No hard database-isolation change is included in this phase. The backend suite
is green at 204 tests and 1,090 assertions, with 68.80% PHP line coverage, and
the frontend suite is green at 42 tests with 63.97% line coverage. Dedicated
tests now cover the previously missing high-risk API and background-work
groups. However, frontend coverage remains narrow, device-driver branches are
still lightly covered, and no real MySQL multi-database integration environment
is present. Claiming "zero error" or "100% confidence" under those conditions
would still be misleading.
