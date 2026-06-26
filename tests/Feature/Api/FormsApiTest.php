<?php

namespace Tests\Feature\Api;

use App\Models\FormTemplate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

class FormsApiTest extends ApiRouteTestCase
{
    public function testFormTemplateCrudNormalizesFieldsAndTranslations(): void
    {
        $this->actingAsUser(['forms.manage']);

        $response = $this->postJson('/api/forms/templates', [
            'title' => '  PAR-Q Form  ',
            'description' => '  Health questionnaire  ',
            'is_active' => true,
            'fields' => [[
                'id' => 'health-question',
                'type' => 'radio',
                'label' => '  Are you healthy?  ',
                'required' => true,
                'options' => ['Yes', 'No'],
            ]],
            'translations' => [
                'si' => [
                    'title' => ' සෞඛ්‍ය පෝරමය ',
                    'fields' => [
                        'health-question' => ['label' => ' සෞඛ්‍ය සම්පන්නද? '],
                    ],
                ],
                'unsupported' => ['title' => 'Ignored'],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.title', 'PAR-Q Form')
            ->assertJsonPath('data.fields.0.label', 'Are you healthy?')
            ->assertJsonPath('data.translations.si.title', 'සෞඛ්‍ය පෝරමය');

        $templateId = (int) $response->json('data.id');
        $this->assertNull($response->json('data.translations.unsupported'));

        $this->getJson('/api/forms/templates')
            ->assertOk()
            ->assertJsonPath('data.0.id', $templateId);

        $this->getJson('/api/forms/templates/active')
            ->assertOk()
            ->assertJsonPath('data.0.fields.0.id', 'health-question');

        $this->putJson('/api/forms/templates/' . $templateId, [
            'title' => 'Updated Form',
            'is_active' => false,
            'fields' => [[
                'id' => 'health-question',
                'type' => 'text',
                'label' => 'Updated question',
                'required' => false,
            ]],
        ])->assertOk()
            ->assertJsonPath('data.title', 'Updated Form');

        $this->getJson('/api/forms/templates/active')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->deleteJson('/api/forms/templates/' . $templateId)
            ->assertOk()
            ->assertJsonPath('message', 'Form template deleted.');
    }

    public function testFormSubmissionIsListedAndTenantScoped(): void
    {
        Queue::fake();
        Storage::fake(config('filesystems.media_disk', 'public'));
        $this->actingAsUser(['forms.manage', 'users.edit']);
        $member = $this->createMember();
        $template = $this->createTemplate();

        $submissionId = (int) $this->postJson('/api/forms/templates/' . $template->id . '/members/' . $member->id . '/submit', [
            'responses' => ['health-question' => 'Yes'],
            'language' => 'si',
        ])->assertCreated()
            ->assertJsonPath('data.language', 'si')
            ->assertJsonPath('data.responses.health-question', 'Yes')
            ->json('data.id');

        $this->getJson('/api/forms/templates/' . $template->id . '/submissions')
            ->assertOk()
            ->assertJsonPath('data.0.id', $submissionId);

        $this->getJson('/api/members/' . $member->id . '/form-submissions')
            ->assertOk()
            ->assertJsonPath('data.0.id', $submissionId);

        $this->getJson('/api/forms/submissions/' . $submissionId)
            ->assertOk()
            ->assertJsonPath('responses.health-question', 'Yes');

        $this->deleteJson('/api/forms/submissions/' . $submissionId)
            ->assertOk()
            ->assertJsonPath('message', 'Submission deleted.');

        $this->assertDatabaseMissing('form_submissions', ['id' => $submissionId]);
    }

    private function createTemplate(array $attributes = []): FormTemplate
    {
        return FormTemplate::create(array_merge([
            'title' => 'Health Form',
            'fields' => [[
                'id' => 'health-question',
                'type' => 'radio',
                'label' => 'Are you healthy?',
                'required' => true,
                'options' => ['Yes', 'No'],
                'order' => 0,
            ]],
            'is_active' => true,
        ], $attributes));
    }
}
