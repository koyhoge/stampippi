<?php

namespace App\Services\StampStorage;

use App\Contracts\StampImageStorageDriver;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

class StampImageStorageManager
{
    /** @var array<string, StampImageStorageDriver> */
    protected array $resolvedDrivers = [];

    /** @var array<string, callable> */
    protected array $customCreators = [];

    /**
     * 独自プラグインドライバを実行時に登録する
     */
    public function extend(string $name, callable $creator): self
    {
        $this->customCreators[$name] = $creator;
        unset($this->resolvedDrivers[$name]);

        return $this;
    }

    /**
     * 指定（省略時は config('stamp.image_driver')）されたドライバを取得する
     */
    public function driver(?string $name = null): StampImageStorageDriver
    {
        $name = $name ?: (string) config('stamp.image_driver', 'public');

        if (isset($this->resolvedDrivers[$name])) {
            return $this->resolvedDrivers[$name];
        }

        return $this->resolvedDrivers[$name] = $this->resolve($name);
    }

    protected function resolve(string $name): StampImageStorageDriver
    {
        if (isset($this->customCreators[$name])) {
            return call_user_func($this->customCreators[$name]);
        }

        $config = config("stamp.drivers.{$name}");
        if (! is_array($config) || empty($config['driver'])) {
            throw new InvalidArgumentException("スタンプ画像ストレージドライバ [{$name}] が config/stamp.php に定義されていません。");
        }

        $driverClass = $config['driver'];

        if ($driverClass === FilesystemDiskStorageDriver::class) {
            return new FilesystemDiskStorageDriver(
                disk: (string) ($config['disk'] ?? 'public'),
                directory: (string) ($config['directory'] ?? 'stamps'),
                publicBaseUrl: $config['public_base_url'] ?? null
            );
        }

        if ($driverClass === S3StampStorageDriver::class) {
            return new S3StampStorageDriver(
                disk: (string) ($config['disk'] ?? 's3'),
                directory: (string) ($config['directory'] ?? 'stamps'),
                publicBaseUrl: $config['public_base_url'] ?? null,
                useSignedUrl: (bool) ($config['use_signed_url'] ?? false),
                signedUrlTtlMinutes: (int) ($config['signed_url_ttl_minutes'] ?? 60),
                visibility: (string) ($config['visibility'] ?? 'public')
            );
        }

        if ($driverClass === LaravelCloudObjectStorageDriver::class) {
            return new LaravelCloudObjectStorageDriver(
                disk: ! empty($config['disk']) ? (string) $config['disk'] : null,
                directory: (string) ($config['directory'] ?? 'stamps'),
                publicBaseUrl: $config['public_base_url'] ?? null,
                useSignedUrl: (bool) ($config['use_signed_url'] ?? false),
                signedUrlTtlMinutes: (int) ($config['signed_url_ttl_minutes'] ?? 60),
                visibility: $config['visibility'] ?? 'public'
            );
        }

        return app($driverClass);
    }

    public function store(UploadedFile $file): string
    {
        SvgSanitizer::sanitizeUploadedFile($file);

        return $this->driver()->store($file);
    }

    public function url(string $storedValue): string
    {
        if (
            str_starts_with($storedValue, 'data:')
            || str_starts_with($storedValue, 'http://')
            || str_starts_with($storedValue, 'https://')
            || str_starts_with($storedValue, '/')
        ) {
            return $storedValue;
        }

        return $this->driver()->url($storedValue);
    }

    public function delete(string $storedValue): void
    {
        $this->driver()->delete($storedValue);
    }
}

