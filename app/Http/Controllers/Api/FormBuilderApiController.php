<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\Member;
use App\Services\FormBuilderService;
use App\Services\MemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FormBuilderApiController extends Controller
{
    public function __construct(
        private readonly FormBuilderService $formService,
        private readonly MemberService $memberService,
    ) {}

    // ─── Templates ────────────────────────────────────────────────────────────

    public function indexTemplates(Request $request): JsonResponse
    {
        $tenantId = app('tenant')->id;

        return response()->json($this->formService->listTemplates($tenantId));
    }

    public function showTemplate(FormTemplate $template): JsonResponse
    {
        $tenantId = app('tenant')->id;

        if ($template->tenant_id !== $tenantId) {
            abort(404);
        }

        return response()->json($this->formService->showTemplate($template));
    }

    public function storeTemplate(Request $request): JsonResponse
    {
        $tenantId = app('tenant')->id;

        $validated = $request->validate([
            'title'                               => ['required', 'string', 'max:255'],
            'description'                         => ['nullable', 'string', 'max:2000'],
            'is_active'                           => ['boolean'],
            'fields'                              => ['required', 'array'],
            'fields.*.type'                       => ['required', 'string', 'in:' . implode(',', FormBuilderService::FIELD_TYPES)],
            'fields.*.label'                      => ['required', 'string', 'max:255'],
            'fields.*.required'                   => ['boolean'],
            'fields.*.options'                    => ['array'],
            'fields.*.options.*'                  => ['string', 'max:255'],
            'translations'                        => ['nullable', 'array'],
            'translations.*'                      => ['array'],
            'translations.*.title'                => ['nullable', 'string', 'max:255'],
            'translations.*.description'          => ['nullable', 'string', 'max:2000'],
            'translations.*.fields'               => ['nullable', 'array'],
            'translations.*.fields.*'             => ['nullable', 'array'],
            'translations.*.fields.*.label'       => ['nullable', 'string', 'max:255'],
            'translations.*.fields.*.placeholder' => ['nullable', 'string', 'max:255'],
            'translations.*.fields.*.options'     => ['nullable', 'array'],
            'translations.*.fields.*.options.*'   => ['nullable', 'string', 'max:255'],
        ]);

        $template = $this->formService->storeTemplate($tenantId, $request->user()?->id, $validated);

        return response()->json([
            'message' => 'Form template created.',
            'data'    => $this->formService->showTemplate($template),
        ], 201);
    }

    public function updateTemplate(Request $request, FormTemplate $template): JsonResponse
    {
        $tenantId = app('tenant')->id;

        if ($template->tenant_id !== $tenantId) {
            abort(404);
        }

        $validated = $request->validate([
            'title'                               => ['required', 'string', 'max:255'],
            'description'                         => ['nullable', 'string', 'max:2000'],
            'is_active'                           => ['boolean'],
            'fields'                              => ['required', 'array'],
            'fields.*.type'                       => ['required', 'string', 'in:' . implode(',', FormBuilderService::FIELD_TYPES)],
            'fields.*.label'                      => ['required', 'string', 'max:255'],
            'fields.*.required'                   => ['boolean'],
            'fields.*.options'                    => ['array'],
            'fields.*.options.*'                  => ['string', 'max:255'],
            'translations'                        => ['nullable', 'array'],
            'translations.*'                      => ['array'],
            'translations.*.title'                => ['nullable', 'string', 'max:255'],
            'translations.*.description'          => ['nullable', 'string', 'max:2000'],
            'translations.*.fields'               => ['nullable', 'array'],
            'translations.*.fields.*'             => ['nullable', 'array'],
            'translations.*.fields.*.label'       => ['nullable', 'string', 'max:255'],
            'translations.*.fields.*.placeholder' => ['nullable', 'string', 'max:255'],
            'translations.*.fields.*.options'     => ['nullable', 'array'],
            'translations.*.fields.*.options.*'   => ['nullable', 'string', 'max:255'],
        ]);

        $template = $this->formService->updateTemplate($template, $tenantId, $validated);

        return response()->json([
            'message' => 'Form template updated.',
            'data'    => $this->formService->showTemplate($template),
        ]);
    }

    public function destroyTemplate(FormTemplate $template): JsonResponse
    {
        $tenantId = app('tenant')->id;

        $this->formService->destroyTemplate($template, $tenantId);

        return response()->json(['message' => 'Form template deleted.']);
    }

    // ─── Submissions ──────────────────────────────────────────────────────────

    public function indexSubmissions(Request $request, FormTemplate $template): JsonResponse
    {
        $tenantId = app('tenant')->id;

        if ($template->tenant_id !== $tenantId) {
            abort(404);
        }

        return response()->json($this->formService->listSubmissions($tenantId, templateId: $template->id));
    }

    public function memberSubmissions(Request $request, Member $member): JsonResponse
    {
        $tenantId = app('tenant')->id;
        $this->memberService->ensureTenantMember($member, $tenantId);

        return response()->json($this->formService->listSubmissions($tenantId, memberId: $member->id));
    }

    public function submitForm(Request $request, FormTemplate $template, Member $member): JsonResponse
    {
        $tenantId = app('tenant')->id;

        if ($template->tenant_id !== $tenantId) {
            abort(404);
        }

        $this->memberService->ensureTenantMember($member, $tenantId);

        $validated = $request->validate([
            'responses'   => ['required', 'array'],
            'responses.*' => ['nullable'],
            'language'    => ['nullable', 'string', 'max:10'],
        ]);

        $submission = $this->formService->submitForm(
            $template,
            $member,
            $tenantId,
            $request->user()?->id,
            $validated['responses'],
            $validated['language'] ?? 'en',
        );

        return response()->json([
            'message' => 'Form submitted successfully.',
            'data'    => $this->formService->showSubmission($submission, $tenantId),
        ], 201);
    }

    public function showSubmission(FormSubmission $submission): JsonResponse
    {
        $tenantId = app('tenant')->id;

        return response()->json($this->formService->showSubmission($submission, $tenantId));
    }

    public function submissionPdfUrl(FormSubmission $submission): JsonResponse
    {
        $tenantId = app('tenant')->id;

        return response()->json([
            'url' => $this->formService->pdfUrl($submission, $tenantId),
        ]);
    }

    public function destroySubmission(FormSubmission $submission): JsonResponse
    {
        $tenantId = app('tenant')->id;

        $this->formService->destroySubmission($submission, $tenantId);

        return response()->json(['message' => 'Submission deleted.']);
    }

    // ─── Active templates list (for member-facing form selector) ──────────────

    public function activeTemplates(): JsonResponse
    {
        $tenantId = app('tenant')->id;

        return response()->json($this->formService->listTemplates($tenantId, activeOnly: true));
    }
}
