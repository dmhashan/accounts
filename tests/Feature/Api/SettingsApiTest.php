<?php

namespace Tests\Feature\Api;

use App\Jobs\RunLegacyCommand;
use App\Models\CommandRunLog;
use App\Models\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SettingsApiTest extends ApiRouteTestCase
{
    public function testGeneralSettingsOnlyUpdateTheCurrentTenant(): void
    {
        $this->actingAsUser(['settings.manage']);
        $otherTenant = $this->createOtherTenant();

        $this->getJson('/api/settings/general')
            ->assertOk()
            ->assertJsonPath('data.name', 'Test Gym');

        $this->putJson('/api/settings/general', [
            'name' => 'Updated Gym',
            'address' => '42 Main Street',
            'email' => 'admin@updated-gym.test',
            'phone' => '0701234567',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Updated Gym')
            ->assertJsonPath('data.email', 'admin@updated-gym.test');

        $this->assertDatabaseHas('tenants', [
            'id' => $this->tenant->id,
            'name' => 'Updated Gym',
        ]);
        $this->assertDatabaseHas('tenants', [
            'id' => $otherTenant->id,
            'name' => 'Other Gym',
        ]);
    }

    public function testTenantLogoIsNamespacedReplacedAndDeleted(): void
    {
        $disk = (string) config('filesystems.media_disk', 'public');
        Storage::fake($disk);
        $this->actingAsUser(['settings.manage']);

        $firstPath = $this->postJson('/api/settings/general/logo', [
            'logo' => UploadedFile::fake()->image('first-logo.png'),
        ])->assertOk()
            ->json('logo_url');

        $storedFirstPath = (string) $this->tenant->fresh()->logo_path;
        $this->assertStringContainsString($this->tenant->tenant_uuid, $storedFirstPath);
        $this->assertNotEmpty($firstPath);
        Storage::disk($disk)->assertExists($storedFirstPath);

        $this->postJson('/api/settings/general/logo', [
            'logo' => UploadedFile::fake()->image('replacement-logo.png'),
        ])->assertOk();

        $storedReplacementPath = (string) $this->tenant->fresh()->logo_path;
        Storage::disk($disk)->assertMissing($storedFirstPath);
        Storage::disk($disk)->assertExists($storedReplacementPath);

        $this->deleteJson('/api/settings/general/logo')->assertOk();

        Storage::disk($disk)->assertMissing($storedReplacementPath);
        $this->assertNull($this->tenant->fresh()->logo_path);
    }

    public function testLegacyToolQueuesOnlyTheCurrentTenantAndRedactsAccessToken(): void
    {
        Queue::fake();
        $user = $this->actingAsUser(['settings.manage']);
        $accessToken = 'legacy-secret-access-token';

        $logId = (int) $this->postJson('/api/settings/legacy-tools/run', [
            'command' => 'legacy:sync-payments',
            'access_token' => $accessToken,
            'date_start' => '2026-06-01',
            'date_end' => '2026-06-09',
            'account_name' => 'Cash',
            'page_size' => 100,
        ])->assertOk()
            ->assertJsonPath('queued', true)
            ->json('log_id');

        $log = CommandRunLog::findOrFail($logId);
        $this->assertSame($user->id, $log->user_id);
        $this->assertArrayNotHasKey('--access-token', $log->params);
        $this->assertSame($this->tenant->domain, $log->params['--tenant-domain']);

        Queue::assertPushed(RunLegacyCommand::class, function (RunLegacyCommand $job) use ($accessToken, $logId) {
            return $this->readPrivate($job, 'logId') === $logId
                && $this->readPrivate($job, 'command') === 'legacy:sync-payments'
                && $this->readPrivate($job, 'params')['--access-token'] === $accessToken
                && $this->readPrivate($job, 'params')['--tenant-domain'] === $this->tenant->domain;
        });
    }

    public function testLegacyToolLogsAreListedAndValidationStillApplies(): void
    {
        $this->actingAsUser(['settings.manage']);

        CommandRunLog::create([
            'command' => 'legacy:sync-members',
            'params' => ['--tenant-domain' => $this->tenant->domain],
            'success' => true,
        ]);

        $this->getJson('/api/settings/legacy-tools/logs?command=legacy:sync-members')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.params.--tenant-domain', $this->tenant->domain);

        $this->postJson('/api/settings/legacy-tools/run', [
            'command' => 'legacy:sync-attendance',
            'access_token' => 'long-enough-token',
            'date_start' => '2026-06-09',
            'date_end' => '2026-06-01',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('date_end');
    }

    private function createOtherTenant(): Tenant
    {
        return Tenant::create([
            'name' => 'Other Gym',
            'domain' => 'other-settings',
            'tenant_uuid' => Str::uuid()->toString(),
        ]);
    }

    private function readPrivate(object $object, string $property): mixed
    {
        $reflection = new \ReflectionClass($object);
        $reflectedProperty = $reflection->getProperty($property);
        $reflectedProperty->setAccessible(true);

        return $reflectedProperty->getValue($object);
    }
}
