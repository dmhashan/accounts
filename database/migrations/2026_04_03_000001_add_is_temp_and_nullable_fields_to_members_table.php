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
        Schema::table('members', function (Blueprint $table) {
            $table->boolean('is_temp')->default(false)->after('is_verified');

            // Make previously required columns nullable so temp members can skip them
            $table->string('name')->nullable()->change();
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->change();
            $table->string('email')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('is_temp');

            $table->string('name')->nullable(false)->change();
            $table->enum('gender', ['male', 'female', 'other'])->nullable(false)->change();
            $table->string('email')->nullable(false)->change();
        });
    }
};
