<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_items', 'unit_cost')) {
                $table->decimal('unit_cost', 14, 4)->nullable()->after('unit_price');
            }

            if (!Schema::hasColumn('sale_items', 'cost_total')) {
                $table->decimal('cost_total', 14, 2)->nullable()->after('subtotal');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (Schema::hasColumn('sale_items', 'cost_total')) {
                $table->dropColumn('cost_total');
            }

            if (Schema::hasColumn('sale_items', 'unit_cost')) {
                $table->dropColumn('unit_cost');
            }
        });
    }
};
