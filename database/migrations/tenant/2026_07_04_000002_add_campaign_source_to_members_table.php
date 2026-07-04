<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->foreignId('campaign_id')->nullable()->after('user_id')->constrained('campaigns')->nullOnDelete();
            $table->string('registration_source', 50)->nullable()->after('campaign_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
            $table->dropColumn(['campaign_id', 'registration_source']);
        });
    }
};
