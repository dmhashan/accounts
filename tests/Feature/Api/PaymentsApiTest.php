<?php

namespace Tests\Feature\Api;

use App\Models\CompanyAccount;
use App\Models\MemberPayment;
use App\Models\PaymentMembership;
use App\Services\AutomatedMemberNotificationService;
use App\Services\BiometricSyncService;
use App\Services\TenantConfigurationService;
use Illuminate\Support\Carbon;

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

    /**
     * @dataProvider realWorldPlansProvider
     */
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
            'tenant_id' => $this->tenant->id,
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

    /**
     * @dataProvider endDateProvider
     */
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
            'tenant_id' => $this->tenant->id,
            'member_id' => $member->id,
            'company_account_id' => $this->createAccount()->id,
            'payment_method' => 'cash',
            'amount' => 1000,
            'payment_date' => '2026-01-15',
        ]);
        PaymentMembership::create([
            'tenant_id' => $this->tenant->id,
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

    public function testMembershipPaymentSendsReceiptNotification(): void
    {
        $this->actingAsUser(['payments.manage']);
        app(TenantConfigurationService::class)->updateBatch($this->tenant->id, [
            'notifications.inapp.enabled' => '1',
        ]);

        $member = $this->createMember(null, ['first_name' => 'Kamal', 'last_name' => 'Silva', 'name' => 'Kamal Silva']);
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
            'tenant_id' => $this->tenant->id,
            'member_id' => $member->id,
            'type' => 'membership_payment_received',
            'body' => 'Payment received! Kamal Silva paid 2,500.00 at Test Gym on 2026-06-08 via Main Cash',
        ]);
    }

    public function testMembershipExpiryReminderServiceQueuesDueOffsets(): void
    {
        app(TenantConfigurationService::class)->updateBatch($this->tenant->id, [
            'notifications.inapp.enabled' => '1',
        ]);

        $member = $this->createMember(null, ['first_name' => 'Asha', 'last_name' => 'Fernando', 'name' => 'Asha Fernando']);
        $plan = $this->createPaymentPlan(['duration_value' => 1, 'duration_unit' => 'month']);
        $payment = MemberPayment::create([
            'tenant_id' => $this->tenant->id,
            'member_id' => $member->id,
            'company_account_id' => $this->createAccount()->id,
            'payment_method' => 'cash',
            'amount' => 1000,
            'payment_date' => '2026-06-01',
        ]);
        PaymentMembership::create([
            'tenant_id' => $this->tenant->id,
            'member_payment_id' => $payment->id,
            'payment_plan_id' => $plan->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-15',
        ]);

        $count = app(AutomatedMemberNotificationService::class)
            ->sendMembershipExpiryReminders(Carbon::parse('2026-06-08'));

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('member_notifications', [
            'tenant_id' => $this->tenant->id,
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
    // Helpers
    // ------------------------------------------------------------------

    private function createAccount(array $attributes = []): CompanyAccount
    {
        return CompanyAccount::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'name' => 'Cash',
            'opening_balance' => 0,
        ], $attributes));
    }
}
