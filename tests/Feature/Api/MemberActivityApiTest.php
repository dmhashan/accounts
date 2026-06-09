<?php

namespace Tests\Feature\Api;

use App\Models\MemberActivityLog;
use App\Models\Tenant;
use Illuminate\Support\Str;

class MemberActivityApiTest extends ApiRouteTestCase
{
    public function testActivityListFiltersAndExcludesOtherTenants(): void
    {
        $this->actingAsUser(['activity.view']);
        $member = $this->createMember();
        $matching = MemberActivityLog::create([
            'tenant_id' => $this->tenant->id,
            'member_id' => $member->id,
            'session_id' => 'current-session',
            'event_type' => 'tab_view',
            'device_type' => 'mobile',
            'browser' => 'Safari',
            'os' => 'iOS',
        ]);
        MemberActivityLog::create([
            'tenant_id' => $this->tenant->id,
            'member_id' => $member->id,
            'session_id' => 'other-event',
            'event_type' => 'login',
            'device_type' => 'desktop',
        ]);

        $otherTenant = Tenant::create([
            'name' => 'Other Gym',
            'domain' => 'other-activity',
            'tenant_uuid' => Str::uuid()->toString(),
        ]);
        MemberActivityLog::create([
            'tenant_id' => $otherTenant->id,
            'member_id' => $member->id,
            'session_id' => 'private-session',
            'event_type' => 'tab_view',
            'device_type' => 'mobile',
        ]);

        $this->getJson('/api/member-activity?event_type=tab_view&device_type=mobile')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $matching->id)
            ->assertJsonPath('data.0.member_name', $member->first_name . ' ' . $member->last_name);
    }

    public function testActivityExportContainsFilteredCurrentTenantRows(): void
    {
        $this->actingAsUser(['activity.view']);
        $member = $this->createMember();
        MemberActivityLog::create([
            'tenant_id' => $this->tenant->id,
            'member_id' => $member->id,
            'session_id' => 'export-session',
            'event_type' => 'workout_opened',
            'device_type' => 'desktop',
            'browser' => 'Chrome',
            'os' => 'Windows',
            'screen_width' => 1920,
            'screen_height' => 1080,
        ]);

        $response = $this->get('/api/member-activity/export?event_type=workout_opened')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('export-session', $content);
        $this->assertStringContainsString('workout_opened', $content);
        $this->assertStringContainsString('1920x1080', $content);
    }
}
