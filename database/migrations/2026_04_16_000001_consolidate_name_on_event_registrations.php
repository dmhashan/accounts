<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── event_registrations ─────────────────────────────────────
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->string('name', 200)->nullable()->after('member_id');
        });

        DB::statement("UPDATE event_registrations SET name = TRIM(CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')))");

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->string('name', 200)->nullable(false)->change();
            $table->dropColumn(['first_name', 'last_name']);
        });

        // ── event_registration_guests ───────────────────────────────
        Schema::table('event_registration_guests', function (Blueprint $table) {
            $table->string('name', 200)->nullable()->after('event_registration_id');
        });

        DB::statement("UPDATE event_registration_guests SET name = TRIM(CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')))");

        Schema::table('event_registration_guests', function (Blueprint $table) {
            $table->string('name', 200)->nullable(false)->change();
            $table->dropColumn(['first_name', 'last_name']);
        });
    }

    public function down(): void
    {
        // ── event_registrations ─────────────────────────────────────
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->string('first_name', 100)->nullable()->after('member_id');
            $table->string('last_name', 100)->nullable()->after('first_name');
        });

        DB::statement("UPDATE event_registrations SET first_name = SUBSTRING_INDEX(name, ' ', 1), last_name = TRIM(SUBSTR(name, LOCATE(' ', name)))");

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropColumn('name');
        });

        // ── event_registration_guests ───────────────────────────────
        Schema::table('event_registration_guests', function (Blueprint $table) {
            $table->string('first_name', 100)->nullable()->after('event_registration_id');
            $table->string('last_name', 100)->nullable()->after('first_name');
        });

        DB::statement("UPDATE event_registration_guests SET first_name = SUBSTRING_INDEX(name, ' ', 1), last_name = TRIM(SUBSTR(name, LOCATE(' ', name)))");

        Schema::table('event_registration_guests', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};
