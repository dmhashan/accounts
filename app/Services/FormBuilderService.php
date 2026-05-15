<?php

namespace App\Services;

use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\Member;
use App\Models\MemberDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

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
            'tenant_id'   => $tenantId,
            'created_by'  => $createdBy,
            'title'       => trim($validated['title']),
            'description' => filled($validated['description'] ?? null) ? trim($validated['description']) : null,
            'fields'      => $this->normalizeFields($validated['fields'] ?? []),
            'is_active'   => $validated['is_active'] ?? true,
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
            'fields'      => $this->normalizeFields($validated['fields'] ?? []),
            'is_active'   => $validated['is_active'] ?? $template->is_active,
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
        array $responses
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
            'submitted_at'     => now(),
        ]);

        // Generate PDF, store it, and create a MemberDocument record
        try {
            [$pdfPath, $pdfSize, $pdfFilename] = $this->generateAndStorePdf($submission, $template, $member, $tenantId);
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
            [$pdfPath] = $this->generateAndStorePdf($submission, $submission->template, $submission->member, $tenantId);
            $submission->update(['pdf_path' => $pdfPath]);
        }

        return $this->media->url($submission->pdf_path);
    }

    // ─── PDF ──────────────────────────────────────────────────────────────────

    private function generateAndStorePdf(
        FormSubmission $submission,
        FormTemplate $template,
        Member $member,
        int $tenantId
    ): array {
        $memberName = trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) ?: ($member->name ?? 'Member');

        $pdf = Pdf::loadView('pdfs.form-submission', [
            'template'    => $template,
            'submission'  => $submission,
            'memberName'  => $memberName,
            'memberId'    => $member->member_id ?? '',
            'submittedAt' => $submission->submitted_at?->format('d M Y, H:i') ?? now()->format('d M Y, H:i'),
        ])->setPaper('a4');

        $content  = $pdf->output();
        $filename = Str::slug($template->title) . '-' . $submission->id . '.pdf';
        $path     = "members/{$tenantId}/{$member->id}/form-submissions/{$filename}";

        $this->media->storeContent($content, $path);

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
            $data['fields'] = $t->fields ?? [];
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
            'has_pdf'      => (bool) $s->pdf_path,
            'submitted_at' => $s->submitted_at?->format('d M Y, H:i'),
            'created_at'   => $s->created_at?->format('d M Y, H:i'),
        ];

        if ($withResponses) {
            $data['responses'] = $s->responses ?? [];
        }

        return $data;
    }
}
