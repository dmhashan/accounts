<?php

namespace Tests\Feature\Api;

use App\Models\MemberDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MemberDocumentsApiTest extends ApiRouteTestCase
{
    public function testDocumentUploadListUrlAndDeleteUseTenantNamespacedStorage(): void
    {
        $disk = (string) config('filesystems.media_disk', 'public');
        Storage::fake($disk);
        $user = $this->actingAsUser(['users.view', 'users.edit']);
        $member = $this->createMember();

        $documentId = (int) $this->postJson('/api/members/' . $member->id . '/documents', [
            'file' => UploadedFile::fake()->createWithContent('health.txt', 'member health notes'),
            'name' => '  Health Notes  ',
            'category' => 'medical',
            'notes' => '  Private notes  ',
        ])->assertCreated()
            ->assertJsonPath('data.name', 'Health Notes')
            ->assertJsonPath('data.notes', 'Private notes')
            ->assertJsonPath('data.uploaded_by.id', $user->id)
            ->json('data.id');

        $document = MemberDocument::findOrFail($documentId);
        $this->assertStringContainsString($this->tenant->tenant_uuid, $document->path);
        Storage::disk($disk)->assertExists($document->path);

        $this->getJson('/api/members/' . $member->id . '/documents')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $documentId);

        $this->getJson('/api/members/' . $member->id . '/documents/' . $documentId . '/url')
            ->assertOk()
            ->assertJsonStructure(['url']);

        $this->deleteJson('/api/members/' . $member->id . '/documents/' . $documentId)
            ->assertOk();

        Storage::disk($disk)->assertMissing($document->path);
        $this->assertDatabaseMissing('member_documents', ['id' => $documentId]);
    }

    public function testDocumentsFromAnotherMemberAreNotAccessible(): void
    {
        $this->actingAsUser(['users.view', 'users.edit']);
        $member = $this->createMember();
        $otherMember = $this->createMember();

        $otherMemberDocument = $this->createDocument($otherMember->id);

        $this->getJson('/api/members/' . $member->id . '/documents/' . $otherMemberDocument->id . '/url')
            ->assertNotFound();
        $this->deleteJson('/api/members/' . $member->id . '/documents/' . $otherMemberDocument->id)
            ->assertNotFound();
    }

    private function createDocument(int $memberId): MemberDocument
    {
        return MemberDocument::create([
            'member_id' => $memberId,
            'name' => 'Private Document',
            'category' => 'other',
            'path' => 'documents/private.txt',
            'mime_type' => 'text/plain',
            'file_size' => 10,
            'original_filename' => 'private.txt',
        ]);
    }
}
