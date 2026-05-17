<?php

namespace App\Services;

use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\Member;
use App\Models\MemberDocument;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

class FormBuilderService
{
    /** Supported field types */
    public const FIELD_TYPES = [
        'text',
        'textarea',
        'number',
        'date',
        'select',
        'checkbox',
        'radio',
        'heading',
        'paragraph',
        'signature',
    ];

    /** Allowed translation language codes */
    public const ALLOWED_LANGUAGES = ['en', 'si', 'ta', 'fr', 'de', 'es', 'pt', 'zh', 'ja', 'ar'];

    public function __construct(private readonly MediaStorageService $media) {}

    // ─── Templates ────────────────────────────────────────────────────────────

    public function listTemplates(int $tenantId, bool $activeOnly = false): array
    {
        $query = FormTemplate::where('tenant_id', $tenantId)
            ->with('creator:id,name')
            ->orderByDesc('created_at');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return [
            'data' => $query->get()->map(fn (FormTemplate $t) => $this->serializeTemplate($t, withFields: $activeOnly))->values()->all(),
        ];
    }

    public function showTemplate(FormTemplate $template): array
    {
        $template->loadMissing('creator:id,name');

        return $this->serializeTemplate($template, withFields: true);
    }

    public function storeTemplate(int $tenantId, ?int $createdBy, array $validated): FormTemplate
    {
        return FormTemplate::create([
            'tenant_id'    => $tenantId,
            'created_by'   => $createdBy,
            'title'        => trim($validated['title']),
            'description'  => filled($validated['description'] ?? null) ? trim($validated['description']) : null,
            'fields'       => $this->normalizeFields($validated['fields'] ?? []),
            'translations' => $this->normalizeTranslations($validated['translations'] ?? []),
            'is_active'    => $validated['is_active'] ?? true,
        ]);
    }

    public function updateTemplate(FormTemplate $template, int $tenantId, array $validated): FormTemplate
    {
        if ($template->tenant_id !== $tenantId) {
            abort(404);
        }

        $template->update([
            'title'       => trim($validated['title']),
            'description' => filled($validated['description'] ?? null) ? trim($validated['description']) : null,
            'fields'       => $this->normalizeFields($validated['fields'] ?? []),
            'translations' => $this->normalizeTranslations($validated['translations'] ?? []),
            'is_active'    => $validated['is_active'] ?? $template->is_active,
        ]);

        return $template->fresh();
    }

    public function destroyTemplate(FormTemplate $template, int $tenantId): void
    {
        if ($template->tenant_id !== $tenantId) {
            abort(404);
        }

        $template->delete();
    }

    // ─── Submissions ──────────────────────────────────────────────────────────

    public function listSubmissions(int $tenantId, ?int $memberId = null, ?int $templateId = null): array
    {
        $query = FormSubmission::where('tenant_id', $tenantId)
            ->with(['template:id,title', 'member:id,first_name,last_name,member_id', 'submitter:id,name'])
            ->orderByDesc('created_at');

        if ($memberId) {
            $query->where('member_id', $memberId);
        }

        if ($templateId) {
            $query->where('form_template_id', $templateId);
        }

        return [
            'data' => $query->get()->map(fn (FormSubmission $s) => $this->serializeSubmission($s))->values()->all(),
        ];
    }

    public function showSubmission(FormSubmission $submission, int $tenantId): array
    {
        if ($submission->tenant_id !== $tenantId) {
            abort(404);
        }

        $submission->loadMissing(['template', 'member:id,first_name,last_name,member_id', 'submitter:id,name']);

        return $this->serializeSubmission($submission, withResponses: true);
    }

    public function submitForm(
        FormTemplate $template,
        Member $member,
        int $tenantId,
        ?int $submittedBy,
        array $responses,
        string $language = 'en'
    ): FormSubmission {
        if ($template->tenant_id !== $tenantId) {
            abort(404);
        }

        $submission = FormSubmission::create([
            'tenant_id'        => $tenantId,
            'form_template_id' => $template->id,
            'member_id'        => $member->id,
            'submitted_by'     => $submittedBy,
            'responses'        => $responses,
            'language'         => in_array($language, self::ALLOWED_LANGUAGES, true) ? $language : 'en',
            'submitted_at'     => now(),
        ]);

        // Generate PDF, store it, and create a MemberDocument record
        try {
            [$pdfPath, $pdfSize, $pdfFilename] = $this->generateAndStorePdf($submission, $template, $member, $tenantId, app('tenant'));
            $submission->update(['pdf_path' => $pdfPath]);

            MemberDocument::create([
                'tenant_id'         => $tenantId,
                'member_id'         => $member->id,
                'uploaded_by'       => $submittedBy,
                'name'              => $template->title,
                'category'          => 'fitness',
                'path'              => $pdfPath,
                'mime_type'         => 'application/pdf',
                'file_size'         => $pdfSize,
                'original_filename' => $pdfFilename,
                'notes'             => 'Auto-generated from form submission #' . $submission->id,
            ]);
        } catch (\Throwable) {
            // PDF generation failure should not block submission
        }

        return $submission->fresh();
    }

