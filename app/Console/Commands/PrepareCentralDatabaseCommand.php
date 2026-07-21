<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PrepareCentralDatabaseCommand extends Command
{
    protected $signature = 'tenants:prepare-central {--force : Required in production}';

    protected $description = 'Create the central session, cache, and queue infrastructure tables';

    public function handle(): int
    {
        if (app()->environment('production') && !$this->option('force')) {
            $this->error('Production execution requires --force.');

            return self::FAILURE;
        }

        $schema = Schema::connection((string) config('tenancy.central_connection', 'central'));

        if (!$schema->hasTable('tenants')) {
            throw new \RuntimeException('The central tenants registry does not exist.');
        }

        $this->createCacheTables($schema);
        $this->createQueueTables($schema);
        $this->createSessionsTable($schema);

        // Run central portal migrations
        $this->info('Running central portal migrations...');
        \Illuminate\Support\Facades\Artisan::call('migrate', [
            '--database' => (string) config('tenancy.central_connection', 'central'),
            '--path' => 'database/migrations/portal',
            '--force' => true,
        ]);
        $this->line(\Illuminate\Support\Facades\Artisan::output());

        foreach (['tenants', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'sessions', 'portal_users', 'tenant_operation_jobs'] as $table) {
            if (!$schema->hasTable($table)) {
                throw new \RuntimeException("Central table [{$table}] was not created.");
            }
        }

        $this->info('Central database infrastructure is ready.');

        return self::SUCCESS;
    }

    private function createCacheTables($schema): void
    {
        if (!$schema->hasTable('cache')) {
            $schema->create('cache', function (Blueprint $table): void {
                $table->string('key')->primary();
                $table->mediumText('value');
                $table->integer('expiration');
            });
        }

        if (!$schema->hasTable('cache_locks')) {
            $schema->create('cache_locks', function (Blueprint $table): void {
                $table->string('key')->primary();
                $table->string('owner');
                $table->integer('expiration');
            });
        }
    }

    private function createQueueTables($schema): void
    {
        if (!$schema->hasTable('jobs')) {
            $schema->create('jobs', function (Blueprint $table): void {
                $table->id();
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }

        if (!$schema->hasTable('job_batches')) {
            $schema->create('job_batches', function (Blueprint $table): void {
                $table->string('id')->primary();
                $table->string('name');
                $table->integer('total_jobs');
                $table->integer('pending_jobs');
                $table->integer('failed_jobs');
                $table->longText('failed_job_ids');
                $table->mediumText('options')->nullable();
                $table->integer('cancelled_at')->nullable();
                $table->integer('created_at');
                $table->integer('finished_at')->nullable();
            });
        }

        if (!$schema->hasTable('failed_jobs')) {
            $schema->create('failed_jobs', function (Blueprint $table): void {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        }
    }

    private function createSessionsTable($schema): void
    {
        if ($schema->hasTable('sessions')) {
            return;
        }

        $schema->create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }
}
