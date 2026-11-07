<?php

namespace App\Services\StampStorage;

use App\Contracts\StampImageStorageDriver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LaravelCloudObjectStorageDriver implements StampImageStorageDriver
{
    public function __construct(
        protected ?string $disk = null,
        protected string $directory = 'stamps',
        protected ?string $publicBaseUrl = null,
        protected bool $useSignedUrl = false,
        protected int $signedUrlTtlMinutes = 60,
        protected ?string $visibility = 'public'
    ) {}

    /**
     * Laravel Cloud が自動注入する LARAVEL_CLOUD_DISK_CONFIG または設定値から対象ディスク名を解決する
     */
    public function resolveDiskName(): string
    {
        $rawCloudConfig = $_SERVER['LARAVEL_CLOUD_DISK_CONFIG']
            ?? $_ENV['LARAVEL_CLOUD_DISK_CONFIG']
            ?? null;

        if (is_string($rawCloudConfig) && $rawCloudConfig !== '') {
            $disks = json_decode($rawCloudConfig, true);
            if (is_array($disks)) {
                foreach ($disks as $diskDef) {
                    if (! empty($diskDef['disk']) && ! config()->has("filesystems.disks.{$diskDef['disk']}")) {
                        config()->set("filesystems.disks.{$diskDef['disk']}", [
                            'driver' => 's3',
                            'key' => $diskDef['access_key_id'] ?? null,
                            'secret' => $diskDef['access_key_secret'] ?? null,
                            'bucket' => $diskDef['bucket'] ?? null,
                            'url' => $diskDef['url'] ?? null,
                            'endpoint' => $diskDef['endpoint'] ?? null,
                            'region' => $diskDef['region'] ?? 'auto',
                            'credentials' => $diskDef['credentials'] ?? null,
                            'use_path_style_endpoint' => false,
                            'throw' => false,
                            'report' => false,
                        ]);
                    }
                }

                if ($this->disk) {
                    return $this->disk;
                }

                foreach ($disks as $diskDef) {
                    if (($diskDef['is_default'] ?? false) && ! empty($diskDef['disk'])) {
                        return (string) $diskDef['disk'];
                    }
                }

                if (! empty($disks[0]['disk'])) {
                    return (string) $disks[0]['disk'];
                }
            }
        }

        if ($this->disk) {
            return $this->disk;
        }

        $defaultDisk = (string) config('filesystems.default', 's3');

        return $defaultDisk !== 'local' ? $defaultDisk : 's3';
    }

    public function store(UploadedFile $file): string
    {
        $diskName = $this->resolveDiskName();
        $extension = SvgSanitizer::resolveSafeExtension($file);
        $filename = Str::ulid() . '.' . $extension;
        $directory = trim($this->directory, '/');

        $options = [
            'disk' => $diskName,
            'CacheControl' => 'public, max-age=31536000',
        ];

        if ($this->visibility) {
            $options['visibility'] = $this->visibility;
        }

        return $file->storeAs($directory, $filename, $options);
    }

    public function url(string $storedValue): string
    {
        $cleanPath = ltrim($storedValue, '/');

        if ($this->publicBaseUrl) {
            return rtrim($this->publicBaseUrl, '/') . '/' . $cleanPath;
        }

        $diskName = $this->resolveDiskName();
        $disk = Storage::disk($diskName);

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

        $diskName = $this->resolveDiskName();
        Storage::disk($diskName)->delete(ltrim($storedValue, '/'));
    }
}

