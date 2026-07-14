<?php

namespace Tests\Feature\Api;

use App\Models\CompanyAccount;
use App\Models\MemberPayment;
use App\Models\PaymentMembership;
use App\Models\PaymentSettlement;
use App\Services\AutomatedMemberNotificationService;
use App\Services\BiometricSyncService;
use App\Services\TenantConfigurationService;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;

class PaymentsApiTest extends ApiRouteTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Disable biometric side-effects globally for these tests
        $mock = \Mockery::mock(BiometricSyncService::class);
        $mock->shouldReceive('syncMember')->andReturnNull();
        $mock->shouldReceive('isMemberSyncEnabled')->andReturnFalse();
        $this->app->instance(BiometricSyncService::class, $mock);
    }

    // ------------------------------------------------------------------
    // Plan CRUD with duration_value + duration_unit
    // ------------------------------------------------------------------

    public function testCreatePlanRequiresDurationValueAndUnit(): void
    {
        $this->actingAsUser(['payments.manage']);

        $this->postJson('/api/payment-plans', [
            'name' => 'Local Annual',
            'price' => 12000,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['duration_value', 'duration_unit']);
    }

    public function testCreatePlanRejectsInvalidUnit(): void
    {
        $this->actingAsUser(['payments.manage']);

        $this->postJson('/api/payment-plans', [
            'name' => 'Bad',
            'duration_value' => 1,
            'duration_unit' => 'fortnight',
            'price' => 100,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['duration_unit']);
    }

    #[DataProvider('realWorldPlansProvider')]
    public function testCreateAndStoreRealWorldPlans(string $name, int $value, string $unit, float $price): void
    {
        $this->actingAsUser(['payments.manage']);

        $response = $this->postJson('/api/payment-plans', [
            'name' => $name,
            'duration_value' => $value,
            'duration_unit' => $unit,
            'price' => $price,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', $name)
            ->assertJsonPath('data.duration_value', $value)
            ->assertJsonPath('data.duration_unit', $unit);
        $this->assertEquals((float) $price, (float) $response->json('data.price'));

        $this->assertDatabaseHas('payment_plans', [
            'name' => $name,
            'duration_value' => $value,
            'duration_unit' => $unit,
        ]);
    }

    public static function realWorldPlansProvider(): array
    {
        return [
            // Local
            'Local Annual' => ['Local Annual',   1, 'year',  12000.00],
            'Local Monthly' => ['Local Monthly',  1, 'month',  1000.00],
            'Local 3 Months' => ['Local 3 Months', 3, 'month',  3000.00],
            'Local 6 Months' => ['Local 6 Months', 6, 'month',  6000.00],
            'Local Couple' => ['Local Couple',   1, 'month',  1500.00],
            'Local Family Per Hd' => ['Local Family',   1, 'month',   750.00],
            // Foreign
            'Foreign Day Pass' => ['Foreign Day Pass', 1, 'day',   1000.00],
            'Foreign Weekly' => ['Foreign Weekly',   1, 'week',  2000.00],
            'Foreign 2 Weeks' => ['Foreign 2 Weeks',  2, 'week',  4000.00],
            'Foreign 3 Weeks' => ['Foreign 3 Weeks',  3, 'week',  6000.00],
            'Foreign Monthly' => ['Foreign Monthly',  1, 'month', 10000.00],
        ];
    }

    public function testUpdatePlanChangesDurationUnit(): void
    {
        $this->actingAsUser(['payments.manage']);
        $plan = $this->createPaymentPlan(['duration_value' => 1, 'duration_unit' => 'month']);

        $this->putJson('/api/payment-plans/' . $plan->id, [
            'name' => 'Now Yearly',
            'duration_value' => 1,
            'duration_unit' => 'year',
            'price' => 12000,
        ])->assertOk()
            ->assertJsonPath('data.duration_unit', 'year')
            ->assertJsonPath('data.duration_value', 1);
    }

    public function testIndexReturnsPlansSortedByApproximateDays(): void
    {
        $this->actingAsUser(['payments.manage']);

        $this->createPaymentPlan(['name' => 'Annual',   'duration_value' => 1, 'duration_unit' => 'year']);
        $this->createPaymentPlan(['name' => 'Monthly',  'duration_value' => 1, 'duration_unit' => 'month']);
        $this->createPaymentPlan(['name' => 'Day Pass', 'duration_value' => 1, 'duration_unit' => 'day']);
        $this->createPaymentPlan(['name' => 'Weekly',   'duration_value' => 1, 'duration_unit' => 'week']);

        $response = $this->getJson('/api/payment-plans')->assertOk();
        $names = collect($response->json('data'))->pluck('name')->all();

        $this->assertSame(['Day Pass', 'Weekly', 'Monthly', 'Annual'], $names);
    }

    // ------------------------------------------------------------------
    // End-date computation per unit
    // ------------------------------------------------------------------

    #[DataProvider('endDateProvider')]
    public function testRecordingMembershipPaymentComputesEndDate(int $value, string $unit, string $start, string $expectedEnd): void
    {
        $this->actingAsUser(['payments.manage']);
        $member = $this->createMember();
        $plan = $this->createPaymentPlan(['duration_value' => $value, 'duration_unit' => $unit, 'price' => 100]);
        $account = $this->createAccount();

        $this->postJson('/api/payments', [
            'member_id' => $member->id,
            'company_account_id' => $account->id,
            'payment_method' => 'cash',
            'payment_plan_id' => $plan->id,
            'amount' => 100,
            'payment_date' => $start,
            'start_date' => $start,
        ])->assertCreated();

        $membership = PaymentMembership::query()->where('payment_plan_id', $plan->id)->first();
        $this->assertNotNull($membership);
        $this->assertSame($start, $membership->start_date->toDateString());
        $this->assertSame($expectedEnd, $membership->end_date->toDateString());
    }

    public static function endDateProvider(): array
    {
        return [
            // Day pass (foreign 1000)
            'day pass' => [1, 'day', '2026-06-03', '2026-06-03'],
            // Weekly (foreign 2000)
            'weekly' => [1, 'week', '2026-06-03', '2026-06-09'],
            // 2 weeks
            '2 weeks' => [2, 'week', '2026-06-03', '2026-06-16'],
            // 3 weeks
            '3 weeks' => [3, 'week', '2026-06-03', '2026-06-23'],
            // Monthly — calendar-aware
            'monthly mid-month' => [1, 'month', '2026-01-15', '2026-02-14'],
            // Monthly across leap-year boundary (Jan 31 → end of Feb)
            'monthly Jan 31' => [1, 'month', '2024-01-31', '2024-02-28'],
            // 3 months
            '3 months' => [3, 'month', '2026-01-15', '2026-04-14'],
            // 6 months
            '6 months' => [6, 'month', '2026-01-15', '2026-07-14'],
            // Annual
            'annual' => [1, 'year', '2026-06-03', '2027-06-02'],
            // Annual leap day handling
            'annual Feb 29' => [1, 'year', '2024-02-29', '2025-02-27'],
        ];
    }

    // ------------------------------------------------------------------
    // Sequential payments produce contiguous memberships
    // ------------------------------------------------------------------

    public function testConsecutiveMonthlyPaymentsAreContiguous(): void
    {
        $this->actingAsUser(['payments.manage']);
        $member = $this->createMember();
        $plan = $this->createPaymentPlan(['duration_value' => 1, 'duration_unit' => 'month', 'price' => 1000]);
        $account = $this->createAccount();

        $startA = '2026-01-15';

        $this->postJson('/api/payments', [
            'member_id' => $member->id,
            'company_account_id' => $account->id,
            'payment_method' => 'cash',
            'payment_plan_id' => $plan->id,
            'amount' => 1000,
            'payment_date' => $startA,
            'start_date' => $startA,
        ])->assertCreated();

        // Pull next start date from API
        $info = $this->getJson('/api/payments/member/' . $member->id . '/payment-info')
            ->assertOk()
            ->json();

        $this->assertSame('2026-02-14', $info['last_payment']['end_date']);
        $this->assertSame('2026-02-15', $info['next_start_date']);

        // Record next payment starting on next_start_date
        $this->postJson('/api/payments', [
            'member_id' => $member->id,
            'company_account_id' => $account->id,
            'payment_method' => 'cash',
            'payment_plan_id' => $plan->id,
            'amount' => 1000,
            'payment_date' => $info['next_start_date'],
            'start_date' => $info['next_start_date'],
        ])->assertCreated();

        $memberships = PaymentMembership::query()->orderBy('start_date')->get();
        $this->assertCount(2, $memberships);
        $this->assertSame('2026-02-14', $memberships[0]->end_date->toDateString());
        $this->assertSame('2026-02-15', $memberships[1]->start_date->toDateString());
        $this->assertSame('2026-03-14', $memberships[1]->end_date->toDateString());
    }

    // ------------------------------------------------------------------
    // Biometric validity uses latest membership end_date + grace
    // ------------------------------------------------------------------

    public function testBiometricValidUntilUsesLatestMembershipPlusGrace(): void
    {
        $member = $this->createMember();
        $plan = $this->createPaymentPlan(['duration_value' => 1, 'duration_unit' => 'month', 'price' => 1000]);

        $payment = MemberPayment::create([
            'member_id' => $member->id,
            'company_account_id' => $this->createAccount()->id,
            'payment_method' => 'cash',
            'amount' => 1000,
            'payment_date' => '2026-01-15',
        ]);
        PaymentMembership::create([
            'member_payment_id' => $payment->id,
            'payment_plan_id' => $plan->id,
            'start_date' => '2026-01-15',
            'end_date' => $plan->endDateFrom(Carbon::parse('2026-01-15'))->toDateString(),
        ]);

        // Build the real BiometricSyncService and exercise the private
        // validity helper through its public payload builder.
        // Use a real BiometricSyncService for reflection — replace the global mock
        $this->app->forgetInstance(BiometricSyncService::class);
        $svc = $this->app->make(BiometricSyncService::class);

        $reflection = new \ReflectionClass($svc);
        $method = $reflection->getMethod('getMemberValidUntil');
        $method->setAccessible(true);

        $validUntil = $method->invoke($svc, $member->fresh(), 3);
        $this->assertNotNull($validUntil);
        // 1 month from Jan 15 → Feb 14 (inclusive); + 3 grace days → Feb 17 end-of-day
        $this->assertSame('2026-02-17', $validUntil->toDateString());
    }

    // ------------------------------------------------------------------
    // Wallet payment + membership flow
    // ------------------------------------------------------------------

    public function testWalletPaymentDeductsBalanceAndCreatesMembership(): void
    {
        $this->actingAsUser(['payments.manage']);
        $member = $this->createMember(null, ['current_balance' => 5000]);
        $plan = $this->createPaymentPlan(['duration_value' => 1, 'duration_unit' => 'month', 'price' => 1000]);

        $this->postJson('/api/payments', [
            'member_id' => $member->id,
            'company_account_id' => null,
            'payment_method' => 'member_wallet',
            'payment_plan_id' => $plan->id,
            'amount' => 1000,
            'payment_date' => '2026-06-03',
            'start_date' => '2026-06-03',
        ])->assertCreated();

        $this->assertSame('4000.00', (string) $member->fresh()->current_balance);
        $this->assertDatabaseCount('payment_memberships', 1);
    }

    public function testPaymentMethodWithReconciliationCreatesPendingSettlementUntilConfirmed(): void
    {
        $user = $this->actingAsUser(['payments.manage', 'accounts.manage']);
        $member = $this->createMember();
        $plan = $this->createPaymentPlan(['duration_value' => 1, 'duration_unit' => 'month', 'price' => 1000]);
        $account = $this->createAccount(['name' => 'Bank Account']);

        $methodId = (int) $this->postJson('/api/payment-methods', [
            'name' => 'Card Payment',
            'company_account_id' => $account->id,
            'deduction_type' => 'percentage',
            'deduction_value' => 3,
            'record_deduction_as_expense' => true,
            'requires_reconciliation' => true,
            'is_active' => true,
            'color' => 'blue',
            'icon' => 'Coins',
            'order' => 5,
        ])->assertCreated()->json('data.id');

        $this->assertDatabaseHas('payment_methods', [
            'id' => $methodId,
            'color' => 'blue',
            'icon' => 'Coins',
            'order' => 5,
        ]);

        $paymentId = (int) $this->postJson('/api/payments', [
            'member_id' => $member->id,
            'payment_method_id' => $methodId,
            'payment_plan_id' => $plan->id,
            'amount' => 1000,
            'payment_date' => '2026-06-03',
            'start_date' => '2026-06-03',
        ])->assertCreated()->json('data.id');

        $this->assertDatabaseHas('payment_settlements', [
            'source_type' => 'payment',
            'source_id' => $paymentId,
            'payment_method_id' => $methodId,
            'company_account_id' => $account->id,
            'gross_amount' => 1000,
            'deduction_amount' => 30,
            'net_amount' => 970,
            'status' => PaymentSettlement::STATUS_PENDING,
        ]);

        $this->assertDatabaseMissing('company_account_transactions', [
            'model_name' => 'payment',
            'reference_id' => $paymentId,
        ]);

        $settlementId = PaymentSettlement::query()
            ->where('source_type', 'payment')
            ->where('source_id', $paymentId)
            ->value('id');

        $this->postJson('/api/accounts/payment-settlements/' . $settlementId . '/confirm', [
            'transaction_date' => '2026-06-05',
            'confirmation_reference' => 'BANK-123',
        ])->assertOk();

        $this->assertDatabaseHas('payment_settlements', [
            'id' => $settlementId,
            'confirmed_by' => $user->id,
        ]);

        $this->assertDatabaseHas('company_account_transactions', [
            'model_name' => 'payment',
            'reference_id' => $paymentId,
            'company_account_id' => $account->id,
            'type' => 'payment',
            'amount' => 1000,
        ]);

        $this->assertDatabaseHas('company_account_transactions', [
            'model_name' => 'payment_deduction',
            'reference_id' => $settlementId,
            'company_account_id' => $account->id,
            'type' => 'payment_deduction',
            'amount' => -30,
        ]);

        $this->getJson('/api/accounts/' . $account->id)
            ->assertOk()
            ->assertJsonPath('data.current_balance', 970);
    }

    public function testBulkConfirmSettlementsConfirmsAllSelectedRows(): void
    {
        $user = $this->actingAsUser(['payments.manage', 'accounts.manage']);
        $member = $this->createMember();
        $plan = $this->createPaymentPlan(['duration_value' => 1, 'duration_unit' => 'month', 'price' => 1000]);
        $account = $this->createAccount(['name' => 'Bulk Bank Account']);

        $methodId = $this->createReconciliationPaymentMethod($account->id, 'Bulk Card');

        $paymentAId = (int) $this->postJson('/api/payments', [
            'member_id' => $member->id,
            'payment_method_id' => $methodId,
            'payment_plan_id' => $plan->id,
            'amount' => 1000,
            'payment_date' => '2026-06-03',
            'start_date' => '2026-06-03',
        ])->assertCreated()->json('data.id');

        $paymentBId = (int) $this->postJson('/api/payments', [
            'member_id' => $member->id,
            'payment_method_id' => $methodId,
            'payment_plan_id' => $plan->id,
            'amount' => 1200,
            'payment_date' => '2026-06-04',
            'start_date' => '2026-06-04',
        ])->assertCreated()->json('data.id');

        $settlementIds = PaymentSettlement::query()
            ->where('source_type', 'payment')
            ->whereIn('source_id', [$paymentAId, $paymentBId])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $this->postJson('/api/accounts/' . $account->id . '/payment-settlements/confirm-bulk', [
            'settlement_ids' => $settlementIds,
            'transaction_date' => '2026-06-05',
            'confirmation_reference' => 'BANK-BULK-001',
        ])->assertOk()
            ->assertJsonPath('data.confirmed_count', 2);

        foreach ($settlementIds as $settlementId) {
            $this->assertDatabaseHas('payment_settlements', [
                'id' => $settlementId,
                'status' => PaymentSettlement::STATUS_CONFIRMED,
                'confirmation_reference' => 'BANK-BULK-001',
                'confirmed_by' => $user->id,
            ]);

            $this->assertSame(
                '2026-06-05',
                PaymentSettlement::query()->find($settlementId)?->confirmed_transaction_date?->toDateString(),
            );

            $this->assertDatabaseHas('company_account_transactions', [
                'model_name' => 'payment_deduction',
                'reference_id' => $settlementId,
                'company_account_id' => $account->id,
                'type' => 'payment_deduction',
            ]);
        }

        $this->assertDatabaseHas('company_account_transactions', [
            'model_name' => 'payment',
            'reference_id' => $paymentAId,
            'company_account_id' => $account->id,
            'type' => 'payment',
            'amount' => 1000,
        ]);

        $this->assertDatabaseHas('company_account_transactions', [
            'model_name' => 'payment',
            'reference_id' => $paymentBId,
            'company_account_id' => $account->id,
            'type' => 'payment',
            'amount' => 1200,
        ]);
    }

    public function testBulkConfirmSettlementsIsAllOrNothingWhenAnySelectionIsNotPending(): void
    {
        $this->actingAsUser(['payments.manage', 'accounts.manage']);
        $member = $this->createMember();
        $plan = $this->createPaymentPlan(['duration_value' => 1, 'duration_unit' => 'month', 'price' => 1000]);
        $account = $this->createAccount(['name' => 'Atomic Bank']);

        $methodId = $this->createReconciliationPaymentMethod($account->id, 'Atomic Card');

        $paymentAId = (int) $this->postJson('/api/payments', [
            'member_id' => $member->id,
            'payment_method_id' => $methodId,
            'payment_plan_id' => $plan->id,
            'amount' => 1000,
            'payment_date' => '2026-06-06',
            'start_date' => '2026-06-06',
        ])->assertCreated()->json('data.id');

        $paymentBId = (int) $this->postJson('/api/payments', [
            'member_id' => $member->id,
            'payment_method_id' => $methodId,
            'payment_plan_id' => $plan->id,
            'amount' => 900,
            'payment_date' => '2026-06-07',
            'start_date' => '2026-06-07',
        ])->assertCreated()->json('data.id');

        $settlementAId = (int) PaymentSettlement::query()
            ->where('source_type', 'payment')
            ->where('source_id', $paymentAId)
            ->value('id');
        $settlementBId = (int) PaymentSettlement::query()
            ->where('source_type', 'payment')
            ->where('source_id', $paymentBId)
            ->value('id');

        $this->postJson('/api/accounts/payment-settlements/' . $settlementAId . '/confirm', [
            'transaction_date' => '2026-06-08',
            'confirmation_reference' => 'ALREADY-CONFIRMED',
        ])->assertOk();

        $this->postJson('/api/accounts/' . $account->id . '/payment-settlements/confirm-bulk', [
            'settlement_ids' => [$settlementAId, $settlementBId],
            'transaction_date' => '2026-06-09',
            'confirmation_reference' => 'SHOULD-NOT-APPLY',
        ])->assertStatus(422);

        $this->assertDatabaseHas('payment_settlements', [
            'id' => $settlementAId,
            'status' => PaymentSettlement::STATUS_CONFIRMED,
            'confirmation_reference' => 'ALREADY-CONFIRMED',
        ]);

        $this->assertDatabaseHas('payment_settlements', [
            'id' => $settlementBId,
            'status' => PaymentSettlement::STATUS_PENDING,
        ]);

        $this->assertDatabaseMissing('company_account_transactions', [
            'model_name' => 'payment',
            'reference_id' => $paymentBId,
        ]);
    }

    public function testBulkConfirmSettlementsRejectsRowsFromAnotherAccount(): void
    {
        $this->actingAsUser(['payments.manage', 'accounts.manage']);
        $member = $this->createMember();
        $plan = $this->createPaymentPlan(['duration_value' => 1, 'duration_unit' => 'month', 'price' => 1000]);
        $accountA = $this->createAccount(['name' => 'Scope A']);
        $accountB = $this->createAccount(['name' => 'Scope B']);

        $methodAId = $this->createReconciliationPaymentMethod($accountA->id, 'Scope Card A');
        $methodBId = $this->createReconciliationPaymentMethod($accountB->id, 'Scope Card B');

        $paymentAId = (int) $this->postJson('/api/payments', [
            'member_id' => $member->id,
            'payment_method_id' => $methodAId,
            'payment_plan_id' => $plan->id,
            'amount' => 1000,
            'payment_date' => '2026-06-10',
            'start_date' => '2026-06-10',
        ])->assertCreated()->json('data.id');

        $paymentBId = (int) $this->postJson('/api/payments', [
            'member_id' => $member->id,
            'payment_method_id' => $methodBId,
            'payment_plan_id' => $plan->id,
            'amount' => 800,
            'payment_date' => '2026-06-11',
            'start_date' => '2026-06-11',
        ])->assertCreated()->json('data.id');

        $settlementAId = (int) PaymentSettlement::query()
            ->where('source_type', 'payment')
            ->where('source_id', $paymentAId)
            ->value('id');
        $settlementBId = (int) PaymentSettlement::query()
            ->where('source_type', 'payment')
            ->where('source_id', $paymentBId)
            ->value('id');

        $this->postJson('/api/accounts/' . $accountA->id . '/payment-settlements/confirm-bulk', [
            'settlement_ids' => [$settlementAId, $settlementBId],
            'transaction_date' => '2026-06-12',
        ])->assertStatus(422);

        $this->assertDatabaseHas('payment_settlements', [
            'id' => $settlementAId,
            'status' => PaymentSettlement::STATUS_PENDING,
        ]);

        $this->assertDatabaseHas('payment_settlements', [
            'id' => $settlementBId,
            'status' => PaymentSettlement::STATUS_PENDING,
        ]);
    }

    public function testMembershipPaymentSendsReceiptNotification(): void
    {
        $this->actingAsUser(['payments.manage']);
        app(TenantConfigurationService::class)->updateBatch($this->tenant->id, [
            'notifications.inapp.enabled' => '1',
        ]);

        $member = $this->createMember(null, ['name' => 'Kamal Silva']);
        $plan = $this->createPaymentPlan(['duration_value' => 1, 'duration_unit' => 'month', 'price' => 2500]);
        $account = $this->createAccount(['name' => 'Main Cash']);

        $this->postJson('/api/payments', [
            'member_id' => $member->id,
            'company_account_id' => $account->id,
            'payment_method' => 'cash',
            'payment_plan_id' => $plan->id,
            'amount' => 2500,
            'payment_date' => '2026-06-08',
            'start_date' => '2026-06-08',
        ])->assertCreated();

        $this->assertDatabaseHas('member_notifications', [
            'member_id' => $member->id,
            'type' => 'membership_payment_received',
            'body' => 'Payment received! Kamal, your Test Gym membership is active from 2026-06-08 to 2026-07-07. Next payment due: 2026-07-08.',
        ]);
    }

    public function testMembershipExpiryReminderServiceQueuesDueOffsets(): void
    {
        app(TenantConfigurationService::class)->updateBatch($this->tenant->id, [
            'notifications.inapp.enabled' => '1',
        ]);

        $member = $this->createMember(null, ['name' => 'Asha Fernando']);
        $plan = $this->createPaymentPlan(['duration_value' => 1, 'duration_unit' => 'month']);
        $payment = MemberPayment::create([
            'member_id' => $member->id,
            'company_account_id' => $this->createAccount()->id,
            'payment_method' => 'cash',
            'amount' => 1000,
            'payment_date' => '2026-06-01',
        ]);
        PaymentMembership::create([
            'member_payment_id' => $payment->id,
            'payment_plan_id' => $plan->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-15',
        ]);

        $count = app(AutomatedMemberNotificationService::class)
            ->sendMembershipExpiryReminders(Carbon::parse('2026-06-08'));

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('member_notifications', [
            'member_id' => $member->id,
            'type' => 'membership_expiry_7_days_before',
            'body' => 'Hey Asha Fernando, your payment at Test Gym was due on 2026-06-15. Please renew and stay active!',
        ]);
    }

    public function testInsufficientWalletBalanceFails(): void
    {
        $this->actingAsUser(['payments.manage']);
        $member = $this->createMember(null, ['current_balance' => 100]);
        $plan = $this->createPaymentPlan(['duration_value' => 1, 'duration_unit' => 'month', 'price' => 1000]);

        $this->postJson('/api/payments', [
            'member_id' => $member->id,
            'company_account_id' => null,
            'payment_method' => 'member_wallet',
            'payment_plan_id' => $plan->id,
            'amount' => 1000,
            'payment_date' => '2026-06-03',
            'start_date' => '2026-06-03',
        ])->assertStatus(422);
    }

    // ------------------------------------------------------------------
    // Plan delete with members → archive guard
    // ------------------------------------------------------------------

    public function testCannotHardDeletePlanWithAssignedMembers(): void
    {
        $this->actingAsUser(['payments.manage']);
        $plan = $this->createPaymentPlan();
        $this->createMember(null, ['payment_plan_id' => $plan->id]);

        $this->deleteJson('/api/payment-plans/' . $plan->id)
            ->assertStatus(422)
            ->assertJsonPath('member_count', 1);

        // Force-archive succeeds
        $this->deleteJson('/api/payment-plans/' . $plan->id . '?force=1')
            ->assertNoContent();

        $this->assertSoftDeleted('payment_plans', ['id' => $plan->id]);
    }

    // ------------------------------------------------------------------
    // Outstanding payments
    // ------------------------------------------------------------------

    public function testCreateOutstandingPayment(): void
    {
        app(TenantConfigurationService::class)->updateBatch($this->tenant->id, [
            'notifications.inapp.enabled' => '1',
        ]);

        $this->actingAsUser(['payments.manage']);
        $member = $this->createMember(null, ['name' => 'John Doe']);
        $plan = $this->createPaymentPlan(['duration_value' => 1, 'duration_unit' => 'month', 'price' => 1000]);

        $response = $this->postJson('/api/payments', [
            'member_id' => $member->id,
            'payment_plan_id' => $plan->id,
            'amount' => 1000,
            'payment_date' => '2026-06-03',
            'start_date' => '2026-06-03',
            'is_paid' => false,
        ])->assertCreated();

        $this->assertDatabaseHas('member_payments', [
            'id' => $response->json('data.id'),
            'is_paid' => false,
            'amount' => 1000.00,
            'paid_amount' => 0.00,
            'balance' => 1000.00,
        ]);

        // Membership should still be created
        $this->assertDatabaseHas('payment_memberships', [
            'payment_plan_id' => $plan->id,
            'start_date' => '2026-06-03 00:00:00',
            'end_date' => '2026-07-02 00:00:00',
        ]);

        // Should dispatch outstanding notification
        $this->assertDatabaseHas('member_notifications', [
            'member_id' => $member->id,
            'type' => 'membership_payment_outstanding',
        ]);
    }

    public function testMarkOutstandingPaymentAsPaid(): void
    {
        app(TenantConfigurationService::class)->updateBatch($this->tenant->id, [
            'notifications.inapp.enabled' => '1',
        ]);

        $this->actingAsUser(['payments.manage']);
        $member = $this->createMember(null, ['name' => 'Jane Smith']);
        $plan = $this->createPaymentPlan(['duration_value' => 1, 'duration_unit' => 'month', 'price' => 1000]);
        $account = $this->createAccount(['name' => 'Main Account']);

        // Create outstanding payment
        $payment = MemberPayment::create([
            'member_id' => $member->id,
            'amount' => 1000,
            'payment_date' => '2026-06-03',
            'is_paid' => false,
            'paid_amount' => 0,
            'balance' => 1000,
        ]);

        // Mark as paid
        $response = $this->postJson('/api/payments/' . $payment->id . '/mark-as-paid', [
            'company_account_id' => $account->id,
            'payment_method' => 'cash',
        ])->assertOk();

        $this->assertDatabaseHas('member_payments', [
            'id' => $payment->id,
            'is_paid' => true,
            'paid_amount' => 1000.00,
            'balance' => 0.00,
            'company_account_id' => $account->id,
        ]);

        // Should dispatch receipt notification
        $this->assertDatabaseHas('member_notifications', [
            'member_id' => $member->id,
            'type' => 'membership_payment_received',
        ]);
    }

    public function testTotalOutstandingCalculationIncludesUnpaidPayments(): void
    {
        $this->actingAsUser(['payments.manage', 'members.view']);
        $member = $this->createMember(null, ['name' => 'Bob Junior']);
        $plan = $this->createPaymentPlan(['duration_value' => 1, 'duration_unit' => 'month', 'price' => 1200]);

        // Create outstanding payment
        $this->postJson('/api/payments', [
            'member_id' => $member->id,
            'payment_plan_id' => $plan->id,
            'amount' => 1200,
            'payment_date' => '2026-06-03',
            'start_date' => '2026-06-03',
            'is_paid' => false,
        ])->assertCreated();

        // Get members list, verify total_outstanding_amount matches
        $response = $this->getJson('/api/members')->assertOk();
        $memberData = collect($response->json('data'))->firstWhere('id', $member->id);

        $this->assertNotNull($memberData);
        $this->assertEquals(1200.00, (float) $memberData['payments_outstanding_amount']);
        $this->assertEquals(1200.00, (float) $memberData['total_outstanding_amount']);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function createAccount(array $attributes = []): CompanyAccount
    {
        return CompanyAccount::create(array_merge([
            'name' => 'Cash',
            'opening_balance' => 0,
        ], $attributes));
    }

    private function createReconciliationPaymentMethod(int $accountId, string $name): int
    {
        return (int) $this->postJson('/api/payment-methods', [
            'name' => $name,
            'company_account_id' => $accountId,
            'deduction_type' => 'percentage',
            'deduction_value' => 3,
            'record_deduction_as_expense' => true,
            'requires_reconciliation' => true,
            'is_active' => true,
            'color' => 'blue',
            'icon' => 'Coins',
            'order' => 5,
        ])->assertCreated()->json('data.id');
    }
}
