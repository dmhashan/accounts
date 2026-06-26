<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('members', 'payment_plan')) {
            return; // already migrated
        }

        // Step 1: Null out all FK references so we can safely truncate payment_plans
        DB::table('members')->update(['payment_plan_id' => null]);

        // Step 2: Truncate payment_plans (disable FK checks for MySQL)
        $isMysql = DB::getDriverName() === 'mysql';

        if ($isMysql) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }
        DB::table('payment_plans')->truncate();

        if ($isMysql) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        // Step 3: Build one PaymentPlan row per unique (tenant_id, lowercase plan name)
        //         Use the first seen price for that plan; default to 0 when missing.
        $now = now();
        $planMap = []; // [tenantId => [lowerName => planId]]

        $rows = DB::table('members')
            ->whereNotNull('payment_plan')
            ->where('payment_plan', '!=', '')
            ->select('tenant_id', 'payment_plan', 'price')
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            $name = trim($row->payment_plan);
            $lowerName = strtolower($name);

            if ($name === '') {
                continue;
            }

            if (isset($planMap[$row->tenant_id][$lowerName])) {
                continue; // already created for this tenant
            }

            $planId = DB::table('payment_plans')->insertGetId([
                'tenant_id' => $row->tenant_id,
                'name' => $name,
                'duration_days' => 30,
                'price' => is_numeric($row->price) ? (float) $row->price : 0,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $planMap[$row->tenant_id][$lowerName] = $planId;
        }

        // Step 4: Back-fill payment_plan_id on every member that has a plan name
        $members = DB::table('members')
            ->whereNotNull('payment_plan')
            ->where('payment_plan', '!=', '')
            ->select('id', 'tenant_id', 'payment_plan')
            ->get();

        foreach ($members as $member) {
            $lowerName = strtolower(trim($member->payment_plan));
            $planId = $planMap[$member->tenant_id][$lowerName] ?? null;

            if ($planId !== null) {
                DB::table('members')
                    ->where('id', $member->id)
                    ->update(['payment_plan_id' => $planId]);
            }
        }

        // Step 5: Drop the legacy text column
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('payment_plan');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('payment_plan')->nullable()->after('admission_fee');
        });

        // Restore plan names from payment_plan_id FK
        DB::table('members')
            ->whereNotNull('payment_plan_id')
            ->join('payment_plans', 'members.payment_plan_id', '=', 'payment_plans.id')
            ->select('members.id', 'payment_plans.name')
            ->orderBy('members.id')
            ->each(function ($row) {
                DB::table('members')
                    ->where('id', $row->id)
                    ->update(['payment_plan' => $row->name]);
            });
    }
};
