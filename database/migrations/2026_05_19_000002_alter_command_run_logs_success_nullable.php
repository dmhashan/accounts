<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('command_run_logs', function (Blueprint $table) {
            $table->boolean('success')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('command_run_logs', function (Blueprint $table) {
            $table->boolean('success')->nullable(false)->default(false)->change();
        });
    }
};
