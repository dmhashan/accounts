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
     * Build a storage path prefixed with `{APP_ENV}/{tenant_uuid}/`.
     * This ensures files are namespaced per environment and per tenant.
     */
    private function prefixedPath(string $path): string
    {
        $env        = app()->environment();
        $tenantUuid = app('tenant')->tenant_uuid;

        return $env . '/' . $tenantUuid . '/' . ltrim($path, '/');
    }

    /**
     * Store an uploaded file under the given directory and return the stored path.
     * The path is automatically prefixed with `{APP_ENV}/{tenant_uuid}/`.
     * Visibility is intentionally omitted — bucket-level settings on R2 / LC control it.
     */
    public function store(UploadedFile $file, string $directory): string
    {
        $path = $file->store($this->prefixedPath($directory), $this->diskName());

        return (string) $path;
    }

    /**
     * Store raw string content at the given path and return the full stored path.
     * The path is automatically prefixed with `{APP_ENV}/{tenant_uuid}/`.
     */
    public function storeContent(string $content, string $path): string
    {
        $fullPath = $this->prefixedPath($path);
        $this->disk()->put($fullPath, $content);

        return $fullPath;
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
