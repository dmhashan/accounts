<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE bulk_notifications MODIFY COLUMN status
             ENUM('draft','processing','sent','failed') NOT NULL DEFAULT 'draft'",
        );
    }

    public function down(): void
    {
        DB::statement(
            "ALTER TABLE bulk_notifications MODIFY COLUMN status
             ENUM('draft','sent') NOT NULL DEFAULT 'draft'",
        );
    }
};
