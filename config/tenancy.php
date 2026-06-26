<?php

return [
    'database_isolation_enabled' => env('TENANT_DATABASE_ISOLATION_ENABLED', false),
    'central_connection' => env('CENTRAL_DB_CONNECTION', 'central'),
    'tenant_connection' => env('TENANT_DB_CONNECTION', 'tenant'),
    'tenant_migrations_path' => env('TENANT_MIGRATIONS_PATH', 'database/migrations/tenant'),
    'blank_database' => env('TENANT_BLANK_DATABASE', '_blank'),
];
