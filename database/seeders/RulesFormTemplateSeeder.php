<?php

namespace Database\Seeders;

use App\Models\FormTemplate;
use App\Models\Tenant;
use App\Services\FormBuilderService;
use Illuminate\Database\Seeder;

class RulesFormTemplateSeeder extends Seeder
{
    // Stable field IDs so the seeder is idempotent on re-runs
    private const F_HEADING = 'corex-rules-heading';

    private const F_SEC_GENERAL = 'corex-rules-sec-general';

    private const F_PARA_GENERAL = 'corex-rules-para-general';

    private const F_SEC_EQUIPMENT = 'corex-rules-sec-equipment';

    private const F_PARA_EQUIPMENT = 'corex-rules-para-equipment';

    private const F_SEC_SAFETY = 'corex-rules-sec-safety';

    private const F_PARA_SAFETY = 'corex-rules-para-safety';

    private const F_SEC_TRAINING = 'corex-rules-sec-training';

    private const F_PARA_TRAINING = 'corex-rules-para-training';

    private const F_SEC_CLEANLINESS = 'corex-rules-sec-cleanliness';

    private const F_PARA_CLEANLINESS = 'corex-rules-para-cleanliness';

    private const F_SEC_MEMBERSHIP = 'corex-rules-sec-membership';

    private const F_PARA_MEMBERSHIP = 'corex-rules-para-membership';

    private const F_SEC_PHOTOGRAPHY = 'corex-rules-sec-photography';

    private const F_PARA_PHOTOGRAPHY = 'corex-rules-para-photography';

    private const F_SEC_TIMINGS = 'corex-rules-sec-timings';

    private const F_PARA_TIMINGS = 'corex-rules-para-timings';

    private const F_PARA_FINAL = 'corex-rules-para-final';

    private const F_PARA_DECL = 'corex-rules-para-decl';

    private const F_SIGNATURE = 'corex-rules-signature';

    private const F_DATE = 'corex-rules-date';

