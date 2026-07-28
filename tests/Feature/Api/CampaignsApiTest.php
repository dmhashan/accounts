<?php

namespace Tests\Feature\Api;

use App\Models\Campaign;
use App\Models\Member;
use App\Models\PaymentPlan;
use App\Services\CampaignService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CampaignsApiTest extends ApiRouteTestCase
{
    public function testCampaignCrudCreatesDraftAndPublishesPublicConfiguration(): void
    {
        $this->actingAsUser(['campaigns.view', 'campaigns.create', 'campaigns.publish']);

        $campaignId = (int) $this->postJson('/api/campaigns', [
            'title' => 'Summer Offer',
            'slug' => 'summer-offer',
            'description' => 'Public registration drive.',
        ])->assertCreated()
            ->assertJsonPath('message', 'Campaign created successfully.')
            ->json('data.id');

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaignId,
            'slug' => 'summer-offer',
            'status' => Campaign::STATUS_DRAFT,
        ]);

        $this->patchJson('/api/campaigns/' . $campaignId . '/status', [
            'status' => Campaign::STATUS_PUBLISHED,
        ])->assertOk()
            ->assertJsonPath('data.status', Campaign::STATUS_PUBLISHED);

        $this->getJson('/api/public/campaigns/summer-offer')
            ->assertOk()
            ->assertJsonPath('status', Campaign::STATUS_PUBLISHED)
            ->assertJsonPath('campaign.title', 'Summer Offer')
            ->assertJsonStructure(['campaign' => ['fields', 'documents', 'tenant']]);
    }

    public function testPublicRegistrationCreatesUnverifiedCampaignMemberAndDocuments(): void
    {
        Storage::fake(config('filesystems.media_disk', 'public'));

        $plan = $this->createPaymentPlan(['name' => 'Student Offer', 'price' => 2500]);
        $campaign = $this->createPublishedCampaign($plan, [
            [
                'key' => 'nic-document',
                'title' => 'NIC Document',
                'description' => 'Upload your ID.',
                'required' => true,
                'allowed_types' => ['png', 'jpg'],
                'max_size_mb' => 5,
                'multiple' => false,
            ],
        ]);

        $file = UploadedFile::fake()->image('nic.png');

        $this->post('/api/public/campaigns/' . $campaign->slug . '/register', [
            'fields' => [
                'name' => 'Nimali Perera',
                'phone_number' => '0771234567',
                'email' => 'nimali@example.com',
            ],
            'documents' => [
                'nic-document' => [$file],
            ],
        ])->assertCreated()
            ->assertJsonPath('message', 'Thank you for your registration. Your details have been submitted successfully. Our team will review your information and contact you soon.');

        $member = Member::query()->where('email', 'nimali@example.com')->firstOrFail();

        $this->assertFalse((bool) $member->is_verified);
        $this->assertSame('campaign', $member->registration_source);
        $this->assertSame($campaign->id, $member->campaign_id);
        $this->assertSame($plan->id, $member->payment_plan_id);
        $this->assertDatabaseHas('member_documents', [
            'member_id' => $member->id,
            'name' => 'NIC Document',
            'category' => 'identification',
            'original_filename' => 'nic.png',
        ]);
    }

    public function testClosedCampaignDoesNotAcceptRegistrations(): void
    {
        $campaign = $this->createPublishedCampaign();
        $campaign->update([
            'status' => Campaign::STATUS_CLOSED,
            'closed_at' => now(),
        ]);

        $this->getJson('/api/public/campaigns/' . $campaign->slug)
            ->assertOk()
            ->assertJsonPath('status', Campaign::STATUS_CLOSED)
            ->assertJsonPath('message', 'Sorry, this campaign has finished or is closed.');

        $this->postJson('/api/public/campaigns/' . $campaign->slug . '/register', [
            'fields' => ['name' => 'Closed Member', 'phone_number' => '0770000000'],
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Sorry, this campaign has finished or is closed.');
    }

    public function testCampaignVerifyPermissionOnlyVerifiesCampaignMembers(): void
    {
        $campaign = $this->createPublishedCampaign();
        $campaignMember = $this->createMember(null, [
            'campaign_id' => $campaign->id,
            'registration_source' => 'campaign',
            'is_verified' => false,
        ]);
        $regularMember = $this->createMember(null, ['is_verified' => false]);

        $this->actingAsUser(['users.view', 'campaigns.verify']);

        $this->patchJson('/api/members/' . $campaignMember->id . '/toggle-verification')
            ->assertOk()
            ->assertJsonPath('is_verified', true);

        $this->patchJson('/api/members/' . $regularMember->id . '/toggle-verification')
            ->assertForbidden();
    }

    private function createPublishedCampaign(?PaymentPlan $plan = null, array $documents = []): Campaign
    {
        $plan ??= $this->createPaymentPlan();
        $service = app(CampaignService::class);
        $fieldConfig = collect($service->defaultFieldConfig())
            ->map(function (array $row) use ($plan) {
                $row['visible'] = false;
                $row['required'] = false;
                $row['editable'] = false;
                $row['constant_value'] = null;

                if (in_array($row['field'], ['name', 'phone_number', 'email'], true)) {
                    $row['visible'] = true;
                    $row['editable'] = true;
                    $row['required'] = in_array($row['field'], ['name', 'phone_number'], true);
                }

                if ($row['field'] === 'gender') {
                    $row['required'] = true;
                    $row['constant_value'] = 'female';
                }

                if ($row['field'] === 'date_of_birth') {
                    $row['required'] = true;
                    $row['constant_value'] = '2000-01-01';
                }

                if ($row['field'] === 'joined_date') {
                    $row['required'] = true;
                    $row['constant_value'] = '__today__';
                }

                if ($row['field'] === 'payment_plan_id') {
                    $row['constant_value'] = $plan->id;
                }

                if ($row['field'] === 'allow_sms') {
                    $row['constant_value'] = '1';
                }

                return $row;
            })
            ->values()
            ->all();

        return Campaign::create([
            'title' => 'Campaign ' . uniqid(),
            'slug' => 'campaign-' . uniqid(),
            'description' => 'Registration campaign.',
            'status' => Campaign::STATUS_PUBLISHED,
            'field_config' => $fieldConfig,
            'document_config' => app(CampaignService::class)->normalizeDocumentConfig($documents),
            'published_at' => now(),
        ]);
    }
}
