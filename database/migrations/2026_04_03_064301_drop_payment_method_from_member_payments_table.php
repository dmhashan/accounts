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
        if (!Schema::hasTable('member_payments') || !Schema::hasColumn('member_payments', 'payment_method')) {
            return;
        }

        Schema::table('member_payments', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('member_payments', function (Blueprint $table) {
            $table->string('payment_method')->default('cash')->after('payment_date');
        });
    }
};
