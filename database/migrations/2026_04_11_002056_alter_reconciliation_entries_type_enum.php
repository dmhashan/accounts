<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE reconciliation_entries MODIFY COLUMN `type` ENUM('account','stock','stock_variation','stock_display','stock_variation_display') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE reconciliation_entries MODIFY COLUMN `type` ENUM('account','stock') NOT NULL");
    }
};
