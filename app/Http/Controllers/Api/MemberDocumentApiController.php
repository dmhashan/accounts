<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\MemberDocument;
use App\Services\MemberDocumentService;
use App\Services\MemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberDocumentApiController extends Controller
{
    public function __construct(
        private readonly MemberDocumentService $documentService,
        private readonly MemberService $memberService,
    ) {}

    public function index(Member $member): JsonResponse
    {
        $tenantId = app('tenant')->id;
        $this->memberService->ensureTenantMember($member, $tenantId);

        return response()->json($this->documentService->index($member, $tenantId));
    }

    public function store(Request $request, Member $member): JsonResponse
    {
        $tenantId = app('tenant')->id;
        $this->memberService->ensureTenantMember($member, $tenantId);

        $allowedMimes = implode(',', MemberDocumentService::ALLOWED_MIMES);

        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png,webp,gif,doc,docx,xls,xlsx,txt',
                'max:' . MemberDocumentService::MAX_FILE_SIZE_KB,
            ],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:' . implode(',', MemberDocumentService::CATEGORIES)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $document = $this->documentService->store(
            $member,
            $tenantId,
            $request->user()?->id,
            $validated,
            $request->file('file'),
        );

        return response()->json([
            'message' => 'Document uploaded successfully.',
            'data' => $this->documentService->serialize($document->load('uploader')),
        ], 201);
    }

    public function url(Member $member, MemberDocument $document): JsonResponse
    {
        $tenantId = app('tenant')->id;
        $this->memberService->ensureTenantMember($member, $tenantId);

        if ($document->member_id !== $member->id) {
            abort(404);
        }

        return response()->json([
            'url' => $this->documentService->url($document),
        ]);
    }

    public function destroy(Member $member, MemberDocument $document): JsonResponse
    {
        $tenantId = app('tenant')->id;
        $this->memberService->ensureTenantMember($member, $tenantId);

        if ($document->member_id !== $member->id) {
            abort(404);
        }

        $this->documentService->destroy($document, $tenantId);

        return response()->json([
            'message' => 'Document deleted successfully.',
        ]);
    }
}
