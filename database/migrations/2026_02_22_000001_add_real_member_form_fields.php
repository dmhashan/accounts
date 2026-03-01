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
        Schema::table('members', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('member_id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('username')->nullable()->after('last_name');
            $table->string('profile_photo_path')->nullable()->after('username');
            $table->unsignedTinyInteger('age')->nullable()->after('date_of_birth');
            $table->text('address')->nullable()->after('age');
            $table->string('member_role')->nullable()->after('address');
            $table->decimal('admission_fee', 10, 2)->nullable()->after('member_role');
            $table->string('payment_plan')->nullable()->after('admission_fee');
            $table->decimal('price', 10, 2)->nullable()->after('payment_plan');
            $table->date('joined_date')->nullable()->after('price');
            $table->unique(['tenant_id', 'username']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->after('email');
            $table->unique(['tenant_id', 'username']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'username']);
            $table->dropColumn('username');
        });

        Schema::table('members', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'username']);
            $table->dropColumn([
                'first_name',
                'last_name',
                'username',
                'profile_photo_path',
                'age',
                'address',
                'member_role',
                'admission_fee',
                'payment_plan',
                'price',
                'joined_date',
            ]);
        });
    }
};
