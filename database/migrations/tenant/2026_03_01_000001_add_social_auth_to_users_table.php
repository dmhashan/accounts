<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('social_provider')->nullable()->after('username');
            $table->string('social_provider_id')->nullable()->after('social_provider');
            $table->string('avatar')->nullable()->after('social_provider_id');
            $table->unique(['tenant_id', 'social_provider', 'social_provider_id'], 'users_tenant_provider_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_tenant_provider_unique');
            $table->dropColumn(['social_provider', 'social_provider_id', 'avatar']);
        });
    }
};
