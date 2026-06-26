<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columnsToDrop = array_values(array_filter([
            'provider',
            'provider_id',
            'provider_token',
            'role',
            'api_token',
            'business_name',
            'phone',
            'avatar_url',
        ], static fn (string $column) => Schema::hasColumn('users', $column)));

        if ($columnsToDrop === []) {
            return;
        }

        Schema::table('users', function (Blueprint $table) use ($columnsToDrop): void {
            if (in_array('api_token', $columnsToDrop, true)) {
                $table->dropUnique('users_api_token_unique');
            }

            if (
                in_array('provider', $columnsToDrop, true)
                || in_array('provider_id', $columnsToDrop, true)
                || in_array('provider_token', $columnsToDrop, true)
            ) {
                $table->dropUnique('users_tenant_provider_unique');
            }

            $table->dropColumn($columnsToDrop);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (!Schema::hasColumn('users', 'provider')) {
                $table->string('provider')->nullable()->after('email');
            }

            if (!Schema::hasColumn('users', 'provider_id')) {
                $table->string('provider_id')->nullable()->after('provider');
            }

            if (!Schema::hasColumn('users', 'provider_token')) {
                $table->text('provider_token')->nullable()->after('provider_id');
            }

            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('tourist')->after('provider_token');
            }

            if (!Schema::hasColumn('users', 'api_token')) {
                $table->string('api_token', 80)->nullable()->after('role');
            }

            if (!Schema::hasColumn('users', 'business_name')) {
                $table->string('business_name')->nullable()->after('api_token');
            }

            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 30)->nullable()->after('business_name');
            }

            if (!Schema::hasColumn('users', 'avatar_url')) {
                $table->string('avatar_url')->nullable()->after('phone');
            }

            if (Schema::hasColumn('users', 'provider') || Schema::hasColumn('users', 'provider_id')) {
                $table->unique(['tenant_id', 'provider_id'], 'users_tenant_provider_id_unique');
            }

            if (Schema::hasColumn('users', 'api_token')) {
                $table->unique('api_token', 'users_api_token_unique');
            }
        });
    }
};
