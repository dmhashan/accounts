<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('members') || !Schema::hasTable('wallets') || !Schema::hasTable('transactions')) {
            return;
        }

        if (!Schema::hasColumn('members', 'current_balance')) {
            DB::table('members')->orderBy('id')->chunkById(200, function ($members) {
                foreach ($members as $member) {
                    DB::table('wallets')->updateOrInsert(
                        ['member_id' => $member->id],
                        [
                            'tenant_id' => $member->tenant_id,
                            'current_balance' => 0,
                            'status' => 'active',
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }
            });

            return;
        }

        DB::table('members')->orderBy('id')->chunkById(200, function ($members) {
            foreach ($members as $member) {
                $balance = round((float) ($member->current_balance ?? 0), 2);
                $wallet = DB::table('wallets')->where('member_id', $member->id)->first();

                if (!$wallet) {
                    $walletId = DB::table('wallets')->insertGetId([
                        'tenant_id' => $member->tenant_id,
                        'member_id' => $member->id,
                        'current_balance' => $balance,
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $walletId = $wallet->id;
                    DB::table('wallets')
                        ->where('id', $walletId)
                        ->update([
                            'current_balance' => $balance,
                            'updated_at' => now(),
                        ]);
                }

                if ($balance == 0.0) {
                    continue;
                }

                $alreadySeeded = DB::table('transactions')
                    ->where('transaction_reference_type', 'wallet')
                    ->where('reference_id', $walletId)
                    ->where('description', 'Opening wallet balance migration')
                    ->exists();

                if ($alreadySeeded) {
                    continue;
                }

                DB::table('transactions')->insert([
                    'tenant_id' => $member->tenant_id,
                    'transaction_reference_type' => 'wallet',
                    'reference_id' => $walletId,
                    'amount' => abs($balance),
                    'transaction_type' => $balance >= 0 ? 'credit' : 'debit',
                    'balance_before' => 0,
                    'balance_after' => $balance,
                    'description' => 'Opening wallet balance migration',
                    'status' => 'completed',
                    'transaction_date' => $member->created_at ?? now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        // No-op: this migration backfills runtime data.
    }
};
