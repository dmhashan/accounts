<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE bulk_notifications MODIFY COLUMN status
                 ENUM('draft','processing','sent','failed') NOT NULL DEFAULT 'draft'",
            );

            return;
        }

        Schema::table('bulk_notifications', function (Blueprint $table) {
            $table->enum('status', ['draft', 'processing', 'sent', 'failed'])
                ->default('draft')
                ->change();
        });
    }

    public function down(): void
    {
        DB::table('bulk_notifications')
            ->whereIn('status', ['processing', 'failed'])
            ->update(['status' => 'draft']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE bulk_notifications MODIFY COLUMN status
                 ENUM('draft','sent') NOT NULL DEFAULT 'draft'",
            );

            return;
        }

        Schema::table('bulk_notifications', function (Blueprint $table) {
            $table->enum('status', ['draft', 'sent'])
                ->default('draft')
                ->change();
        });
    }
};
