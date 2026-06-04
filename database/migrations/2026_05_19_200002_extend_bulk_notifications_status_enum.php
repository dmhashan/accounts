<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return; // SQLite/Postgres do not support MySQL ENUM ALTER syntax
        }

        DB::statement(
            "ALTER TABLE bulk_notifications MODIFY COLUMN status
             ENUM('draft','processing','sent','failed') NOT NULL DEFAULT 'draft'",
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE bulk_notifications MODIFY COLUMN status
             ENUM('draft','sent') NOT NULL DEFAULT 'draft'",
        );
    }
};
