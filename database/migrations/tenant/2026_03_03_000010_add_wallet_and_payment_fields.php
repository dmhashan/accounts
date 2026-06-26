<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->decimal('current_balance', 10, 2)->default(0)->after('price');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('customer_member_id')->nullable()->after('customer_name')->constrained('members')->nullOnDelete();
            $table->string('payment_method')->default('cash')->after('customer_type');
            $table->string('reference_number')->nullable()->after('payment_method');
            $table->index(['tenant_id', 'payment_method']);
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'payment_method']);
            $table->dropConstrainedForeignId('customer_member_id');
            $table->dropColumn(['payment_method', 'reference_number']);
        });

        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('current_balance');
        });
    }
};
