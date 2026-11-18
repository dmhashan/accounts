<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('track_stock')->default(true);
            $table->boolean('is_active')->default(true);
        });

        Schema::table('product_variations', function (Blueprint $table) {
            $table->decimal('untracked_local_price', 10, 2)->nullable();
            $table->decimal('untracked_foreign_price', 10, 2)->nullable();
            $table->decimal('untracked_unit_cost', 14, 4)->nullable();
        });

        Schema::table('sale_items', function (Blueprint $table) {
            // Null identifies legacy items; an empty array means no stock was consumed.
            $table->json('stock_allocations')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', fn (Blueprint $table) => $table->dropColumn('stock_allocations'));
        Schema::table('product_variations', fn (Blueprint $table) => $table->dropColumn(['untracked_local_price', 'untracked_foreign_price', 'untracked_unit_cost']));
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn(['track_stock', 'is_active']));
    }
};
