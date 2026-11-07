<?php

namespace App\Services\StampStorage;

use App\Contracts\StampImageStorageDriver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FilesystemDiskStorageDriver implements StampImageStorageDriver
{
    public function __construct(
        protected string $disk = 'public',
        protected string $directory = 'stamps',
        protected ?string $publicBaseUrl = null
    ) {}

    public function store(UploadedFile $file): string
    {
        return $file->storePublicly($this->directory, ['disk' => $this->disk]);
    }

    public function url(string $storedValue): string
    {
        $cleanPath = ltrim($storedValue, '/');

        if ($this->publicBaseUrl) {
            return rtrim($this->publicBaseUrl, '/') . '/' . $cleanPath;
        }

        if ($this->disk === 'public') {
            return asset('storage/' . $cleanPath);
        }

        return Storage::disk($this->disk)->url($cleanPath);
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

        Storage::disk($this->disk)->delete($storedValue);
    }
}

