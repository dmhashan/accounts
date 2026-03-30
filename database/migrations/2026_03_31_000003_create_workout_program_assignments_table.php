<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_program_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('source_program_id')->constrained('workout_programs')->restrictOnDelete();
            $table->foreignId('assigned_program_id')->constrained('workout_programs')->restrictOnDelete();
            $table->date('effective_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'effective_date'], 'workout_assignments_tenant_effective_idx');
            $table->index(['tenant_id', 'member_id'], 'workout_assignments_tenant_member_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_program_assignments');
    }
};
