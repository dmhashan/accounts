<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_plans', function (Blueprint $table) {
            $table->unsignedSmallInteger('duration_value')->default(1)->after('name');
            $table->string('duration_unit', 8)->default('day')->after('duration_value');
        });

        // Backfill from legacy duration_days
        if (Schema::hasColumn('payment_plans', 'duration_days')) {
            DB::table('payment_plans')->orderBy('id')->chunkById(200, function ($plans) {
                foreach ($plans as $plan) {
                    [$value, $unit] = self::classify((int) $plan->duration_days);
                    DB::table('payment_plans')
                        ->where('id', $plan->id)
                        ->update(['duration_value' => $value, 'duration_unit' => $unit]);
                }
            });

            Schema::table('payment_plans', function (Blueprint $table) {
                $table->dropColumn('duration_days');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('payment_plans', 'duration_days')) {
            Schema::table('payment_plans', function (Blueprint $table) {
                $table->unsignedSmallInteger('duration_days')->default(30)->after('name');
            });
        }

        DB::table('payment_plans')->orderBy('id')->chunkById(200, function ($plans) {
            foreach ($plans as $plan) {
                $days = match ($plan->duration_unit ?? 'day') {
                    'year' => 365 * (int) $plan->duration_value,
                    'month' => 30 * (int) $plan->duration_value,
                    'week' => 7 * (int) $plan->duration_value,
                    default => (int) $plan->duration_value,
                };
                DB::table('payment_plans')->where('id', $plan->id)->update(['duration_days' => $days]);
            }
        });

        Schema::table('payment_plans', function (Blueprint $table) {
            $table->dropColumn(['duration_value', 'duration_unit']);
        });
    }

    private static function classify(int $days): array
    {
        return match (true) {
            $days <= 0 => [1, 'day'],
            $days === 1 => [1, 'day'],
            $days === 7 => [1, 'week'],
            $days === 14 => [2, 'week'],
            $days === 21 => [3, 'week'],
            $days === 28 => [4, 'week'],
            $days === 30 || $days === 31 => [1, 'month'],
            $days === 60 || $days === 61 => [2, 'month'],
            $days === 90 || $days === 91 || $days === 92 => [3, 'month'],
            $days === 180 || $days === 182 || $days === 183 => [6, 'month'],
            $days === 365 || $days === 366 => [1, 'year'],
            $days % 365 === 0 => [intdiv($days, 365), 'year'],
            $days % 30 === 0 => [intdiv($days, 30), 'month'],
            $days % 7 === 0 => [intdiv($days, 7), 'week'],
            default => [$days, 'day'],
        };
    }
};
