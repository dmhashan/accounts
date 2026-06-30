<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_body_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->decimal('weight', 8, 2);
            $table->decimal('height', 8, 2);
            $table->date('measurement_date');
            $table->json('measurements')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['member_id', 'measurement_date'], 'mbm_member_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_body_measurements');
    }
};