    public function destroySubmission(FormSubmission $submission, int $tenantId): void
    {
        if ($submission->tenant_id !== $tenantId) {
            abort(404);
        }

        if ($submission->pdf_path) {
            // Delete the linked MemberDocument record and the file
            MemberDocument::where('tenant_id', $tenantId)
                ->where('path', $submission->pdf_path)
                ->delete();
            $this->media->delete($submission->pdf_path);
        }

        $submission->delete();
    }

    public function pdfUrl(FormSubmission $submission, int $tenantId): string
    {
        if ($submission->tenant_id !== $tenantId) {
            abort(404);
        }

        if (! $submission->pdf_path) {
            // Regenerate if missing
            $submission->loadMissing(['template', 'member']);
            [$pdfPath] = $this->generateAndStorePdf($submission, $submission->template, $submission->member, $tenantId, app('tenant'));
            $submission->update(['pdf_path' => $pdfPath]);
        }

        return $this->media->url($submission->pdf_path);
    }

    // ─── PDF ──────────────────────────────────────────────────────────────────

    private function generateAndStorePdf(
        FormSubmission $submission,
        FormTemplate $template,
        Member $member,
        int $tenantId,
        ?\App\Models\Tenant $tenant = null
    ): array {
        $memberName = trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) ?: ($member->name ?? 'Member');

        // Resolve field labels for the submission language
        $resolvedFields = $this->resolveFieldsForLanguage(
            $template->fields ?? [],
            $template->translations ?? [],
            $submission->language ?? 'en'
        );

        $lang = $submission->language ?? 'en';
        $isRtl = $lang === 'ar';

        // Font config per non-Latin script
        $scriptFonts = [
            'si' => ['key' => 'notosanssinhala', 'file' => 'NotoSansSinhala-OTL.ttf',    'otl' => true],
            'ta' => ['key' => 'notosanstamil',   'file' => 'NotoSansTamil-OTL.ttf',      'otl' => true],
            'ar' => ['key' => 'notosansarabic',  'file' => 'NotoSansArabic-Regular.ttf',  'otl' => false],
            'zh' => ['key' => 'notosanssc',      'file' => 'NotoSansSC-Regular.ttf',      'otl' => false],
            'ja' => ['key' => 'notosansjp',      'file' => 'NotoSansJP-Regular.ttf',      'otl' => false],
        ];
        $scriptFont = $scriptFonts[$lang] ?? null;

        // Build mPDF font config on top of defaults
        $defaultFontDirs = (new ConfigVariables())->getDefaults()['fontDir'];
        $defaultFontData = (new FontVariables())->getDefaults()['fontdata'];

        $fontDirs = array_merge($defaultFontDirs, [storage_path('fonts')]);
        $fontData = $defaultFontData;

        $defaultFont = 'dejavusans';
        $bodyFont    = 'dejavusans, sans-serif';

        if ($scriptFont) {
            $entry = [
                'R' => $scriptFont['file'],
                'B' => $scriptFont['file'],
                'I' => $scriptFont['file'],
            ];
            if ($scriptFont['otl']) {
                $entry['useOTL'] = 0xFF;
            }
            $fontData[$scriptFont['key']] = $entry;
            $defaultFont = $scriptFont['key'];
            $bodyFont    = "'{$scriptFont['key']}', dejavusans, sans-serif";
        }

        $html = view('pdfs.form-submission', [
            'template'       => $template,
            'submission'     => $submission,
            'resolvedFields' => $resolvedFields,
            'language'       => $lang,
            'bodyFont'       => $bodyFont,
            'isRtl'          => $isRtl,
            'memberName'     => $memberName,
            'memberId'       => $member->member_id ?? '',
            'submittedAt'    => $submission->submitted_at?->format('d M Y, H:i') ?? now()->format('d M Y, H:i'),
            'tenantName'     => $tenant->name ?? '',
            'tenantAddress'  => $tenant->address ?? '',
            'tenantEmail'    => $tenant->email ?? '',
            'tenantPhone'    => $tenant->phone ?? '',
            'tenantLogoBase64' => $this->resolveLogoBase64($tenant),
        ])->render();

        $mpdf = new Mpdf([
            'mode'         => 'utf-8',
            'format'       => 'A4',
            'fontDir'      => $fontDirs,
            'fontdata'     => $fontData,
            'default_font' => $defaultFont,
            'tempDir'      => storage_path('app/mpdf-tmp'),
        ]);

        if ($isRtl) {
            $mpdf->SetDirectionality('rtl');
        }

        $mpdf->WriteHTML($html);
        $content  = $mpdf->Output('', 'S');

        $filename = Str::slug($template->title) . '-' . $submission->id . '.pdf';
        $path     = $this->media->storeContent($content, "form-submissions/{$filename}");

