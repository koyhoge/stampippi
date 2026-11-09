<?php

namespace App\Services;

use App\Contracts\StampImageStorageDriver;
use App\Services\StampStorage\FilesystemDiskStorageDriver;
use App\Services\StampStorage\LaravelCloudObjectStorageDriver;
use App\Services\StampStorage\S3StampStorageDriver;
use Illuminate\Http\UploadedFile;

class AvatarStorageManager
{
    protected ?StampImageStorageDriver $driver = null;

    public function driver(): StampImageStorageDriver
    {
        if ($this->driver !== null) {
            return $this->driver;
        }

        $name = (string) config('stamp.image_driver', 'public');
        $config = config("stamp.drivers.{$name}");

        if (! is_array($config) || empty($config['driver'])) {
            return $this->driver = new FilesystemDiskStorageDriver(disk: 'public', directory: 'avatars');
        }

        $driverClass = $config['driver'];
        $directory = 'avatars';

        if ($driverClass === FilesystemDiskStorageDriver::class) {
            return $this->driver = new FilesystemDiskStorageDriver(
                disk: (string) ($config['disk'] ?? 'public'),
                directory: $directory,
                publicBaseUrl: $config['public_base_url'] ?? null
            );
        }

        if ($driverClass === S3StampStorageDriver::class) {
            return $this->driver = new S3StampStorageDriver(
                disk: (string) ($config['disk'] ?? 's3'),
                directory: $directory,
                publicBaseUrl: $config['public_base_url'] ?? null,
                useSignedUrl: (bool) ($config['use_signed_url'] ?? false),
                signedUrlTtlMinutes: (int) ($config['signed_url_ttl_minutes'] ?? 60),
                visibility: (string) ($config['visibility'] ?? 'public')
            );
        }

        if ($driverClass === LaravelCloudObjectStorageDriver::class) {
            return $this->driver = new LaravelCloudObjectStorageDriver(
                disk: ! empty($config['disk']) ? (string) $config['disk'] : null,
                directory: $directory,
                publicBaseUrl: $config['public_base_url'] ?? null,
                useSignedUrl: (bool) ($config['use_signed_url'] ?? false),
                signedUrlTtlMinutes: (int) ($config['signed_url_ttl_minutes'] ?? 60),
                visibility: $config['visibility'] ?? 'public'
            );
        }

        return $this->driver = new FilesystemDiskStorageDriver(disk: 'public', directory: $directory);
    }

    public function store(UploadedFile $file): string
    {
        return $this->driver()->store($file);
    }

    public function url(?string $storedValue): string
    {
        if (empty($storedValue)) {
            return '';
        }

        if (str_starts_with($storedValue, 'http://') || str_starts_with($storedValue, 'https://')) {
            return $storedValue;
        }

        return $this->driver()->url($storedValue);
    }

    public function delete(?string $storedValue): void
    {
        if (empty($storedValue)) {
            return;
        }

        $this->driver()->delete($storedValue);
    }
}
