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
    | The disk used for user-uploaded media (e.g. member profile photos).
    | Use "public" locally (no credentials needed, symlinked via storage:link).
    | Use "s3" (or your cloud disk) in production.
    |
    */

    'media_disk' => env('MEDIA_DISK', 'public'),

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
            'url' => env('APP_URL').'/storage',
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
         | Laravel Cloud bucket disk.
         |
         | Credentials are injected automatically by Laravel Cloud when you
         | attach a storage bucket to your environment. You can also supply
         | them manually via the variables below.
         |
         | Set MEDIA_DISK=cloud in your production environment to route all
         | member profile photo uploads through this disk.
         */
        'cloud' => [
            'driver' => 's3',
            'key' => env('CLOUD_STORAGE_KEY'),
            'secret' => env('CLOUD_STORAGE_SECRET'),
            'region' => env('CLOUD_STORAGE_REGION', 'us-east-1'),
            'bucket' => env('CLOUD_STORAGE_BUCKET'),
            'url' => env('CLOUD_STORAGE_URL'),
            'endpoint' => env('CLOUD_STORAGE_ENDPOINT'),
            'use_path_style_endpoint' => env('CLOUD_STORAGE_PATH_STYLE', true),
            'visibility' => 'public',
            'throw' => true,
            'report' => false,
        ],

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
