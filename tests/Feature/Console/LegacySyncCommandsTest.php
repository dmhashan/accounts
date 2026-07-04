<?php

namespace Tests\Feature\Console;

use App\Models\CompanyAccountTransaction;
use App\Models\Member;
use App\Models\MemberAttendance;
use App\Models\MemberPayment;
use App\Models\PaymentMembership;
use App\Models\PaymentPlan;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Api\ApiRouteTestCase;

class LegacySyncCommandsTest extends ApiRouteTestCase
{
    public function testAttendanceSyncImportsAndLinksCurrentTenantMember(): void
    {
        $member = $this->createMember(attributes: ['biometric_member_id' => '42']);
        Http::fake([
            'https://legacy.test/attendance-summary-to-date*' => Http::response([
                'attendedMembers' => [[
                    'id' => 'attendance-uuid-1',
                    'memberId' => 42,
                    'username' => 'legacy-attendee',
                ]],
            ]),
        ]);

        $exitCode = Artisan::call('legacy:sync-attendance', [
            '--access-token' => 'secret-token',
            '--date-start' => '2026-06-08',
            '--date-end' => '2026-06-08',
            '--tenant-domain' => $this->tenant->domain,
            '--base-url' => 'https://legacy.test',
        ]);

        $this->assertSame(0, $exitCode);
        $attendance = MemberAttendance::sole();
        $this->assertSame($member->id, $attendance->member_id);
        $this->assertSame('attendance-uuid-1', $attendance->legacy_uuid);
        Http::assertSentCount(1);
    }

    public function testPaymentSyncImportsMembershipAndAccountTransaction(): void
    {
        $member = $this->createMember(attributes: ['biometric_member_id' => '84']);
        $account = $this->createCompanyAccount(['name' => 'Cash Account']);
        Http::fake([
            'https://legacy.test/getpaymenthistory*' => Http::response([
                'items' => [[
                    'id' => 'payment-uuid-1',
                    'memberid' => 84,
                    'username' => 'legacy-payer',
                    'amount' => 3000,
                    'paymentdate' => '2026-06-01',
                    'nextpaymentdate' => '2026-07-01',
                ]],
                'totalCount' => 1,
            ]),
        ]);

        $exitCode = Artisan::call('legacy:sync-payments', [
            '--access-token' => 'secret-token',
            '--tenant-id' => $this->tenant->id,
            '--account-name' => $account->name,
            '--base-url' => 'https://legacy.test',
            '--page-size' => 100,
        ]);

        $this->assertSame(0, $exitCode);
        $payment = MemberPayment::sole();
        $this->assertSame($member->id, $payment->member_id);
        $this->assertSame($account->id, $payment->company_account_id);
        $this->assertSame('payment-uuid-1', $payment->legacy_uuid);

        $membership = PaymentMembership::sole();
        $this->assertSame($payment->id, $membership->member_payment_id);
        $this->assertSame('2026-06-30', $membership->end_date->toDateString());

        $transaction = CompanyAccountTransaction::sole();
        $this->assertSame($payment->id, $transaction->reference_id);
        $this->assertSame('payment', $transaction->model_name);
    }

    public function testMemberSyncImportsNestedLegacyMemberAndPaymentPlan(): void
    {
        Http::fake(function ($request) {
            if (str_contains($request->url(), '/getmemberview/legacy-member-1')) {
                return Http::response([
                    'data' => [
                        'emailAddress' => ' LEGACY.MEMBER@EXAMPLE.COM ',
                        'fullName' => 'Legacy Member',
                        'userName' => 'legacy-member',
                        'gender' => 'F',
                        'isActive' => 'yes',
                        'memberCode' => '77',
                        'mobileNumber' => '0701234567',
                        'birthDay' => '1995-04-03',
                        'dateOfJoin' => '2024-01-02',
                        'paymentPlan' => ['planName' => 'Premium', 'price' => 'Rs. 4,500.00'],
                        'address' => ['text' => 'Legacy Street'],
                        'remark' => 'Imported member',
                    ],
                ]);
            }

            return Http::response(['data' => [['id' => 'legacy-member-1']]]);
        });

        $exitCode = Artisan::call('legacy:sync-members', [
            '--access-token' => 'secret-token',
            '--tenant-domain' => $this->tenant->domain,
            '--base-url' => 'https://legacy.test',
            '--max-pages' => 1,
        ]);

        $this->assertSame(0, $exitCode);
        $member = Member::where('email', 'legacy.member@example.com')->firstOrFail();
        $this->assertSame('Legacy Member', $member->name);
        $this->assertSame('female', $member->gender);
        $this->assertSame('77', $member->biometric_member_id);
        $plan = PaymentPlan::findOrFail($member->payment_plan_id);
        $this->assertSame('Premium', $plan->name);
        $this->assertSame('4500.00', $plan->price);
    }

    public function testLegacyCommandsRejectInvalidOperationalInputs(): void
    {
        $this->assertSame(1, Artisan::call('legacy:sync-attendance', [
            '--access-token' => '',
        ]));
        $this->assertSame(1, Artisan::call('legacy:sync-attendance', [
            '--access-token' => 'token',
            '--tenant-domain' => 'missing-tenant',
        ]));
        $this->assertSame(1, Artisan::call('legacy:sync-payments', [
            '--access-token' => 'token',
            '--tenant-id' => $this->tenant->id,
            '--account-name' => 'Missing Account',
        ]));
        $this->assertSame(1, Artisan::call('legacy:sync-members', [
            '--access-token' => '',
        ]));
    }
}
