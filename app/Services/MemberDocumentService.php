<?php

namespace App\Services;

use App\Models\Member;
use App\Models\MemberDocument;
use Illuminate\Http\UploadedFile;

class MemberDocumentService
{
    public const CATEGORIES = ['medical', 'identification', 'contract', 'fitness', 'other'];

    public const ALLOWED_MIMES = [
        'application/pdf',
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp',
        'image/gif',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
    ];

    public const MAX_FILE_SIZE_KB = 10240; // 10 MB

    public function __construct(private readonly MediaStorageService $media) {}

    public function index(Member $member, int $tenantId): array
    {
        $documents = MemberDocument::query()
            ->where('tenant_id', $tenantId)
            ->where('member_id', $member->id)
            ->with('uploader:id,name')
            ->orderByDesc('created_at')
            ->get();

        return [
            'data' => $documents->map(fn (MemberDocument $doc) => $this->serialize($doc))->values()->all(),
        ];
    }

    public function store(Member $member, int $tenantId, ?int $uploadedBy, array $validated, UploadedFile $file): MemberDocument
    {
        $path = $this->media->store(
            $file,
            "members/{$tenantId}/{$member->id}/documents"
        );

        return MemberDocument::create([
            'tenant_id'         => $tenantId,
            'member_id'         => $member->id,
            'uploaded_by'       => $uploadedBy,
            'name'              => trim($validated['name']),
            'category'          => $validated['category'] ?? 'other',
            'path'              => $path,
            'mime_type'         => $file->getMimeType(),
            'file_size'         => $file->getSize(),
            'original_filename' => $file->getClientOriginalName(),
            'notes'             => filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null,
        ]);
    }

    public function url(MemberDocument $document): string
    {
        return $this->media->url($document->path);
    }

    public function destroy(MemberDocument $document, int $tenantId): void
    {
        if ($document->tenant_id !== $tenantId) {
            abort(404);
        }

        $this->media->delete($document->path);
        $document->delete();
    }

    public function serialize(MemberDocument $doc): array
    {
        return [
            'id'                => $doc->id,
            'name'              => $doc->name,
            'category'          => $doc->category,
            'mime_type'         => $doc->mime_type,
            'file_size'         => $doc->file_size,
            'original_filename' => $doc->original_filename,
            'notes'             => $doc->notes,
            'uploaded_by'       => $doc->uploader ? ['id' => $doc->uploader->id, 'name' => $doc->uploader->name] : null,
            'created_at'        => optional($doc->created_at)->format('d M Y, H:i'),
        ];
    }
}
