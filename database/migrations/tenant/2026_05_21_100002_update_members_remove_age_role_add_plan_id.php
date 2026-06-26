<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['age', 'member_role']);
            $table->foreignId('payment_plan_id')->nullable()->after('admission_fee')
                ->constrained('payment_plans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['payment_plan_id']);
            $table->dropColumn('payment_plan_id');
            $table->unsignedTinyInteger('age')->nullable()->after('date_of_birth');
            $table->string('member_role')->nullable()->after('address');
        });
    }
};
