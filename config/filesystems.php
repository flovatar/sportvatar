<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],
        'localstorage' => [
            'driver' => 'local',
            'root' => storage_path(''),
        ],
        'localpublic' => [
            'driver' => 'local',
            'root' => public_path(''),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
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
        ],

        // Item image renders (App\Services\Render). Scoped R2 token (buckets flovatar, sportvatar, flovatar-web,
        // sportvatar-web; server IP only) - values live in the server .env only, never in git.
        'r2' => [
            'driver' => 's3',
            'key' => env('R2_ACCESS_KEY_ID'),
            'secret' => env('R2_SECRET_ACCESS_KEY'),
            'region' => 'auto',
            'bucket' => env('R2_IMAGES_BUCKET', 'sportvatar'),
            'endpoint' => env('R2_ENDPOINT'),
            'url' => env('RENDER_PUBLIC_URL', 'https://images.sportvatar.com'),
            'use_path_style_endpoint' => true,
            'throw' => true,
            // R2 answers over IPv6 too and PHP's curl prefers it, but the scoped token's IP filter only lists
            // the server's IPv4 address (403 AccessDenied over IPv6, while the aws CLI used IPv4 and worked).
            'http' => env('R2_FORCE_IPV4', true) ? ['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]] : [],
        ],

        // Static headless frontend (bucket sportvatar-web; served by the sportvatar-headless Worker). Same scoped token as 'r2'.
        'r2web' => [
            'driver' => 's3',
            'key' => env('R2_ACCESS_KEY_ID'),
            'secret' => env('R2_SECRET_ACCESS_KEY'),
            'region' => 'auto',
            'bucket' => env('R2_WEB_BUCKET', 'sportvatar-web'),
            'endpoint' => env('R2_ENDPOINT'),
            'use_path_style_endpoint' => true,
            'throw' => true,
            'http' => env('R2_FORCE_IPV4', true) ? ['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]] : [],
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
