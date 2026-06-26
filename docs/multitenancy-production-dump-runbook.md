# Multitenancy Production Dump Runbook

This runbook documents the local rehearsal for the database-per-tenant migration and the production-safe command order.

## Local Test Status

Local rehearsal date: 2026-06-27

Source dump:

```text
storage/db-dumps/latest-main.sql -> prod-main-20260610-004426.sql
```

Imported source database:

```text
accounts_prod_clone
```

Created local databases:

```text
portal
7db4e956-56fc-4c85-9ffb-28b6e09b1def
e53f790d-7c1f-4691-9668-221f14c1b562
a7d2c4f8-5b91-4e3d-8f76-19c2b5a4d8e7
_blank
```

Tenant mapping created in `portal.tenants`:

```text
cxfit -> 7db4e956-56fc-4c85-9ffb-28b6e09b1def
test  -> e53f790d-7c1f-4691-9668-221f14c1b562
thfit -> a7d2c4f8-5b91-4e3d-8f76-19c2b5a4d8e7
```

Local verification result:

```text
accounts_prod_clone.tenants: 3 rows
portal.tenants: 3 rows
portal.users table: absent
portal tables: tenants, cache, cache_locks, jobs, job_batches, failed_jobs, sessions
tenant UUID database table count: 69 each
_blank table count: 1
full test suite: 205 passed, 1092 assertions
```

Important local note: `_blank` currently contains only the `migrations` repository table. Laravel creates that table even during `migrate --pretend` when the database is empty.

## Safety Rules

Do not run destructive commands directly against production.

Do not run real tenant migrations yet:

```bash
php artisan tenants:migrate --force
```

Reason: the tenant migration set currently includes `2026_06_27_000001_drop_tenant_id_columns_from_isolated_tenant_databases`. The current application still has live `tenant_id` reads and writes, so the real migration must wait until app-side `tenant_id` cleanup is complete.

The local test intentionally did not run:

```bash
php artisan tenants:split-once ...
php artisan tenants:migrate --force
```

## Local Commands Already Run

### 1. Import Latest Dump

```bash
docker exec accounts_mysql mysql -uroot -proot \
  -e "DROP DATABASE IF EXISTS accounts_prod_clone; CREATE DATABASE accounts_prod_clone CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

docker exec -i accounts_mysql mysql -uroot -proot accounts_prod_clone < storage/db-dumps/latest-main.sql
```

Source dump verification:

```text
tables: 69
tenants: 3
```

### 2. Split Preflight

```bash
DB_CONNECTION=mysql \
DB_HOST=127.0.0.1 \
DB_PORT=3307 \
DB_DATABASE=accounts_prod_clone \
DB_USERNAME=root \
DB_PASSWORD=root \
CENTRAL_DB_DATABASE=portal \
TENANT_DATABASE_ISOLATION_ENABLED=false \
php artisan tenants:split-once --central=portal
```

Result:

```text
Preflight passed. No databases or rows were changed.
```

### 3. Execute Local Split

```bash
DB_CONNECTION=mysql \
DB_HOST=127.0.0.1 \
DB_PORT=3307 \
DB_DATABASE=accounts_prod_clone \
DB_USERNAME=root \
DB_PASSWORD=root \
CENTRAL_DB_DATABASE=portal \
TENANT_DATABASE_ISOLATION_ENABLED=false \
php artisan tenants:split-once \
  --central=portal \
  --execute \
  --backup-confirmed \
  --maintenance-confirmed \
  --force
```

Result:

```text
Tenant database split completed and verified.
Source deletion disabled; accounts_prod_clone remains unchanged.
```

### 4. Grant Local App User Access

```bash
docker exec accounts_mysql mysql -uroot -proot <<'SQL'
GRANT ALL PRIVILEGES ON `portal`.* TO 'laravel'@'%';
GRANT ALL PRIVILEGES ON `7db4e956-56fc-4c85-9ffb-28b6e09b1def`.* TO 'laravel'@'%';
GRANT ALL PRIVILEGES ON `e53f790d-7c1f-4691-9668-221f14c1b562`.* TO 'laravel'@'%';
GRANT ALL PRIVILEGES ON `a7d2c4f8-5b91-4e3d-8f76-19c2b5a4d8e7`.* TO 'laravel'@'%';
FLUSH PRIVILEGES;
SQL
```

### 5. Prepare Central Portal Tables

Current local `.env` is already set to the new architecture:

```env
DB_CONNECTION=central
DB_DATABASE=portal
CENTRAL_DB_DATABASE=portal
TENANT_DATABASE_ISOLATION_ENABLED=true

MULTITENANCY_ENABLED=false
MULTITENANCY_BYPASS_DOMAIN=cxfit

CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database
DB_CACHE_CONNECTION=central
DB_QUEUE_CONNECTION=central
SESSION_CONNECTION=central
TENANT_BLANK_DATABASE=_blank
```

Commands run:

```bash
php artisan config:clear
php artisan tenants:prepare-central --force
```

Result:

```text
Central database infrastructure is ready.
```

### 6. Dry-Run Tenant Migrations

All tenants:

```bash
php artisan tenants:migrate --pretend --force
```

Result:

```text
Success: 7db4e956-56fc-4c85-9ffb-28b6e09b1def
Success: e53f790d-7c1f-4691-9668-221f14c1b562
Success: a7d2c4f8-5b91-4e3d-8f76-19c2b5a4d8e7
```

Single tenant:

```bash
php artisan tenants:migrate --pretend --subdomain=cxfit --force
```

Result:

```text
Success: 7db4e956-56fc-4c85-9ffb-28b6e09b1def
```

Blank template:

