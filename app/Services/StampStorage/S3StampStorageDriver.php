<?php

namespace App\Services\StampStorage;

use App\Contracts\StampImageStorageDriver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class S3StampStorageDriver implements StampImageStorageDriver
{
    public function __construct(
        protected string $disk = 's3',
        protected string $directory = 'stamps',
        protected ?string $publicBaseUrl = null,
        protected bool $useSignedUrl = false,
        protected int $signedUrlTtlMinutes = 60,
        protected string $visibility = 'public'
    ) {}

    public function store(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension() ?: ($file->guessExtension() ?: 'png');
        $filename = Str::ulid() . '.' . strtolower($extension);
        $directory = trim($this->directory, '/');

        $options = [
            'disk' => $this->disk,
            'visibility' => $this->visibility,
            'CacheControl' => 'public, max-age=31536000',
        ];

        return $file->storeAs($directory, $filename, $options);
    }

    public function url(string $storedValue): string
    {
        $cleanPath = ltrim($storedValue, '/');

        // CloudFront や Cloudflare R2 カスタムドメイン等の公開ベースURLが指定されている場合
        if ($this->publicBaseUrl) {
            return rtrim($this->publicBaseUrl, '/') . '/' . $cleanPath;
        }

        $disk = Storage::disk($this->disk);

        // S3 パブリックアクセスブロック有効時（署名付きURL発行モード）
        if ($this->useSignedUrl && method_exists($disk, 'temporaryUrl')) {
            return $disk->temporaryUrl($cleanPath, now()->addMinutes($this->signedUrlTtlMinutes));
        }

        return $disk->url($cleanPath);
    }

    public function delete(string $storedValue): void
    {
        if (
            str_starts_with($storedValue, 'data:')
            || str_starts_with($storedValue, 'http://')
            || str_starts_with($storedValue, 'https://')
        ) {
            return;
        }

        Storage::disk($this->disk)->delete(ltrim($storedValue, '/'));
    }
}

