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

    'media_disk' => env('MEDIA_DISK', 'local'),

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
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        'media_local' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => true,
            'report' => false,
        ],

        'media_ftp' => [
            'driver' => 'ftp',
            'host' => env('MEDIA_FTP_HOST'),
            'username' => env('MEDIA_FTP_USERNAME'),
            'password' => env('MEDIA_FTP_PASSWORD'),
            'port' => (int) env('MEDIA_FTP_PORT', 21),
            'root' => env('MEDIA_FTP_ROOT', ''),
            'passive' => filter_var(env('MEDIA_FTP_PASSIVE', true), FILTER_VALIDATE_BOOL),
            'ssl' => filter_var(env('MEDIA_FTP_SSL', false), FILTER_VALIDATE_BOOL),
            'timeout' => (int) env('MEDIA_FTP_TIMEOUT', 30),
            'url' => env('MEDIA_FTP_URL'),
            'visibility' => 'public',
            'throw' => true,
            'report' => false,
        ],

        'media_sftp' => [
            'driver' => 'sftp',
            'host' => env('MEDIA_SFTP_HOST'),
            'username' => env('MEDIA_SFTP_USERNAME'),
            'password' => env('MEDIA_SFTP_PASSWORD'),
            'privateKey' => env('MEDIA_SFTP_PRIVATE_KEY'),
            'passphrase' => env('MEDIA_SFTP_PASSPHRASE'),
            'port' => (int) env('MEDIA_SFTP_PORT', 22),
            'root' => env('MEDIA_SFTP_ROOT', ''),
            'timeout' => (int) env('MEDIA_SFTP_TIMEOUT', 30),
            'permissions' => [
                'file' => ['public' => 0644, 'private' => 0600],
                'dir' => ['public' => 0755, 'private' => 0700],
            ],
            'url' => env('MEDIA_SFTP_URL'),
            'visibility' => 'public',
            'throw' => true,
            'report' => false,
        ],

        'media_aliyun' => [
            'driver' => 'aliyun',
            'key' => env('ALIYUN_OSS_ACCESS_KEY_ID'),
            'secret' => env('ALIYUN_OSS_ACCESS_KEY_SECRET'),
            'bucket' => env('ALIYUN_OSS_BUCKET'),
            'endpoint' => env('ALIYUN_OSS_ENDPOINT'),
            'url' => env('ALIYUN_OSS_URL'),
            'throw' => true,
            'report' => false,
        ],

        'media_upyun' => [
            'driver' => 'upyun',
            'bucket' => env('UPYUN_BUCKET'),
            'operator' => env('UPYUN_OPERATOR'),
            'password' => env('UPYUN_PASSWORD'),
            'domain' => env('UPYUN_DOMAIN'),
            'protocol' => env('UPYUN_PROTOCOL', 'https'),
            'url' => env('UPYUN_DOMAIN'),
            'throw' => true,
            'report' => false,
        ],

        'media_qiniu' => [
            'driver' => 'qiniu',
            'key' => env('QINIU_ACCESS_KEY'),
            'secret' => env('QINIU_SECRET_KEY'),
            'bucket' => env('QINIU_BUCKET'),
            'domain' => env('QINIU_DOMAIN'),
            'url' => env('QINIU_DOMAIN'),
            'throw' => true,
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

        'media_aws' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
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
