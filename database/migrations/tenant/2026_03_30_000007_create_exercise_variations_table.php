<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('exercise_variations')) {
            Schema::create('exercise_variations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('exercise_id')->constrained('exercises')->cascadeOnDelete();
                $table->string('variation_name');
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->unsignedTinyInteger('default_sets')->default(1);
                $table->string('default_reps', 100);
                $table->string('default_tempo', 20);
                $table->unsignedInteger('default_rest')->default(0);
                $table->timestamps();

                $table->unique(['exercise_id', 'variation_name'], 'exercise_variations_unique_name');
            });
        }

        if (Schema::hasTable('exercises')) {
            if (Schema::hasColumn('exercises', 'muscle_group')) {
                // Drop dependent index first to keep SQLite schema rewrites valid.
                try {
                    Schema::table('exercises', function (Blueprint $table) {
                        $table->dropIndex('exercises_tenant_muscle_idx');
                    });
                } catch (Throwable $e) {
                    // Index might already be absent in some environments.
                }

                Schema::table('exercises', function (Blueprint $table) {
                    $table->dropColumn('muscle_group');
                });
            }

            if (Schema::hasColumn('exercises', 'category')) {
                Schema::table('exercises', function (Blueprint $table) {
                    $table->dropColumn('category');
                });
            }

            if (Schema::hasColumn('exercises', 'equipment')) {
                Schema::table('exercises', function (Blueprint $table) {
                    $table->dropColumn('equipment');
                });
            }

            if (Schema::hasColumn('exercises', 'difficulty')) {
                Schema::table('exercises', function (Blueprint $table) {
                    $table->dropColumn('difficulty');
                });
            }

            if (Schema::hasColumn('exercises', 'description')) {
                Schema::table('exercises', function (Blueprint $table) {
                    $table->dropColumn('description');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('exercise_variations')) {
            Schema::dropIfExists('exercise_variations');
        }

        if (Schema::hasTable('exercises')) {
            if (!Schema::hasColumn('exercises', 'muscle_group')) {
                Schema::table('exercises', function (Blueprint $table) {
                    $table->string('muscle_group')->nullable()->after('name');
                });

                try {
                    Schema::table('exercises', function (Blueprint $table) {
                        $table->index(['tenant_id', 'muscle_group'], 'exercises_tenant_muscle_idx');
                    });
                } catch (Throwable $e) {
                    // Ignore if index already exists.
                }
            }

            if (!Schema::hasColumn('exercises', 'category')) {
                Schema::table('exercises', function (Blueprint $table) {
                    $table->string('category')->nullable()->after('muscle_group');
                });
            }

            if (!Schema::hasColumn('exercises', 'equipment')) {
                Schema::table('exercises', function (Blueprint $table) {
                    $table->string('equipment')->nullable()->after('category');
                });
            }

            if (!Schema::hasColumn('exercises', 'difficulty')) {
                Schema::table('exercises', function (Blueprint $table) {
                    $table->string('difficulty')->nullable()->after('equipment');
                });
            }

            if (!Schema::hasColumn('exercises', 'description')) {
                Schema::table('exercises', function (Blueprint $table) {
                    $table->text('description')->nullable()->after('difficulty');
                });
            }
        }
    }
};
