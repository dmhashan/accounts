<?php

namespace App\Services;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Single wrapper for all media file operations.
 *
 * Resolves the configured media disk so no other class references
 * the disk name or calls Storage directly.
 *
 * URL strategy:
 *  - Public disk (local dev)  → permanent public URL via Storage::url()
 *  - Private R2 bucket (prod) → 1-hour pre-signed URL via Storage::temporaryUrl()
 *  - Public  R2 bucket (prod) → permanent public URL (requires AWS_URL to be set)
 */
class MediaStorageService
{
    private function disk(): FilesystemAdapter
    {
        /** @var FilesystemAdapter */
        return Storage::disk($this->diskName());
    }

    private function diskName(): string
    {
        return (string) config('filesystems.media_disk', 'public');
    }

    /**
     * Store an uploaded file under the given directory and return the stored path.
     * Visibility is intentionally omitted — bucket-level settings on R2 / LC control it.
     */
    public function store(UploadedFile $file, string $directory): string
    {
        $path = $file->store($directory, $this->diskName());

        return (string) $path;
    }

    /**
     * Delete a file by its stored path. Silently ignores missing files.
     */
    public function delete(string $path): void
    {
        $this->disk()->delete($path);
    }

    /**
     * Return a URL for the given stored path.
     *
     * When the disk has a base URL configured (public bucket / local public disk)
     * a permanent URL is returned.
     * When AWS_URL is empty (private R2 bucket), a pre-signed URL valid for
     * 1 hour is returned instead.
     */
    public function url(string $path): string
    {
        $disk = $this->disk();

        // If the disk has a base URL (public bucket or local public disk), use it.
        $baseUrl = config("filesystems.disks.{$this->diskName()}.url");
        if ($baseUrl) {
            return $disk->url($path);
        }

        // Private bucket — generate a pre-signed temporary URL (1 hour).
        return $disk->temporaryUrl($path, now()->addHour());
    }
}
