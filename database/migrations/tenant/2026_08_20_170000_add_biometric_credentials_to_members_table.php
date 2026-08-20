<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->boolean('has_face')->default(false)->after('biometric_last_synced_at');
            $table->boolean('has_fingerprint')->default(false)->after('has_face');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['has_face', 'has_fingerprint']);
        });
    }
};
