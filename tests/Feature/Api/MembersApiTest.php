<?php

namespace Tests\Feature\Api;

use App\Models\MemberAttendance;
use App\Models\MemberNotification;
use App\Models\MemberPayment;
use App\Models\PaymentMembership;
use App\Models\Sale;
use App\Services\AutomatedMemberNotificationService;
use App\Services\BiometricSyncService;
use App\Services\TenantConfigurationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class MembersApiTest extends ApiRouteTestCase
{
    public function testMembersMetaRouteReturnsGeneratedMemberId(): void
    {
        $this->actingAsUser(['users.view']);

        $response = $this->getJson('/api/members/meta');

        $response
            ->assertOk()
            ->assertJsonStructure(['generated_member_id']);
    }

    public function testMembersIndexRouteReturnsPaginatedMembers(): void
    {
        $this->actingAsUser(['users.view']);
        $member = $this->createMember();

        $response = $this->getJson('/api/members?per_page=10');

        $response
            ->assertOk()
            ->assertJsonStructure(['data', 'meta', 'permissions'])
            ->assertJsonFragment(['id' => $member->id]);
    }

    public function testMembersIndexRouteIncludesDetailsAndAppliesBasicFilters(): void
    {
        $this->travelTo(Carbon::parse('2026-07-04 10:00:00'));

        try {
            $this->actingAsUser(['users.view']);
            $plan = $this->createPaymentPlan(['name' => 'Gold Plan']);
            $member = $this->createMember(null, [
                'name' => 'Filter Match',
                'gender' => 'female',
                'payment_plan_id' => $plan->id,
                'current_balance' => -50,
                'profile_photo_path' => 'member-avatars/filter-match.jpg',
            ]);

            $attachDetails = function ($targetMember, float $walletOutstanding = 50) use ($plan): void {
                $targetMember->update([
                    'current_balance' => 0 - $walletOutstanding,
                ]);

                $payment = MemberPayment::create([
                    'member_id' => $targetMember->id,
                    'company_account_id' => null,
                    'payment_method' => 'cash',
                    'amount' => 1200,
                    'payment_date' => '2026-05-05',
                ]);

                PaymentMembership::create([
                    'member_payment_id' => $payment->id,
                    'payment_plan_id' => $plan->id,
                    'start_date' => '2026-04-06',
                    'end_date' => '2026-05-05',
                ]);

                MemberAttendance::create([
                    'member_id' => $targetMember->id,
                    'attended_date' => '2026-05-05',
                ]);

                Sale::create([
                    'customer_name' => $targetMember->name,
                    'customer_member_id' => $targetMember->id,
                    'customer_type' => 'local',
                    'payment_method' => 'cash',
                    'total_amount' => 500,
                    'paid_amount' => 100,
                    'balance' => -400,
                    'is_paid' => false,
                ]);
            };

            $attachDetails($member);

            $inactiveUnverifiedMember = $this->createMember(null, [
                'name' => 'Inactive Match',
                'gender' => 'female',
                'payment_plan_id' => $plan->id,
                'is_active' => false,
                'is_verified' => false,
            ]);
            $attachDetails($inactiveUnverifiedMember, 25);

            $this->createMember(null, [
                'name' => 'Filter Miss',
                'gender' => 'male',
            ]);

            $response = $this->getJson('/api/members?per_page=10'
                . '&active=active'
                . '&verified=verified'
                . '&gender=female'
                . '&plan_id=' . $plan->id
                . '&expiry_preset=expired_60'
                . '&attendance_preset=older_60'
                . '&outstanding=with');

            $response
                ->assertOk()
                ->assertJsonPath('meta.total', 1)
                ->assertJsonPath('data.0.id', $member->id)
                ->assertJsonPath('data.0.plan_name', 'Gold Plan')
                ->assertJsonPath('data.0.membership_expiry_date', '2026-05-05')
                ->assertJsonPath('data.0.days_until_payment_expiry', -60)
                ->assertJsonPath('data.0.last_attendance_date', '2026-05-05')
                ->assertJsonPath('data.0.days_since_last_attendance', 60)
                ->assertJsonPath('data.0.total_outstanding_amount', 450);

            $this->assertStringContainsString(
                'member-avatars/filter-match.jpg',
                (string) $response->json('data.0.profile_photo_url'),
            );

            $this->getJson('/api/members?active=active&expiry_preset=expired_30&attendance_preset=older_30')
                ->assertOk()
                ->assertJsonPath('meta.total', 1);

            $this->getJson('/api/members?active=active&expiry_preset=expired_90')
                ->assertOk()
                ->assertJsonPath('meta.total', 0);
        } finally {
            $this->travelBack();
        }
    }

    public function testMembersIndexPlanFilterUsesResolvedPlanAndUpdatesPaginationMeta(): void
    {
        $this->actingAsUser(['users.view']);

        $basicPlan = $this->createPaymentPlan(['name' => 'Basic Plan']);
        $goldPlan = $this->createPaymentPlan(['name' => 'Gold Plan']);
        $otherPlan = $this->createPaymentPlan(['name' => 'Other Plan']);

        $latestPlanMember = $this->createMember(null, [
            'name' => 'Latest Plan Member',
            'payment_plan_id' => $basicPlan->id,
        ]);
        $defaultPlanMember = $this->createMember(null, [
            'name' => 'Default Plan Member',
            'payment_plan_id' => $goldPlan->id,
        ]);
        $missedPlanMember = $this->createMember(null, [
            'name' => 'Missed Plan Member',
            'payment_plan_id' => $otherPlan->id,
        ]);

        $payment = MemberPayment::create([
            'member_id' => $latestPlanMember->id,
            'company_account_id' => null,
            'payment_method' => 'cash',
            'amount' => 1200,
            'payment_date' => '2026-07-01',
        ]);

        PaymentMembership::create([
            'member_payment_id' => $payment->id,
            'payment_plan_id' => $goldPlan->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
        ]);

        $response = $this->getJson('/api/members?per_page=1&plan_id=' . $goldPlan->id);

        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonCount(1, 'data');

        $allMatches = $this->getJson('/api/members?per_page=10&plan_id=' . $goldPlan->id)
            ->assertOk()
            ->assertJsonPath('meta.total', 2);

        $matchedIds = collect($allMatches->json('data'))->pluck('id')->all();
        $this->assertContains($latestPlanMember->id, $matchedIds);
        $this->assertContains($defaultPlanMember->id, $matchedIds);
        $this->assertNotContains($missedPlanMember->id, $matchedIds);

        $this->getJson('/api/members?per_page=10&plan_id=' . $basicPlan->id)
            ->assertOk()
            ->assertJsonPath('meta.total', 0)
            ->assertJsonPath('meta.last_page', 1);
    }

    public function testMembersExportRouteReturnsCsvStream(): void
    {
        $this->actingAsUser(['users.view']);
        $this->createMember();

        $response = $this->get('/api/members/export/google-contacts');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
    }

    public function testMembersShowRouteReturnsSingleMember(): void
    {
        $this->actingAsUser(['users.view', 'users.edit', 'users.delete']);
        $member = $this->createMember(null, [
            'is_active' => false,
            'is_verified' => true,
            'current_balance' => 1250,
        ]);

        $response = $this->getJson('/api/members/' . $member->id);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $member->id)
            ->assertJsonPath('data.email', $member->email)
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.is_verified', true)
            ->assertJsonPath('permissions.edit', true)
            ->assertJsonPath('permissions.delete', true);
    }

    public function testMembersShowRouteTriesBiometricFacePhotoSyncWhenMissingPhoto(): void
    {
        $this->actingAsUser(['users.view', 'users.edit', 'users.delete']);
        $member = $this->createMember(null, [
            'biometric_member_id' => 'BIO-1001',
            'profile_photo_path' => null,
        ]);

        $biometric = \Mockery::mock(BiometricSyncService::class);
        $biometric->shouldReceive('getMemberDeviceInfo')
            ->once()
            ->withArgs(fn ($arg) => $arg->id === $member->id)
            ->andReturn([
                'connection_failed' => false,
                'not_assigned' => false,
                'not_found' => false,
                'face' => ['enrolled' => true],
            ]);

        $biometric->shouldReceive('uploadFaceAsAvatar')
            ->once()
            ->withArgs(fn ($arg) => $arg->id === $member->id)
            ->andReturn([
                'success' => true,
                'profile_photo_url' => 'https://example.test/photo.jpg',
            ]);

        $this->app->instance(BiometricSyncService::class, $biometric);

        $response = $this->getJson('/api/members/' . $member->id);

        $response->assertOk();
    }

    public function testMembersShowRouteSkipsUploadWhenFaceNotEnrolled(): void
    {
        $this->actingAsUser(['users.view', 'users.edit', 'users.delete']);
        $member = $this->createMember(null, [
            'biometric_member_id' => 'BIO-1002',
            'profile_photo_path' => null,
        ]);

        $biometric = \Mockery::mock(BiometricSyncService::class);
        $biometric->shouldReceive('getMemberDeviceInfo')
            ->once()
            ->withArgs(fn ($arg) => $arg->id === $member->id)
            ->andReturn([
                'connection_failed' => false,
                'not_assigned' => false,
                'not_found' => false,
                'face' => ['enrolled' => false],
            ]);

        $biometric->shouldReceive('uploadFaceAsAvatar')->never();

        $this->app->instance(BiometricSyncService::class, $biometric);

        $response = $this->getJson('/api/members/' . $member->id);

        $response->assertOk();
    }

    public function testMembersStoreRouteCreatesMember(): void
    {
        $this->createRole('member');
        $this->actingAsUser(['users.view', 'users.create']);

        $payload = $this->memberPayload([
            'email' => 'member-store@example.com',
        ]);

        $response = $this->postJson('/api/members', $payload);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Member created successfully.');

        $this->assertDatabaseHas('members', [
            'email' => 'member-store@example.com',
        ]);
    }

    public function testMembersStoreRouteAllowsOptionalEmail(): void
    {
        $this->createRole('member');
        $this->actingAsUser(['users.view', 'users.create']);

        $payload = $this->memberPayload([
            'email' => null,
        ]);

        $response = $this->postJson('/api/members', $payload);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Member created successfully.');

        $this->assertDatabaseHas('members', [
            'email' => null,
        ]);
    }

    public function testMembersStoreRouteSendsWelcomeNotificationWithConfiguredLinks(): void
    {
        $this->createRole('member');
        $this->actingAsUser(['users.view', 'users.create']);

        $biometric = \Mockery::mock(BiometricSyncService::class);
        $biometric->shouldReceive('syncMember')->andReturnNull();
        $this->app->instance(BiometricSyncService::class, $biometric);

        app(TenantConfigurationService::class)->updateBatch($this->tenant->id, [
            'notifications.inapp.enabled' => '1',
            'general.member_notifications' => json_encode([
                'member_login_url' => 'https://members.test/login',
            ]),
        ]);

        $payload = $this->memberPayload([
            'name' => 'Nimali Perera',
            'email' => 'nimali@example.com',
            'gender' => 'female',
        ]);

        $this->postJson('/api/members', $payload)->assertCreated();

        $notification = MemberNotification::query()
            ->where('type', 'member_welcome')
            ->first();

        $this->assertNotNull($notification);
        $this->assertSame("Hi Nimali Perera, welcome to Test Gym\n\nLogin: https://members.test/login\nLet's begin your fitness journey!", $notification->body);
    }

    public function testMemberMilestoneServiceSendsBirthdayNotification(): void
    {
        app(TenantConfigurationService::class)->updateBatch($this->tenant->id, [
            'notifications.inapp.enabled' => '1',
        ]);

        $member = $this->createMember(null, [
            'name' => 'Asha Fernando',
            'date_of_birth' => '1994-06-09',
        ]);

        $count = app(AutomatedMemberNotificationService::class)
            ->sendMemberMilestoneNotifications(Carbon::parse('2026-06-09'));

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('member_notifications', [
            'member_id' => $member->id,
            'type' => 'member_birthday',
            'body' => 'Happy Birthday Asha Fernando! Wishing you a strong, healthy and joyful year ahead from everyone at Test Gym. Keep moving, keep growing!',
        ]);
    }

    public function testMemberMilestoneServiceSendsJoinAnniversaryWithLastYearAttendance(): void
    {
        app(TenantConfigurationService::class)->updateBatch($this->tenant->id, [
            'notifications.inapp.enabled' => '1',
        ]);

        $member = $this->createMember(null, [
            'name' => 'Kamal Silva',
            'date_of_birth' => '1990-01-01',
            'joined_date' => '2025-06-09',
        ]);

        foreach (['2025-06-09', '2025-12-15', '2026-06-08'] as $date) {
            MemberAttendance::create([
                'member_id' => $member->id,
                'attended_date' => $date,
            ]);
        }

        MemberAttendance::create([
            'member_id' => $member->id,
            'attended_date' => '2025-06-08',
        ]);

        $count = app(AutomatedMemberNotificationService::class)
            ->sendMemberMilestoneNotifications(Carbon::parse('2026-06-09'));

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('member_notifications', [
            'member_id' => $member->id,
            'type' => 'member_join_anniversary',
            'body' => 'Happy fitness anniversary Kamal Silva! You showed up for 2 training days at Test Gym in the last year. That consistency matters. Keep pushing forward!',
        ]);
    }

    public function testMembersUpdateRouteUpdatesMember(): void
    {
        $this->actingAsUser(['users.view', 'users.edit']);
        $linkedUser = $this->createUser();
        $member = $this->createMember($linkedUser, [
            'email' => 'member-update@example.com',
        ]);

        $payload = $this->memberPayload([
            'name' => 'Updated Member',
            'email' => 'member-update@example.com',
        ]);

        $response = $this->putJson('/api/members/' . $member->id, $payload);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Member updated successfully.');

        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'name' => 'Updated Member',
        ]);
    }

    public function testMembersUpdateRouteAllowsBlankMemberEmailWithoutChangingLinkedUserEmail(): void
    {
        $this->actingAsUser(['users.view', 'users.edit']);
        $linkedUser = $this->createUser([], ['email' => 'linked-member@example.com']);
        $member = $this->createMember($linkedUser, [
            'email' => 'member-update@example.com',
        ]);

        $payload = $this->memberPayload([
            'email' => null,
        ]);

        $response = $this->putJson('/api/members/' . $member->id, $payload);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Member updated successfully.');

        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'email' => null,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $linkedUser->id,
            'email' => 'linked-member@example.com',
            'username' => $linkedUser->username,
        ]);
    }

    public function testMembersToggleStatusRouteTogglesState(): void
    {
        $this->actingAsUser(['users.view', 'users.edit']);
        $member = $this->createMember(null, ['is_active' => true]);

        $response = $this->patchJson('/api/members/' . $member->id . '/toggle-status');

        $response
            ->assertOk()
            ->assertJsonPath('is_active', false);
    }

    public function testMembersToggleVerificationRouteTogglesState(): void
    {
        $this->actingAsUser(['users.view', 'users.edit']);
        $member = $this->createMember(null, ['is_verified' => true]);

        $response = $this->patchJson('/api/members/' . $member->id . '/toggle-verification');

        $response
            ->assertOk()
            ->assertJsonPath('is_verified', false);
    }

    public function testMembersDestroyRouteDeletesMemberAndLinkedUser(): void
    {
        $this->actingAsUser(['users.view', 'users.delete']);
        $linkedUser = $this->createUser([], ['email' => 'linked-user@example.com']);
        $member = $this->createMember($linkedUser);

        $response = $this->deleteJson('/api/members/' . $member->id);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Member deleted successfully.');

        $this->assertDatabaseMissing('members', ['id' => $member->id]);
        $this->assertDatabaseMissing('users', ['id' => $linkedUser->id]);
    }

    private function memberPayload(array $overrides = []): array
    {
        $plan = $this->createPaymentPlan();

        return array_merge([
            'name' => 'John Doe',
            'gender' => 'male',
            'email' => 'member-' . Str::lower(Str::random(6)) . '@example.com',
            'phone_number' => '0712345678',
            'nic' => '993456789V',
            'date_of_birth' => now()->subYears(25)->toDateString(),
            'address' => 'No 1, Main Street',
            'admission_fee' => 500,
            'payment_plan_id' => $plan->id,
            'price' => 2500,
            'joined_date' => now()->toDateString(),
            'comment' => 'Test member payload',
        ], $overrides);
    }
}
