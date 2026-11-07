<?php

use App\Services\StampStorage\DatabaseDataUriStorageDriver;
use App\Services\StampStorage\FilesystemDiskStorageDriver;

return [
    /*
    |--------------------------------------------------------------------------
    | スタンプ画像ストレージ ドライバ（サービス全体設定）
    |--------------------------------------------------------------------------
    | サーバレス構成（Cloud Run / Laravel Cloud 等）やローカル環境に合わせて、
    | スタンプ絵柄画像の保存先をプラグイン方式で切り替えられます。
    |
    | 組み込みドライバ:
    | - "public"   : ローカルディスク (storage/app/public/stamps)
    | - "s3"       : 外部S3互換ストレージ (AWS S3 / Cloudflare R2 / GCS 等)
    | - "database" : DBインライン保存 (Data URI化・外部バケット不要のサーバレス構成向け)
    |
    | 独自プラグインを追加する場合は、App\Contracts\StampImageStorageDriver を実装した
    | クラスを下の 'drivers' 配列に登録し、STAMP_IMAGE_DRIVER で指定してください。
    */
    'image_driver' => env('STAMP_IMAGE_DRIVER', 'public'),

    'drivers' => [
        'public' => [
            'driver' => FilesystemDiskStorageDriver::class,
            'disk' => 'public',
            'directory' => env('STAMP_IMAGE_DIRECTORY', 'stamps'),
            'public_base_url' => env('STAMP_IMAGE_PUBLIC_URL'),
        ],

        's3' => [
            'driver' => FilesystemDiskStorageDriver::class,
            'disk' => env('STAMP_IMAGE_S3_DISK', 's3'),
            'directory' => env('STAMP_IMAGE_DIRECTORY', 'stamps'),
            'public_base_url' => env('STAMP_IMAGE_PUBLIC_URL'),
        ],

        'database' => [
            'driver' => DatabaseDataUriStorageDriver::class,
        ],
    ],
];

