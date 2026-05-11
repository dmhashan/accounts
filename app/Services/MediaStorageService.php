<?php

namespace App\Services;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Single wrapper for all media file operations.
 *
 * Resolves the configured media disk once so no other class
 * references the disk name or calls Storage directly.
 */
class MediaStorageService
{
    private function disk(): FilesystemAdapter
    {
        /** @var FilesystemAdapter */
        return Storage::disk(config('filesystems.media_disk', 'public'));
    }

    /**
     * Store an uploaded file under the given directory and return the stored path.
     */
    public function store(UploadedFile $file, string $directory): string
    {
        $path = $file->store($directory, [
            'disk'       => config('filesystems.media_disk', 'public'),
            'visibility' => 'public',
        ]);

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
     * Return the full public URL for a stored path.
     */
    public function url(string $path): string
    {
        return $this->disk()->url($path);
    }
}