    public function run(): void
    {
        /** @var FormBuilderService $formBuilder */
        $formBuilder = app(FormBuilderService::class);

        $fields = [
            [
                'id' => self::F_HEADING,
                'type' => 'heading',
                'label' => 'Rules & Regulations',
                'required' => false,
            ],

            // ── General Rules ─────────────────────────────────────────────────
            [
                'id' => self::F_SEC_GENERAL,
                'type' => 'heading',
                'label' => 'General Rules',
                'required' => false,
            ],
            [
                'id' => self::F_PARA_GENERAL,
                'type' => 'paragraph',
                'label' => "1. All members must treat staff, trainers, and other members with respect.\n"
                    . "2. Biometric access control (fingerprint or face ID) must be used for entry to the gym.\n"
                    . "3. Proper gym attire and clean sports shoes are required at all times.\n"
                    . "4. Members should maintain personal hygiene and use deodorant when training.\n"
                    . "5. Smoking, alcohol, vaping, and illegal substances are strictly prohibited inside the gym premises.\n"
                    . '6. Any form of harassment, discrimination, or inappropriate behavior will result in immediate membership termination.',
                'required' => false,
            ],

            // ── Equipment Usage ───────────────────────────────────────────────
            [
                'id' => self::F_SEC_EQUIPMENT,
                'type' => 'heading',
                'label' => 'Equipment Usage',
                'required' => false,
            ],
            [
                'id' => self::F_PARA_EQUIPMENT,
                'type' => 'paragraph',
                'label' => "1. Wipe down equipment after use.\n"
                    . "2. Return dumbbells, plates, and accessories to their designated places after workouts.\n"
                    . "3. Do not misuse or intentionally damage gym equipment.\n"
                    . "4. Members should allow others to share equipment during busy hours.\n"
                    . '5. Report damaged equipment immediately to gym staff.',
                'required' => false,
            ],

            // ── Safety Regulations ────────────────────────────────────────────
            [
                'id' => self::F_SEC_SAFETY,
                'type' => 'heading',
                'label' => 'Safety Regulations',
                'required' => false,
            ],
            [
                'id' => self::F_PARA_SAFETY,
                'type' => 'paragraph',
                'label' => "1. Members train at their own risk.\n"
                    . "2. Beginners are encouraged to seek guidance from trainers before using equipment.\n"
                    . "3. Warm-up and stretching are recommended before workouts.\n"
                    . '4. Do not attempt dangerous lifts without proper support or supervision.',
                'required' => false,
            ],

            // ── Training Sessions ─────────────────────────────────────────────
            [
                'id' => self::F_SEC_TRAINING,
                'type' => 'heading',
                'label' => 'Training Sessions',
                'required' => false,
            ],
            [
                'id' => self::F_PARA_TRAINING,
                'type' => 'paragraph',
                'label' => "1. Personal training sessions, spotted training, and group training sessions must be booked in advance.\n"
                    . "2. Members must arrive on time for all scheduled training sessions.\n"
                    . '3. Trainers\' instructions must be followed during all training sessions.',
                'required' => false,
            ],

            // ── Cleanliness & Facilities ──────────────────────────────────────
            [
                'id' => self::F_SEC_CLEANLINESS,
                'type' => 'heading',
                'label' => 'Cleanliness & Facilities',
                'required' => false,
            ],
            [
                'id' => self::F_PARA_CLEANLINESS,
                'type' => 'paragraph',
                'label' => "1. Keep the gym clean and dispose of trash properly.\n"
                    . '2. Towels are recommended during workouts.',
                'required' => false,
            ],

            // ── Membership Policies ───────────────────────────────────────────
            [
                'id' => self::F_SEC_MEMBERSHIP,
                'type' => 'heading',
                'label' => 'Membership Policies',
                'required' => false,
            ],
            [
                'id' => self::F_PARA_MEMBERSHIP,
                'type' => 'paragraph',
                'label' => "1. Membership fees are non-refundable and non-transferable.\n"
                    . "2. Monthly memberships must be renewed on or before the due date.\n"
                    . "3. Management reserves the right to suspend or cancel memberships for rule violations.\n"
                    . '4. Members must inform management of any medical conditions before training.',
                'required' => false,
            ],

            // ── Photography & Social Media ────────────────────────────────────
            [
                'id' => self::F_SEC_PHOTOGRAPHY,
                'type' => 'heading',
                'label' => 'Photography & Social Media',
                'required' => false,
            ],
            [
                'id' => self::F_PARA_PHOTOGRAPHY,
                'type' => 'paragraph',
                'label' => "1. Respect others' privacy when taking photos or videos.\n"
                    . '2. Commercial photography or recording requires management approval.',
                'required' => false,
            ],

            // ── Gym Timings ───────────────────────────────────────────────────
            [
                'id' => self::F_SEC_TIMINGS,
                'type' => 'heading',
                'label' => 'Gym Timings',
                'required' => false,
            ],
            [
                'id' => self::F_PARA_TIMINGS,
                'type' => 'paragraph',
                'label' => '1. Members must complete workouts before closing time.',
                'required' => false,
            ],

            // ── Final Note ────────────────────────────────────────────────────
            [
                'id' => self::F_PARA_FINAL,
                'type' => 'paragraph',
                'label' => 'Management reserves the right to update rules and regulations at any time to ensure the safety, cleanliness, and positive environment of the Fitness Center.',
                'required' => false,
            ],

            // ── Declaration ───────────────────────────────────────────────────
            [
                'id' => self::F_PARA_DECL,
                'type' => 'paragraph',
                'label' => '"I have read, understood, and agree to abide by all the Rules & Regulations of the Fitness Center. I acknowledge that failure to comply may result in suspension or termination of my membership."',
                'required' => false,
            ],

            // ── Signature & Date ──────────────────────────────────────────────
            [
                'id' => self::F_SIGNATURE,
                'type' => 'signature',
                'label' => 'Member Signature',
                'required' => true,
            ],
            [
                'id' => self::F_DATE,
                'type' => 'date',
                'label' => 'Date',
                'placeholder' => '',
                'required' => true,
            ],
        ];

        $data = [
            'title' => 'Rules & Regulations',
            'description' => 'Member acknowledgement form for the Fitness Center rules and regulations. Members must sign to confirm they have read and agree to comply with all gym policies.',
            'is_active' => true,
            'fields' => $fields,
            'translations' => [],
        ];

        foreach (Tenant::all() as $tenant) {
            $existing = FormTemplate::where('title', $data['title'])
                ->first();

            if ($existing) {
                $existing->update([
                    'fields' => $formBuilder->normalizeFields($data['fields']),
                    'description' => $data['description'],
                    'is_active' => $data['is_active'],
                ]);
                $this->command->line("  Updated [{$tenant->name}] — Rules fields refreshed.");
                continue;
            }

            $formBuilder->storeTemplate($tenant->id, null, $data);
            $this->command->info("  Created Rules & Regulations template for [{$tenant->name}].");
        }
    }
}
