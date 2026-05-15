<?php

namespace Database\Seeders;

use App\Models\FormTemplate;
use App\Models\Tenant;
use App\Services\FormBuilderService;
use Illuminate\Database\Seeder;

class ParqFormTemplateSeeder extends Seeder
{
    public function run(): void
    {
        /** @var FormBuilderService $formBuilder */
        $formBuilder = app(FormBuilderService::class);

        $fields = [
            [
                'type'     => 'heading',
                'label'    => 'Physical Activity Readiness Questionnaire (PAR-Q)',
                'required' => false,
            ],
            [
                'type'     => 'paragraph',
                'label'    => 'A Questionnaire for People Aged 15 to 69',
                'required' => false,
            ],
            [
                'type'     => 'paragraph',
                'label'    => 'Regular physical activity is fun and healthy, and increasingly more people are starting to become more active every day. Being more active is very safe for most people. However, some people should check with their doctor before they start becoming much more physically active.'
                    . "\n\n"
                    . 'If you are planning to become much more physically active than you are now, start by answering the seven questions below. If you are between the ages of 15 and 69, the PAR-Q will tell you if you should check with your doctor before you start. If you are over 69 years of age and are not used to being very active, check with your doctor.'
                    . "\n\n"
                    . 'Common sense is your best guide when you answer these questions. Please read the questions carefully and answer each one honestly: answer YES or NO.',
                'required' => false,
            ],
            [
                'type'     => 'radio',
                'label'    => '1. Has your doctor ever said that you have a heart condition AND that you should only do physical activity recommended by a doctor?',
                'required' => true,
                'options'  => ['Yes', 'No'],
            ],
            [
                'type'     => 'radio',
                'label'    => '2. Do you feel pain in your chest when you do physical activity?',
                'required' => true,
                'options'  => ['Yes', 'No'],
            ],
            [
                'type'     => 'radio',
                'label'    => '3. In the past month, have you had chest pain when you were not doing physical activity?',
                'required' => true,
                'options'  => ['Yes', 'No'],
            ],
            [
                'type'     => 'radio',
                'label'    => '4. Do you lose your balance because of dizziness, or do you ever lose consciousness?',
                'required' => true,
                'options'  => ['Yes', 'No'],
            ],
            [
                'type'     => 'radio',
                'label'    => '5. Do you have a bone or joint problem (for example, back, knee or hip) that could be made worse by a change in your physical activity?',
                'required' => true,
                'options'  => ['Yes', 'No'],
            ],
            [
                'type'     => 'radio',
                'label'    => '6. Is your doctor currently prescribing drugs (for example, water pills) for your blood pressure or heart condition?',
                'required' => true,
                'options'  => ['Yes', 'No'],
            ],
            [
                'type'     => 'radio',
                'label'    => '7. Do you know of ANY OTHER REASON why you should not do physical activity?',
                'required' => true,
                'options'  => ['Yes', 'No'],
            ],
            [
                'type'     => 'paragraph',
                'label'    => "If you answered YES to one or more questions:\nTalk to your doctor by phone or in person BEFORE you start becoming much more physically active or BEFORE you have a fitness appraisal. Tell your doctor about the PAR-Q and which questions you answered YES."
                    . "\n\n"
                    . "If you answered NO to all questions:\nYou can be reasonably sure that you can start becoming much more physically active — begin slowly and build up gradually. This is the safest and easiest way to go."
                    . "\n\n"
                    . 'NOTE: This physical activity clearance is valid for a maximum of 12 months from the date it is completed and becomes invalid if your condition changes so that you would answer YES to any of the seven questions.',
                'required' => false,
            ],
            [
                'type'     => 'paragraph',
                'label'    => '"I have read, understood and completed this questionnaire. Any questions I had were answered to my full satisfaction."',
                'required' => false,
            ],
            [
                'type'     => 'signature',
                'label'    => 'Member Signature',
                'required' => true,
            ],
            [
                'type'        => 'date',
                'label'       => 'Date',
                'placeholder' => '',
                'required'    => true,
            ],
        ];

        $data = [
            'title'       => 'Physical Activity Readiness Questionnaire (PAR-Q)',
            'description' => 'A pre-exercise screening questionnaire for people aged 15 to 69. Identifies individuals who may need medical clearance before beginning a fitness program.',
            'is_active'   => true,
            'fields'      => $fields,
        ];

        foreach (Tenant::all() as $tenant) {
            $existing = FormTemplate::where('tenant_id', $tenant->id)
                ->where('title', $data['title'])
                ->first();

            if ($existing) {
                $existing->update([
                    'fields'      => $formBuilder->normalizeFields($data['fields']),
                    'description' => $data['description'],
                    'is_active'   => $data['is_active'],
                ]);
                $this->command->line("  Updated [{$tenant->name}] — PAR-Q fields refreshed.");
                continue;
            }

            $formBuilder->storeTemplate($tenant->id, null, $data);
            $this->command->info("  Created PAR-Q template for [{$tenant->name}].");
        }
    }
}
