<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_payments', function (Blueprint $table) {
            $table->boolean('is_paid')->default(true)->after('notes');
            $table->decimal('paid_amount', 14, 2)->default(0.00)->after('is_paid');
            $table->decimal('balance', 14, 2)->default(0.00)->after('paid_amount');
        });

        // For existing payments, since they were already created, they are all paid.
        DB::table('member_payments')->update([
            'is_paid' => true,
            'paid_amount' => DB::raw('amount'),
            'balance' => 0.00,
        ]);
    }

    public function down(): void
    {
        Schema::table('member_payments', function (Blueprint $table) {
            $table->dropColumn(['is_paid', 'paid_amount', 'balance']);
        });
    }
};
