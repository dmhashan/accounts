<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('muscle_group');
            $table->enum('category', ['compound', 'isolation']);
            $table->string('equipment')->nullable();
            $table->enum('difficulty', ['beginner', 'intermediate', 'advanced']);
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
            $table->index(['tenant_id', 'muscle_group'], 'exercises_tenant_muscle_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
};
