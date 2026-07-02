<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_account_id')->constrained('company_accounts')->restrictOnDelete();
            $table->string('name');
            $table->string('deduction_type', 20)->default('none');
            $table->decimal('deduction_value', 14, 4)->nullable();
            $table->boolean('record_deduction_as_expense')->default(true);
            $table->boolean('requires_reconciliation')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('name');
            $table->index(['company_account_id', 'is_active']);
            $table->index('deduction_type');
        });

        Schema::create('payment_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_method_id')->constrained('payment_methods')->restrictOnDelete();
            $table->foreignId('company_account_id')->constrained('company_accounts')->restrictOnDelete();
            $table->string('source_type', 30);
            $table->unsignedBigInteger('source_id');
            $table->string('payment_method_name');
            $table->decimal('gross_amount', 14, 2);
            $table->decimal('deduction_amount', 14, 2)->default(0);
            $table->decimal('net_amount', 14, 2);
            $table->boolean('record_deduction_as_expense')->default(true);
            $table->string('status', 20)->default('pending');
            $table->date('payment_date');
            $table->date('confirmed_transaction_date')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference_number')->nullable();
            $table->string('confirmation_reference')->nullable();
            $table->text('notes')->nullable();
            $table->text('confirmation_notes')->nullable();
            $table->timestamps();

            $table->unique(['source_type', 'source_id'], 'payment_settlements_source_unique');
            $table->index(['company_account_id', 'status']);
            $table->index(['status', 'payment_date']);
        });

        Schema::table('member_payments', function (Blueprint $table) {
            $table->foreignId('payment_method_id')
                ->nullable()
                ->after('company_account_id')
                ->constrained('payment_methods')
                ->nullOnDelete();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('payment_method_id')
                ->nullable()
                ->after('account_id')
                ->constrained('payment_methods')
                ->nullOnDelete();
        });

        $this->seedDefaultMethodsFromAccounts();
        $this->backfillExistingPaymentMethodLinks();
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_method_id');
        });

        Schema::table('member_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_method_id');
        });

        Schema::dropIfExists('payment_settlements');
        Schema::dropIfExists('payment_methods');
    }

    private function seedDefaultMethodsFromAccounts(): void
    {
        if (!Schema::hasTable('company_accounts')) {
            return;
        }

        $now = now();
        $accounts = DB::table('company_accounts')->select('id', 'name')->orderBy('id')->get();

        foreach ($accounts as $account) {
            DB::table('payment_methods')->updateOrInsert(
                ['name' => $account->name],
                [
                    'company_account_id' => $account->id,
                    'deduction_type' => 'none',
                    'deduction_value' => null,
                    'record_deduction_as_expense' => true,
                    'requires_reconciliation' => false,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }

    private function backfillExistingPaymentMethodLinks(): void
    {
        $methodIdsByAccount = DB::table('payment_methods')
            ->pluck('id', 'company_account_id');

        foreach ($methodIdsByAccount as $accountId => $methodId) {
            DB::table('member_payments')
                ->where('company_account_id', $accountId)
                ->whereNull('payment_method_id')
                ->update(['payment_method_id' => $methodId]);

            DB::table('sales')
                ->where('account_id', $accountId)
                ->whereNull('payment_method_id')
                ->update(['payment_method_id' => $methodId]);
        }
    }
};
