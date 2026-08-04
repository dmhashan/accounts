<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('tenant_configurations')) {
            return;
        }

        // 1. Fetch existing biometric_member_id values from members table
        $memberIds = [];

        if (Schema::hasTable('members')) {
            $memberIds = DB::table('members')
                ->whereNotNull('biometric_member_id')
                ->where('biometric_member_id', '!=', '')
                ->pluck('biometric_member_id')
                ->all();
        }

        $detectedPrefix = '';
        $maxSeq = 0;
        $detectedPadding = 4;
        $prefixesCount = [];
        $paddings = [];

        foreach ($memberIds as $rawId) {
            $strId = trim((string) $rawId);

            if (preg_match('/^(.*?)([0-9]+)$/', $strId, $matches)) {
                $prefix = $matches[1];
                $numStr = $matches[2];
                $numVal = (int) $numStr;

                if ($prefix !== '') {
                    $prefixesCount[$prefix] = ($prefixesCount[$prefix] ?? 0) + 1;
                }

                $maxSeq = max($maxSeq, $numVal);

                if (strlen($numStr) > 1 && str_starts_with($numStr, '0')) {
                    $paddings[] = strlen($numStr);
                }
            }
        }

        if (!empty($prefixesCount)) {
            arsort($prefixesCount);
            $detectedPrefix = (string) key($prefixesCount);
        }

        if (!empty($paddings)) {
            $paddingCounts = array_count_values($paddings);
            arsort($paddingCounts);
            $detectedPadding = (int) key($paddingCounts);
        }

        $nextNumber = max(1, $maxSeq + 1);

        $defaultConfigs = [
            'member.id_prefix' => ['Member ID Prefix', $detectedPrefix],
            'member.id_next_number' => ['Member ID Next Number', (string) $nextNumber],
            'member.id_padding' => ['Member ID Zero Padding', (string) $detectedPadding],
            'member.id_auto_generate' => ['Auto Generate Member ID', '1'],
            'biometric.id_prefix' => ['Biometric ID Prefix', ''],
            'biometric.id_next_number' => ['Biometric ID Next Number', (string) $nextNumber],
            'biometric.id_padding' => ['Biometric ID Zero Padding', (string) $detectedPadding],
            'biometric.id_same_as_member_id' => ['Biometric ID Same as Member ID', '1'],
        ];

        $now = now();

        foreach ($defaultConfigs as $key => [$title, $defaultValue]) {
            $existing = DB::table('tenant_configurations')->where('key', $key)->first();

            if (!$existing) {
                DB::table('tenant_configurations')->insert([
                    'key' => $key,
                    'title' => $title,
                    'value' => $defaultValue,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } elseif (in_array($key, ['member.id_prefix', 'member.id_next_number', 'member.id_padding', 'biometric.id_next_number', 'biometric.id_padding'], true)) {
                DB::table('tenant_configurations')->where('key', $key)->update([
                    'value' => $defaultValue,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tenant_configurations')) {
            DB::table('tenant_configurations')->whereIn('key', [
                'member.id_prefix',
                'member.id_next_number',
                'member.id_padding',
                'member.id_auto_generate',
                'biometric.id_prefix',
                'biometric.id_next_number',
                'biometric.id_padding',
                'biometric.id_same_as_member_id',
            ])->delete();
        }
    }
};
