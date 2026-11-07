<?php

use App\Services\StampStorage\DatabaseDataUriStorageDriver;
use App\Services\StampStorage\FilesystemDiskStorageDriver;
use App\Services\StampStorage\LaravelCloudObjectStorageDriver;
use App\Services\StampStorage\S3StampStorageDriver;

return [
    /*
    |--------------------------------------------------------------------------
    | スタンプ画像ストレージ ドライバ（サービス全体設定）
    |--------------------------------------------------------------------------
    | サーバレス構成（Laravel Cloud / Cloud Run 等）やローカル環境に合わせて、
    | スタンプ絵柄画像の保存先をプラグイン方式で切り替えられます。
    |
    | 組み込みドライバ:
    | - "public"        : ローカルディスク (storage/app/public/stamps)
    | - "laravel_cloud" : Laravel Cloud の Laravel Object Storage (Cloudflare R2ベース・自動ディスク解決対応)
    | - "s3"            : AWS S3 / S3互換ストレージ (CloudFront / 署名付きURL対応)
    | - "database"      : DBインライン保存 (Data URI化・外部バケット不要のサーバレス構成向け)
    |
    | ※ Laravel Cloud 上で Object Storage がアタッチされている場合（LARAVEL_CLOUD_DISK_CONFIG 存在時）は、
    |    STAMP_IMAGE_DRIVER 未指定でも自動的に "laravel_cloud" ドライバが選択されます。
    */
    'image_driver' => env(
        'STAMP_IMAGE_DRIVER',
        isset($_SERVER['LARAVEL_CLOUD_DISK_CONFIG']) ? 'laravel_cloud' : 'public'
    ),

    'drivers' => [
        'public' => [
            'driver' => FilesystemDiskStorageDriver::class,
            'disk' => 'public',
            'directory' => env('STAMP_IMAGE_DIRECTORY', 'stamps'),
            'public_base_url' => env('STAMP_IMAGE_PUBLIC_URL'),
        ],

        'laravel_cloud' => [
            'driver' => LaravelCloudObjectStorageDriver::class,
            'disk' => env('STAMP_IMAGE_CLOUD_DISK'), // 未指定時は LARAVEL_CLOUD_DISK_CONFIG のデフォルトディスクを自動選択
            'directory' => env('STAMP_IMAGE_DIRECTORY', 'stamps'),
            'public_base_url' => env('STAMP_IMAGE_PUBLIC_URL'),
            'use_signed_url' => (bool) env('STAMP_IMAGE_CLOUD_SIGNED_URL', false),
            'signed_url_ttl_minutes' => (int) env('STAMP_IMAGE_CLOUD_SIGNED_TTL', 60),
            'visibility' => env('STAMP_IMAGE_CLOUD_VISIBILITY', 'public'),
        ],

        's3' => [
            'driver' => S3StampStorageDriver::class,
            'disk' => env('STAMP_IMAGE_S3_DISK', 's3'),
            'directory' => env('STAMP_IMAGE_DIRECTORY', 'stamps'),
            'public_base_url' => env('STAMP_IMAGE_PUBLIC_URL'),
            'use_signed_url' => (bool) env('STAMP_IMAGE_S3_SIGNED_URL', false),
            'signed_url_ttl_minutes' => (int) env('STAMP_IMAGE_S3_SIGNED_TTL', 60),
            'visibility' => env('STAMP_IMAGE_S3_VISIBILITY', 'public'),
        ],

        'database' => [
            'driver' => DatabaseDataUriStorageDriver::class,
        ],
    ],
];
