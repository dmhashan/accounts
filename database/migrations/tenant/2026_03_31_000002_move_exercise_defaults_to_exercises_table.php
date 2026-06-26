<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('exercises')) {
            Schema::table('exercises', function (Blueprint $table) {
                if (!Schema::hasColumn('exercises', 'default_sets')) {
                    $table->unsignedTinyInteger('default_sets')->default(1)->after('status');
                }

                if (!Schema::hasColumn('exercises', 'default_reps')) {
                    $table->string('default_reps', 100)->default('10')->after('default_sets');
                }

                if (!Schema::hasColumn('exercises', 'default_tempo')) {
                    $table->string('default_tempo', 100)->default('3-1-1-0')->after('default_reps');
                }

                if (!Schema::hasColumn('exercises', 'default_rest')) {
                    $table->unsignedInteger('default_rest')->default(0)->after('default_tempo');
                }
            });
        }

        if (Schema::hasTable('exercise_variations') && Schema::hasTable('exercises')) {
            $exerciseIds = DB::table('exercises')->pluck('id');

            foreach ($exerciseIds as $exerciseId) {
                $firstVariation = DB::table('exercise_variations')
                    ->where('exercise_id', $exerciseId)
                    ->orderBy('id')
                    ->first();

                if (!$firstVariation) {
                    continue;
                }

                DB::table('exercises')
                    ->where('id', $exerciseId)
                    ->update([
                        'default_sets' => (int) ($firstVariation->default_sets ?? 1),
                        'default_reps' => (string) ($firstVariation->default_reps ?? '10'),
                        'default_tempo' => (string) ($firstVariation->default_tempo ?? '3-1-1-0'),
                        'default_rest' => (int) ($firstVariation->default_rest ?? 0),
                    ]);
            }

            Schema::table('exercise_variations', function (Blueprint $table) {
                if (Schema::hasColumn('exercise_variations', 'status')) {
                    $table->dropColumn('status');
                }

                if (Schema::hasColumn('exercise_variations', 'default_sets')) {
                    $table->dropColumn('default_sets');
                }

                if (Schema::hasColumn('exercise_variations', 'default_reps')) {
                    $table->dropColumn('default_reps');
                }

                if (Schema::hasColumn('exercise_variations', 'default_tempo')) {
                    $table->dropColumn('default_tempo');
                }

                if (Schema::hasColumn('exercise_variations', 'default_rest')) {
                    $table->dropColumn('default_rest');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('exercise_variations')) {
            Schema::table('exercise_variations', function (Blueprint $table) {
                if (!Schema::hasColumn('exercise_variations', 'status')) {
                    $table->enum('status', ['active', 'inactive'])->default('active')->after('variation_name');
                }

                if (!Schema::hasColumn('exercise_variations', 'default_sets')) {
                    $table->unsignedTinyInteger('default_sets')->default(1)->after('status');
                }

                if (!Schema::hasColumn('exercise_variations', 'default_reps')) {
                    $table->string('default_reps', 100)->default('10')->after('default_sets');
                }

                if (!Schema::hasColumn('exercise_variations', 'default_tempo')) {
                    $table->string('default_tempo', 100)->default('3-1-1-0')->after('default_reps');
                }

                if (!Schema::hasColumn('exercise_variations', 'default_rest')) {
                    $table->unsignedInteger('default_rest')->default(0)->after('default_tempo');
                }
            });
        }

        if (Schema::hasTable('exercise_variations') && Schema::hasTable('exercises')) {
            $exerciseDefaults = DB::table('exercises')
                ->select('id', 'default_sets', 'default_reps', 'default_tempo', 'default_rest')
                ->get()
                ->keyBy('id');

            DB::table('exercise_variations')
                ->select('id', 'exercise_id')
                ->orderBy('id')
                ->chunkById(200, function ($variations) use ($exerciseDefaults) {
                    foreach ($variations as $variation) {
                        $defaults = $exerciseDefaults->get($variation->exercise_id);

                        if (!$defaults) {
                            continue;
                        }

                        DB::table('exercise_variations')
                            ->where('id', $variation->id)
                            ->update([
                                'status' => 'active',
                                'default_sets' => (int) ($defaults->default_sets ?? 1),
                                'default_reps' => (string) ($defaults->default_reps ?? '10'),
                                'default_tempo' => (string) ($defaults->default_tempo ?? '3-1-1-0'),
                                'default_rest' => (int) ($defaults->default_rest ?? 0),
                            ]);
                    }
                });
        }

        if (Schema::hasTable('exercises')) {
            Schema::table('exercises', function (Blueprint $table) {
                if (Schema::hasColumn('exercises', 'default_sets')) {
                    $table->dropColumn('default_sets');
                }

                if (Schema::hasColumn('exercises', 'default_reps')) {
                    $table->dropColumn('default_reps');
                }

                if (Schema::hasColumn('exercises', 'default_tempo')) {
                    $table->dropColumn('default_tempo');
                }

                if (Schema::hasColumn('exercises', 'default_rest')) {
                    $table->dropColumn('default_rest');
                }
            });
        }
    }
};
