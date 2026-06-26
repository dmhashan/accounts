<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Media Disk
    |--------------------------------------------------------------------------
    |
    | The disk used for user-uploaded media (member profile photos, etc.).
    | Falls back to FILESYSTEM_DISK so Laravel Cloud environments work
    | automatically — LC sets FILESYSTEM_DISK=private, so no separate
    | MEDIA_DISK env var is required in production.
    | Locally: keep FILESYSTEM_DISK=local and MEDIA_DISK=public (default),
    | or set MEDIA_DISK=private to test against the real R2 bucket.
    |
    */
    'media_disk' => env('MEDIA_DISK', env('FILESYSTEM_DISK', 'public')),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

        /*
         | Laravel Cloud / Cloudflare R2 bucket disk.
         |
         | Laravel Cloud injects LARAVEL_CLOUD_DISK_CONFIG (not AWS_* vars).
         | The IIFE parses that JSON to build the S3 driver config at boot time.
         | Falls back to local disk when the env var is absent (local dev with
         | MEDIA_DISK=public has no need for this disk at all).
         */
        'private' => (static function (): array {
            $raw = (string) env('LARAVEL_CLOUD_DISK_CONFIG', '[]');
            $entries = json_decode($raw, true);
            $cfg = collect((array) $entries)->firstWhere('disk', 'private') ?? [];

            if (empty($cfg)) {
                return ['driver' => 'local', 'root' => storage_path('app/private')];
            }

            return [
                'driver' => 's3',
                'key' => $cfg['access_key_id'],
                'secret' => $cfg['access_key_secret'],
                'region' => $cfg['default_region'] ?? 'auto',
                'bucket' => $cfg['bucket'],
                'url' => $cfg['url'] ?: null,
                'endpoint' => $cfg['endpoint'],
                'use_path_style_endpoint' => $cfg['use_path_style_endpoint'] ?? false,
                'throw' => true,
                'report' => false,
            ];
        })(),

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
