<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->boolean('allow_sms')->default(true)->after('phone_number');
            $table->boolean('allow_whatsapp')->default(true)->after('allow_sms');
            $table->string('whatsapp_number')->nullable()->after('allow_whatsapp');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['allow_sms', 'allow_whatsapp', 'whatsapp_number']);
        });
    }
};
