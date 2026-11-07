<?php

namespace App\Services\StampStorage;

use App\Contracts\StampImageStorageDriver;
use Illuminate\Http\UploadedFile;

class DatabaseDataUriStorageDriver implements StampImageStorageDriver
{
    public function store(UploadedFile $file): string
    {
        $mime = $file->getMimeType() ?: 'image/png';
        $contents = file_get_contents($file->getRealPath());

        return 'data:' . $mime . ';base64,' . base64_encode($contents ?: '');
    }

    public function url(string $storedValue): string
    {
        return $storedValue;
    }

    public function delete(string $storedValue): void
    {
        // インライン保存のため外部ファイル削除は不要
    }
}

