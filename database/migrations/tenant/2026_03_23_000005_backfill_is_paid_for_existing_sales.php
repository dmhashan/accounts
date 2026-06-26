<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('sales')->whereNull('deleted_at')->update(['is_paid' => true]);
    }

    public function down(): void
    {
        // Cannot safely reverse — we don't know which rows were originally unpaid.
    }
};