```bash
docker exec accounts_mysql mysql -uroot -proot <<'SQL'
CREATE DATABASE IF NOT EXISTS `_blank` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON `_blank`.* TO 'laravel'@'%';
FLUSH PRIVILEGES;
SQL

php artisan tenants:migrate --pretend --include-blank --blank-database=_blank --database=_blank --force
```

Result:

```text
Success: _blank
```

### 7. Final Verification

Command registry:

```bash
php artisan list tenants --raw
php artisan list legacy --raw
```

Available tenant commands:

```text
tenants:migrate
tenants:prepare-central
tenants:split-once
```

Available legacy commands:

```text
legacy:delete-member-users
legacy:sync-attendance
legacy:sync-members
legacy:sync-payments
```

Database checks:

```bash
docker exec accounts_mysql mysql -uroot -proot -e "
SELECT COUNT(*) AS source_tenant_rows FROM accounts_prod_clone.tenants;
SELECT COUNT(*) AS portal_tenant_rows FROM portal.tenants;
SELECT COUNT(*) AS portal_users_tables
  FROM INFORMATION_SCHEMA.TABLES
 WHERE TABLE_SCHEMA='portal'
   AND TABLE_NAME='users';
SELECT subdomain, database_name FROM portal.tenants ORDER BY subdomain;
"
```

Table-count check:

```bash
docker exec accounts_mysql mysql -ularavel -plaravel -N -e "
SELECT TABLE_SCHEMA, COUNT(*) AS tables_count
  FROM INFORMATION_SCHEMA.TABLES
 WHERE TABLE_SCHEMA IN (
   'portal',
   '7db4e956-56fc-4c85-9ffb-28b6e09b1def',
   'e53f790d-7c1f-4691-9668-221f14c1b562',
   'a7d2c4f8-5b91-4e3d-8f76-19c2b5a4d8e7',
   '_blank',
   'accounts_prod_clone'
 )
 GROUP BY TABLE_SCHEMA
 ORDER BY TABLE_SCHEMA;
"
```

Tests:

```bash
php artisan test
```

Result:

```text
205 passed, 1092 assertions
```

## Production-Safe Sequence

Use this order for a production-dump rehearsal or production cutover planning.

### 1. Take Backup And Stop Writes

Before any split execution:

```text
Confirm restorable database backup exists.
Confirm queue workers and web writes are paused.
Confirm no user writes are reaching the source database.
```

### 2. Import Or Point To Source Database

For local rehearsal:

```bash
mysql -h 127.0.0.1 -P 3307 -u root -p \
  -e "DROP DATABASE IF EXISTS accounts_prod_clone; CREATE DATABASE accounts_prod_clone CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

mysql -h 127.0.0.1 -P 3307 -u root -p accounts_prod_clone < production_dump.sql
```

### 3. Run Split Preflight

```bash
DB_CONNECTION=mysql \
DB_HOST=<host> \
DB_PORT=<port> \
DB_DATABASE=<source_database> \
DB_USERNAME=<admin_user> \
DB_PASSWORD=<admin_password> \
CENTRAL_DB_DATABASE=portal \
TENANT_DATABASE_ISOLATION_ENABLED=false \
php artisan tenants:split-once --central=portal
```

Preflight must pass before continuing.

### 4. Execute Split

```bash
DB_CONNECTION=mysql \
DB_HOST=<host> \
DB_PORT=<port> \
DB_DATABASE=<source_database> \
DB_USERNAME=<admin_user> \
DB_PASSWORD=<admin_password> \
CENTRAL_DB_DATABASE=portal \
TENANT_DATABASE_ISOLATION_ENABLED=false \
php artisan tenants:split-once \
  --central=portal \
  --execute \
  --backup-confirmed \
  --maintenance-confirmed \
  --force
```

Do not use `--delete-source` in production cutover until the new runtime has been verified and rollback is no longer needed.

### 5. Switch Runtime Environment

Production runtime values:

```env
DB_CONNECTION=central
DB_DATABASE=portal
CENTRAL_DB_DATABASE=portal
TENANT_DATABASE_ISOLATION_ENABLED=true
MULTITENANCY_ENABLED=true

CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database
DB_CACHE_CONNECTION=central
DB_QUEUE_CONNECTION=central
SESSION_CONNECTION=central
SESSION_DOMAIN=null
TENANT_BLANK_DATABASE=_blank
```

Local development bypass values:

```env
MULTITENANCY_ENABLED=false
MULTITENANCY_BYPASS_DOMAIN=cxfit
```

Clear config:

```bash
php artisan config:clear
```

### 6. Prepare Portal Infrastructure

```bash
php artisan tenants:prepare-central --force
```

Expected central tables:

```text
tenants
cache
cache_locks
jobs
job_batches
failed_jobs
sessions
```

### 7. Verify Tenant Registry

```bash
php artisan tinker --execute="dump(DB::connection('central')->table('tenants')->orderBy('subdomain')->get())"
```

Each row must have:

```text
subdomain
database_name
```

`database_name` must be a UUID.

### 8. Dry-Run Tenant Migrations

```bash
php artisan tenants:migrate --pretend --force
php artisan tenants:migrate --pretend --subdomain=cxfit --force
php artisan tenants:migrate --pretend --include-blank --blank-database=_blank --force
```

### 9. Real Tenant Migrations

Blocked for now.

Only run after the app no longer depends on tenant-owned `tenant_id` columns:

```bash
php artisan tenants:migrate --force
php artisan tenants:migrate --include-blank --blank-database=_blank --force
```

### 10. Runtime Smoke Test

Open dashboard after login:

```text
http://localhost:8001/dashboard
```

Expected database access:

```text
portal.sessions
tenant_uuid_database.users
portal.sessions
```

The app must not query:

```text
portal.users
```
