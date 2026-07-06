<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->string('color', 30)->default('slate')->after('is_active');
            $table->string('icon', 50)->default('CreditCard')->after('color');
            $table->integer('order')->default(0)->after('icon');
        });
    }

    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn(['color', 'icon', 'order']);
        });
    }
};
