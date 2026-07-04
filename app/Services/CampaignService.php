<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Member;
use App\Models\PaymentPlan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CampaignService
{
    private const TODAY_TOKEN = '__today__';

    public function __construct(
        private readonly MediaStorageService $media,
        private readonly MemberDocumentService $memberDocuments,
        private readonly AuditService $audit,
    ) {}

    public function fieldCatalog(): array
    {
        return [
            [
                'key' => 'first_name',
                'label' => 'First name',
                'group' => 'Personal details',
                'type' => 'text',
                'rules' => ['string', 'max:100'],
                'default_visible' => true,
                'default_required' => true,
                'default_editable' => true,
                'supports_constant' => true,
            ],
            [
                'key' => 'last_name',
                'label' => 'Last name',
                'group' => 'Personal details',
                'type' => 'text',
                'rules' => ['string', 'max:100'],
                'default_visible' => true,
                'default_required' => true,
                'default_editable' => true,
                'supports_constant' => true,
            ],
            [
                'key' => 'gender',
                'label' => 'Gender',
                'group' => 'Personal details',
                'type' => 'select',
                'rules' => ['in:male,female'],
                'options' => [
                    ['value' => 'male', 'label' => 'Male'],
                    ['value' => 'female', 'label' => 'Female'],
                ],
                'default_visible' => true,
                'default_required' => true,
                'default_editable' => true,
                'supports_constant' => true,
            ],
            [
                'key' => 'date_of_birth',
                'label' => 'Date of birth',
                'group' => 'Personal details',
                'type' => 'date',
                'rules' => ['date', 'before_or_equal:today'],
                'default_visible' => true,
                'default_required' => true,
                'default_editable' => true,
                'supports_constant' => true,
            ],
            [
                'key' => 'nic',
                'label' => 'NIC',
                'group' => 'Personal details',
                'type' => 'text',
                'rules' => ['string', 'max:50'],
                'default_visible' => true,
                'default_required' => false,
                'default_editable' => true,
                'supports_constant' => true,
            ],
            [
                'key' => 'phone_number',
                'label' => 'Phone number',
                'group' => 'Contact details',
                'type' => 'tel',
                'rules' => ['string', 'max:20'],
                'default_visible' => true,
                'default_required' => true,
                'default_editable' => true,
                'supports_constant' => true,
            ],
            [
                'key' => 'email',
                'label' => 'Email',
                'group' => 'Contact details',
                'type' => 'email',
                'rules' => ['email', 'max:255'],
                'default_visible' => true,
                'default_required' => false,
                'default_editable' => true,
                'supports_constant' => true,
            ],
            [
                'key' => 'allow_sms',
                'label' => 'Receives SMS',
                'group' => 'Contact preferences',
                'type' => 'boolean',
                'rules' => ['boolean'],
                'default_visible' => true,
                'default_required' => false,
                'default_editable' => true,
                'supports_constant' => true,
            ],
            [
                'key' => 'allow_whatsapp',
                'label' => 'Has WhatsApp',
                'group' => 'Contact preferences',
                'type' => 'boolean',
                'rules' => ['boolean'],
                'default_visible' => true,
                'default_required' => false,
                'default_editable' => true,
                'supports_constant' => true,
            ],
            [
                'key' => 'whatsapp_number',
                'label' => 'WhatsApp number',
                'group' => 'Contact preferences',
                'type' => 'tel',
                'rules' => ['string', 'max:20'],
                'default_visible' => false,
                'default_required' => false,
                'default_editable' => true,
                'supports_constant' => true,
            ],
            [
                'key' => 'address',
                'label' => 'Address',
                'group' => 'Contact details',
                'type' => 'textarea',
                'rules' => ['string', 'max:1000'],
                'default_visible' => true,
                'default_required' => false,
                'default_editable' => true,
                'supports_constant' => true,
            ],
            [
                'key' => 'payment_plan_id',
                'label' => 'Payment plan',
                'group' => 'Membership defaults',
                'type' => 'payment_plan',
                'rules' => ['integer', 'exists:payment_plans,id'],
                'default_visible' => false,
                'default_required' => false,
                'default_editable' => false,
                'supports_constant' => true,
            ],
            [
                'key' => 'admission_fee',
                'label' => 'Admission fee',
                'group' => 'Membership defaults',
                'type' => 'money',
                'rules' => ['numeric', 'min:0'],
                'default_visible' => false,
                'default_required' => false,
                'default_editable' => false,
                'supports_constant' => true,
            ],
            [
                'key' => 'joined_date',
                'label' => 'Joined date',
                'group' => 'Membership defaults',
                'type' => 'date',
                'rules' => ['date'],
                'default_visible' => false,
                'default_required' => true,
                'default_editable' => false,
                'default_constant_value' => self::TODAY_TOKEN,
                'supports_constant' => true,
            ],
            [
                'key' => 'comment',
                'label' => 'Comment',
                'group' => 'Other details',
                'type' => 'textarea',
                'rules' => ['string', 'max:2000'],
                'default_visible' => false,
                'default_required' => false,
                'default_editable' => true,
                'supports_constant' => true,
            ],
        ];
    }

    public function documentTypeOptions(): array
    {
        return [
            'pdf' => 'PDF',
            'jpg' => 'JPG',
            'jpeg' => 'JPEG',
            'png' => 'PNG',
            'webp' => 'WEBP',
            'doc' => 'DOC',
            'docx' => 'DOCX',
        ];
    }

    public function meta(): array
    {
        return [
            'field_catalog' => $this->fieldCatalogWithOptions(),
            'default_field_config' => $this->defaultFieldConfig(),
            'document_type_options' => $this->documentTypeOptions(),
            'today_token' => self::TODAY_TOKEN,
            'payment_plans' => PaymentPlan::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'price'])
                ->map(fn (PaymentPlan $plan) => [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'price' => (float) $plan->price,
                ])
                ->values()
                ->all(),
        ];
    }

    public function index(int $perPage, string $search = ''): array
    {
        $paginator = Campaign::query()
            ->withCount('members')
            ->when($search !== '', fn ($query) => $query
                ->where('title', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%"))
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return [
            'data' => $paginator->map(fn (Campaign $campaign) => $this->listItem($campaign))->values()->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    public function store(array $data, ?UploadedFile $coverImage, ?User $user): Campaign
    {
        $fieldConfig = $this->normalizeFieldConfig($this->parseArray($data['field_config'] ?? null));
        $documentConfig = $this->normalizeDocumentConfig($this->parseArray($data['document_config'] ?? null));

        $campaign = Campaign::create([
            'title' => trim($data['title']),
            'slug' => Str::slug($data['slug']),
            'description' => filled($data['description'] ?? null) ? trim((string) $data['description']) : null,
            'cover_image_path' => $coverImage ? $this->media->store($coverImage, 'campaigns/covers') : null,
            'status' => Campaign::STATUS_DRAFT,
            'field_config' => $fieldConfig,
            'document_config' => $documentConfig,
            'created_by' => $user?->id,
            'updated_by' => $user?->id,
        ]);

        $this->audit->log((int) app('tenant')->id, 'campaign.created', $campaign, null, $campaign->toArray());

        return $campaign;
    }

    public function update(Campaign $campaign, array $data, ?UploadedFile $coverImage, ?User $user): Campaign
    {
        $before = $campaign->toArray();
        $fieldConfig = $this->normalizeFieldConfig($this->parseArray($data['field_config'] ?? $campaign->field_config));
        $documentConfig = $this->normalizeDocumentConfig($this->parseArray($data['document_config'] ?? $campaign->document_config));

        $updates = [
            'title' => trim($data['title']),
            'slug' => Str::slug($data['slug']),
            'description' => filled($data['description'] ?? null) ? trim((string) $data['description']) : null,
            'field_config' => $fieldConfig,
            'document_config' => $documentConfig,
            'updated_by' => $user?->id,
        ];

        if ($coverImage) {
            if ($campaign->cover_image_path) {
                $this->media->delete($campaign->cover_image_path);
            }

            $updates['cover_image_path'] = $this->media->store($coverImage, 'campaigns/covers');
        }

        $campaign->update($updates);
        $campaign = $campaign->fresh();
        $this->audit->log((int) app('tenant')->id, 'campaign.updated', $campaign, $before, $campaign->toArray());

        return $campaign;
    }

    public function updateStatus(Campaign $campaign, string $status, ?User $user): Campaign
    {
        if ($status === Campaign::STATUS_PUBLISHED) {
            $this->assertPublishable($campaign);
        }

        if ($status === Campaign::STATUS_DRAFT && $campaign->status === Campaign::STATUS_PUBLISHED && $campaign->members()->exists()) {
            throw ValidationException::withMessages([
                'status' => ['Campaigns with registrations cannot be moved back to draft. Close the campaign instead.'],
            ]);
        }

        $before = $campaign->toArray();
        $updates = [
            'status' => $status,
            'updated_by' => $user?->id,
        ];

        if ($status === Campaign::STATUS_PUBLISHED) {
            $updates['published_at'] = $campaign->published_at ?: now();
            $updates['closed_at'] = null;
        } elseif ($status === Campaign::STATUS_CLOSED) {
            $updates['closed_at'] = now();
        }

        $campaign->update($updates);
        $campaign = $campaign->fresh();

        $action = match ($status) {
            Campaign::STATUS_PUBLISHED => $before['status'] === Campaign::STATUS_CLOSED ? 'campaign.reopened' : 'campaign.published',
            Campaign::STATUS_CLOSED => 'campaign.closed',
            default => 'campaign.status_updated',
        };

        $this->audit->log((int) app('tenant')->id, $action, $campaign, $before, $campaign->toArray());

        return $campaign;
    }

    public function destroy(Campaign $campaign): void
    {
        $before = $campaign->toArray();
        $campaign->delete();
        $this->audit->log((int) app('tenant')->id, 'campaign.deleted', $campaign, $before, null);
    }

    public function show(Campaign $campaign): array
    {
        $campaign->loadCount('members');

        return [
            ...$this->listItem($campaign),
            'description' => $campaign->description,
            'cover_image_url' => $campaign->cover_image_path ? $this->media->url($campaign->cover_image_path) : null,
            'field_config' => $this->normalizeFieldConfig($campaign->field_config),
            'document_config' => $this->normalizeDocumentConfig($campaign->document_config),
        ];
    }

    public function registrations(Campaign $campaign, int $perPage, string $search = ''): array
    {
        $paginator = Member::query()
            ->where('campaign_id', $campaign->id)
            ->withCount('documents')
            ->when($search !== '', fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone_number', 'like', "%{$search}%"))
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return [
            'data' => $paginator->map(fn (Member $member) => [
                'id' => $member->id,
                'biometric_member_id' => $member->biometric_member_id,
                'name' => $member->name ?: trim((string) $member->first_name . ' ' . (string) $member->last_name),
                'email' => $member->email,
                'phone_number' => $member->phone_number,
                'is_active' => (bool) $member->is_active,
                'is_verified' => (bool) $member->is_verified,
                'documents_count' => $member->documents_count ?? 0,
                'created_at' => $member->created_at?->toIso8601String(),
            ])->values()->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    public function publicCampaign(string $slug): ?array
    {
        $campaign = Campaign::query()
            ->where('slug', Str::slug($slug))
            ->first();

        if (!$campaign) {
            return null;
        }

        if ($campaign->status === Campaign::STATUS_DRAFT) {
            return null;
        }

        if ($campaign->status === Campaign::STATUS_CLOSED) {
            return [
                'status' => Campaign::STATUS_CLOSED,
                'message' => 'Sorry, this campaign has finished or is closed.',
                'campaign' => [
                    'title' => $campaign->title,
                    'slug' => $campaign->slug,
                    'description' => $campaign->description,
                    'cover_image_url' => $campaign->cover_image_path ? $this->media->url($campaign->cover_image_path) : null,
                    'tenant' => $this->tenantSummary(),
                ],
            ];
        }

        return [
            'status' => Campaign::STATUS_PUBLISHED,
            'campaign' => [
                'title' => $campaign->title,
                'slug' => $campaign->slug,
                'description' => $campaign->description,
                'cover_image_url' => $campaign->cover_image_path ? $this->media->url($campaign->cover_image_path) : null,
                'tenant' => $this->tenantSummary(),
                'fields' => $this->publicFields($campaign),
                'documents' => $this->normalizeDocumentConfig($campaign->document_config),
            ],
        ];
    }

    public function register(Campaign $campaign, Request $request): Member
    {
        if ($campaign->status !== Campaign::STATUS_PUBLISHED) {
            throw ValidationException::withMessages([
                'campaign' => ['This campaign is not accepting registrations.'],
            ]);
        }

        $fieldConfig = $this->normalizeFieldConfig($campaign->field_config);
        $documentConfig = $this->normalizeDocumentConfig($campaign->document_config);
        $fieldsInput = $request->input('fields', []);

        if (!is_array($fieldsInput)) {
            $fieldsInput = [];
        }

        $memberData = $this->validatedMemberData($fieldConfig, $fieldsInput);
        $documentFiles = $this->validatedDocumentFiles($documentConfig, $request);

        return DB::transaction(function () use ($campaign, $memberData, $documentConfig, $documentFiles) {
            $memberData['campaign_id'] = $campaign->id;
            $memberData['registration_source'] = 'campaign';
            $memberData['biometric_member_id'] = Member::generateBiometricMemberId((int) app('tenant')->id);
            $memberData['name'] = trim((string) ($memberData['first_name'] ?? '') . ' ' . (string) ($memberData['last_name'] ?? ''));
            $memberData['email'] = filled($memberData['email'] ?? null) ? trim((string) $memberData['email']) : null;
            $memberData['is_active'] = true;
            $memberData['is_verified'] = false;
            $memberData['is_temp'] = false;
            $memberData['current_balance'] = $memberData['current_balance'] ?? 0;
            $memberData['allow_sms'] = (bool) ($memberData['allow_sms'] ?? false);
            $memberData['allow_whatsapp'] = (bool) ($memberData['allow_whatsapp'] ?? false);
            $memberData['joined_date'] = $memberData['joined_date'] ?? now()->toDateString();

            if (!empty($memberData['payment_plan_id'])) {
                $plan = PaymentPlan::find($memberData['payment_plan_id']);

                if ($plan) {
                    $memberData['price'] = $plan->price;
                }
            }

            $member = Member::create($memberData);

            foreach ($documentConfig as $documentField) {
                foreach ($documentFiles[$documentField['key']] ?? [] as $file) {
                    $this->memberDocuments->store(
                        $member,
                        (int) app('tenant')->id,
                        null,
                        [
                            'name' => $documentField['title'],
                            'category' => $this->documentCategory($documentField['title']),
                            'notes' => trim('Campaign: ' . $campaign->title . "\n" . ($documentField['description'] ?? '')),
                        ],
                        $file,
                    );
                }
            }

            $this->audit->log((int) app('tenant')->id, 'campaign.member_registered', $member, null, [
                'member_id' => $member->id,
                'campaign_id' => $campaign->id,
                'campaign_title' => $campaign->title,
            ]);

            return $member;
        });
    }

    public function listItem(Campaign $campaign): array
    {
        return [
            'id' => $campaign->id,
            'title' => $campaign->title,
            'slug' => $campaign->slug,
            'status' => $campaign->status,
            'created_at' => $campaign->created_at?->toIso8601String(),
            'published_at' => $campaign->published_at?->toIso8601String(),
            'closed_at' => $campaign->closed_at?->toIso8601String(),
            'registrations_count' => $campaign->members_count ?? $campaign->members()->count(),
            'public_url' => url('/campaigns/' . $campaign->slug),
        ];
    }

    public function normalizeFieldConfig(mixed $config): array
    {
        $config = $this->parseArray($config);
        $byKey = collect($config)
            ->filter(fn ($item) => is_array($item) && isset($item['field']))
            ->keyBy('field');

        return collect($this->fieldCatalog())->map(function (array $definition, int $index) use ($byKey) {
            $existing = $byKey->get($definition['key'], []);
            $constantValue = array_key_exists('constant_value', $existing)
                ? $existing['constant_value']
                : ($definition['default_constant_value'] ?? null);
            $visible = $this->booleanValue($existing['visible'] ?? $definition['default_visible']);
            $editable = $visible
                ? $this->booleanValue($existing['editable'] ?? $definition['default_editable'])
                : false;
            $required = $this->booleanValue($existing['required'] ?? $definition['default_required']);

            if (!$visible && !$this->hasConstantValue($constantValue)) {
                $required = false;
            }

            if ($visible && !$editable && !$this->hasConstantValue($constantValue)) {
                $required = false;
            }

            return [
                'field' => $definition['key'],
                'visible' => $visible,
                'required' => $required,
                'editable' => $editable,
                'constant_value' => $constantValue,
                'sort_order' => (int) ($existing['sort_order'] ?? $index),
            ];
        })
            ->sortBy('sort_order')
            ->values()
            ->all();
    }

    public function normalizeDocumentConfig(mixed $config): array
    {
        $allowedExtensions = array_keys($this->documentTypeOptions());

        return collect($this->parseArray($config))
            ->filter(fn ($item) => is_array($item) && trim((string) ($item['title'] ?? '')) !== '')
            ->map(function (array $item, int $index) use ($allowedExtensions) {
                $title = trim((string) $item['title']);
                $key = trim((string) ($item['key'] ?? ''));

                if ($key === '') {
                    $key = Str::slug($title) ?: 'document-' . ($index + 1);
                }

                $allowedTypes = collect($item['allowed_types'] ?? ['pdf', 'jpg', 'jpeg', 'png'])
                    ->map(fn ($type) => strtolower(trim((string) $type)))
                    ->filter(fn ($type) => in_array($type, $allowedExtensions, true))
                    ->values()
                    ->all();

                if ($allowedTypes === []) {
                    $allowedTypes = ['pdf', 'jpg', 'jpeg', 'png'];
                }

                return [
                    'key' => Str::slug($key) ?: 'document-' . ($index + 1),
                    'title' => $title,
                    'description' => filled($item['description'] ?? null) ? trim((string) $item['description']) : null,
                    'required' => $this->booleanValue($item['required'] ?? false),
                    'allowed_types' => $allowedTypes,
                    'max_size_mb' => max(1, min((int) ($item['max_size_mb'] ?? 10), 25)),
                    'multiple' => $this->booleanValue($item['multiple'] ?? false),
                    'sort_order' => (int) ($item['sort_order'] ?? $index),
                ];
            })
            ->sortBy('sort_order')
            ->values()
            ->all();
    }

    public function defaultFieldConfig(): array
    {
        return $this->normalizeFieldConfig([]);
    }

    private function fieldCatalogWithOptions(): array
    {
        $plans = PaymentPlan::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (PaymentPlan $plan) => ['value' => $plan->id, 'label' => $plan->name])
            ->values()
            ->all();

        return collect($this->fieldCatalog())
            ->map(function (array $field) use ($plans) {
                unset($field['rules']);

                if ($field['type'] === 'payment_plan') {
                    $field['options'] = $plans;
                }

                return $field;
            })
            ->values()
            ->all();
    }

    private function assertPublishable(Campaign $campaign): void
    {
        $errors = [];

        foreach ($this->normalizeFieldConfig($campaign->field_config) as $row) {
            if ($row['required'] && !$row['visible'] && !$this->hasConstantValue($row['constant_value'])) {
                $errors["field_config.{$row['field']}"][] = 'Required hidden fields need a constant value.';
            }

            if ($row['required'] && $row['visible'] && !$row['editable'] && !$this->hasConstantValue($row['constant_value'])) {
                $errors["field_config.{$row['field']}"][] = 'Required read-only fields need a constant value.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function publicFields(Campaign $campaign): array
    {
        $definitions = collect($this->fieldCatalogWithOptions())->keyBy('key');

        return collect($this->normalizeFieldConfig($campaign->field_config))
            ->filter(fn (array $row) => $row['visible'])
            ->map(function (array $row) use ($definitions) {
                $definition = $definitions->get($row['field']);

                return [
                    'field' => $row['field'],
                    'label' => $definition['label'] ?? Str::headline($row['field']),
                    'group' => $definition['group'] ?? 'Details',
                    'type' => $definition['type'] ?? 'text',
                    'required' => (bool) $row['required'],
                    'editable' => (bool) $row['editable'],
                    'value' => $this->publicConstantValue($row['field'], $row['constant_value']),
                    'options' => $definition['options'] ?? [],
                    'sort_order' => $row['sort_order'],
                ];
            })
            ->values()
            ->all();
    }

    private function validatedMemberData(array $fieldConfig, array $fieldsInput): array
    {
        $definitions = collect($this->fieldCatalog())->keyBy('key');
        $data = [];
        $rules = [];
        $attributes = [];
        $configuredFields = collect($fieldConfig)->keyBy('field');

        foreach ($this->fieldCatalog() as $definition) {
            $field = $definition['key'];
            $row = $configuredFields->get($field);

            if (!$row) {
                continue;
            }

            $hasConstant = $this->hasConstantValue($row['constant_value']);
            $hasPublicInput = $row['visible'] && $row['editable'];

            if (!$hasConstant && !$hasPublicInput && !$row['required']) {
                continue;
            }

            $value = $hasConstant
                ? $this->normalizeFieldValue($field, $row['constant_value'])
                : $this->normalizeFieldValue($field, Arr::get($fieldsInput, $field));

            $data[$field] = $value;
            $rules[$field] = [
                $row['required'] ? 'required' : 'nullable',
                ...$definition['rules'],
            ];
            $attributes[$field] = strtolower($definition['label']);
        }

        if (isset($rules['email'])) {
            $rules['email'][] = Rule::unique('members', 'email');
            $rules['email'][] = Rule::unique('users', 'email');
        }

        $validator = Validator::make($data, $rules, [], $attributes);
        $validator->after(function ($validator) use ($data) {
            $firstName = trim((string) ($data['first_name'] ?? ''));
            $lastName = trim((string) ($data['last_name'] ?? ''));

            if ($firstName === '' && $lastName === '') {
                $validator->errors()->add('first_name', 'At least one name field is required.');
            }
        });

        return $validator->validate();
    }

    /**
     * @return array<string, array<int, UploadedFile>>
     */
    private function validatedDocumentFiles(array $documentConfig, Request $request): array
    {
        $errors = [];
        $filesByKey = [];

        foreach ($documentConfig as $documentField) {
            $key = $documentField['key'];
            $uploaded = $request->file("documents.{$key}", []);
            $files = $this->normalizeUploadedFiles($uploaded);

            if ($documentField['required'] && $files === []) {
                $errors["documents.{$key}"][] = "{$documentField['title']} is required.";
                continue;
            }

            if (!$documentField['multiple'] && count($files) > 1) {
                $errors["documents.{$key}"][] = "{$documentField['title']} allows only one file.";
            }

            foreach ($files as $index => $file) {
                $extension = strtolower($file->getClientOriginalExtension());

                if (!$file->isValid()) {
                    $errors["documents.{$key}.{$index}"][] = "{$documentField['title']} could not be uploaded.";
                    continue;
                }

                if (!in_array($extension, $documentField['allowed_types'], true)) {
                    $errors["documents.{$key}.{$index}"][] = "{$documentField['title']} must be one of: " . implode(', ', $documentField['allowed_types']) . '.';
                }

                if ($file->getSize() > ($documentField['max_size_mb'] * 1024 * 1024)) {
                    $errors["documents.{$key}.{$index}"][] = "{$documentField['title']} must be {$documentField['max_size_mb']} MB or smaller.";
                }
            }

            $filesByKey[$key] = $files;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $filesByKey;
    }

    /**
     * @return array<int, UploadedFile>
     */
    private function normalizeUploadedFiles(mixed $uploaded): array
    {
        if ($uploaded instanceof UploadedFile) {
            return [$uploaded];
        }

        if (!is_array($uploaded)) {
            return [];
        }

        return collect($uploaded)
            ->filter(fn ($file) => $file instanceof UploadedFile)
            ->values()
            ->all();
    }

    private function normalizeFieldValue(string $field, mixed $value): mixed
    {
        if ($value === self::TODAY_TOKEN) {
            return now()->toDateString();
        }

        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === '') {
            return null;
        }

        return match ($field) {
            'allow_sms', 'allow_whatsapp' => $this->booleanValue($value),
            'payment_plan_id' => $value === null ? null : (int) $value,
            'admission_fee' => $value === null ? null : (float) $value,
            default => $value,
        };
    }

    private function publicConstantValue(string $field, mixed $value): mixed
    {
        if (!$this->hasConstantValue($value)) {
            return null;
        }

        return $this->normalizeFieldValue($field, $value);
    }

    private function hasConstantValue(mixed $value): bool
    {
        return $value !== null && $value !== '';
    }

    private function booleanValue(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }

    private function parseArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function tenantSummary(): array
    {
        $tenant = app('tenant');

        return [
            'name' => $tenant->name,
            'domain' => $tenant->domain,
            'address' => $tenant->address,
            'email' => $tenant->email,
            'phone' => $tenant->phone,
            'logo_url' => $tenant->logo_path ? $this->media->url($tenant->logo_path) : null,
        ];
    }

    private function documentCategory(string $title): string
    {
        $normalized = strtolower($title);

        if (str_contains($normalized, 'medical')) {
            return 'medical';
        }

        if (str_contains($normalized, 'consent') || str_contains($normalized, 'contract')) {
            return 'contract';
        }

        if (str_contains($normalized, 'fitness')) {
            return 'fitness';
        }

        if (
            str_contains($normalized, 'nic')
            || str_contains($normalized, 'id')
            || str_contains($normalized, 'passport')
            || str_contains($normalized, 'student')
        ) {
            return 'identification';
        }

        return 'other';
    }
}