        return [$path, strlen($content), $filename];
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function normalizeFields(array $fields): array
    {
        return array_values(array_map(function (array $field, int $order) {
            return [
                'id'          => $field['id'] ?? Str::uuid()->toString(),
                'type'        => $field['type'] ?? 'text',
                'label'       => trim($field['label'] ?? ''),
                'placeholder' => $field['placeholder'] ?? null,
                'required'    => (bool) ($field['required'] ?? false),
                'options'     => $field['options'] ?? [],   // for select/radio
                'order'       => $order,
            ];
        }, $fields, array_keys($fields)));
    }

    /**
     * Normalize and sanitize the translations map.
     * Shape: { lang_code: { title, description, fields: { field_id: { label, placeholder, options } } } }
     */
    public function normalizeTranslations(array $translations): array
    {
        $result = [];

        foreach ($translations as $lang => $data) {
            if (! in_array($lang, self::ALLOWED_LANGUAGES, true) || $lang === 'en') {
                continue;
            }

            $fields = [];
            foreach ($data['fields'] ?? [] as $fieldId => $ft) {
                $fields[(string) $fieldId] = [
                    'label'       => isset($ft['label'])       ? trim((string) $ft['label'])       : null,
                    'placeholder' => isset($ft['placeholder']) ? trim((string) $ft['placeholder']) : null,
                    'options'     => array_values(array_filter(
                        array_map(fn ($o) => trim((string) $o), $ft['options'] ?? []),
                        fn ($o) => $o !== ''
                    )),
                ];
            }

            $result[$lang] = [
                'title'       => isset($data['title'])       ? trim((string) $data['title'])       : null,
                'description' => isset($data['description']) ? trim((string) $data['description']) : null,
                'fields'      => $fields,
            ];
        }

        return $result;
    }

    /**
     * Merge translation overrides onto a fields array for the given language.
     * Falls back to the original English value for any missing translation.
     */
    private function resolveFieldsForLanguage(array $fields, array $translations, string $language): array
    {
        if ($language === 'en' || empty($translations[$language])) {
            return $fields;
        }

        $trans = $translations[$language]['fields'] ?? [];

        return array_map(function (array $field) use ($trans) {
            $ft = $trans[$field['id']] ?? null;
            if (! $ft) {
                return $field;
            }
            return array_merge($field, array_filter([
                'label'       => $ft['label']       ?: null,
                'placeholder' => $ft['placeholder'] ?: null,
                'options'     => ! empty($ft['options']) ? $ft['options'] : null,
            ], fn ($v) => $v !== null));
        }, $fields);
    }

    private function serializeTemplate(FormTemplate $t, bool $withFields = false): array
    {
        $data = [
            'id'          => $t->id,
            'title'       => $t->title,
            'description' => $t->description,
            'is_active'   => $t->is_active,
            'created_by'  => $t->creator ? ['id' => $t->creator->id, 'name' => $t->creator->name] : null,
            'created_at'  => $t->created_at?->format('d M Y'),
        ];

        if ($withFields) {
            $data['fields']       = $t->fields ?? [];
            $data['translations'] = $t->translations ?? [];
        }

        return $data;
    }

    private function serializeSubmission(FormSubmission $s, bool $withResponses = false): array
    {
        $data = [
            'id'           => $s->id,
            'template'     => $s->template ? ['id' => $s->template->id, 'title' => $s->template->title] : null,
            'member'       => $s->member ? [
                'id'        => $s->member->id,
                'name'      => trim(($s->member->first_name ?? '') . ' ' . ($s->member->last_name ?? '')) ?: ($s->member->name ?? ''),
                'member_id' => $s->member->member_id ?? '',
            ] : null,
            'submitted_by' => $s->submitter ? ['id' => $s->submitter->id, 'name' => $s->submitter->name] : null,
            'language'     => $s->language ?? 'en',
            'has_pdf'      => (bool) $s->pdf_path,
            'submitted_at' => $s->submitted_at?->format('d M Y, H:i'),
            'created_at'   => $s->created_at?->format('d M Y, H:i'),
        ];

        if ($withResponses) {
            $data['responses'] = $s->responses ?? [];
        }

        return $data;
    }

    /**
     * Return a data-URI (base64) for the tenant logo suitable for mPDF inline images.
     * Returns null if no logo is set or the file cannot be read.
     */
    private function resolveLogoBase64(?\App\Models\Tenant $tenant): ?string
    {
        if (! $tenant || ! $tenant->logo_path) {
            return null;
        }

        try {
            $diskName = config('filesystems.media_disk', 'public');
            $content  = Storage::disk($diskName)->get($tenant->logo_path);

            if (! $content) {
                return null;
            }

            $ext      = strtolower(pathinfo($tenant->logo_path, PATHINFO_EXTENSION));
            $mimeMap  = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'svg' => 'image/svg+xml'];
            $mime     = $mimeMap[$ext] ?? 'image/png';

            return 'data:' . $mime . ';base64,' . base64_encode($content);
        } catch (\Throwable) {
            return null;
        }
    }
}
