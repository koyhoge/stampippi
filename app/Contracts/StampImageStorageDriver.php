<?php

namespace App\Contracts;

use Illuminate\Http\UploadedFile;

interface StampImageStorageDriver
{
    /**
     * アップロードされたスタンプ画像を保存し、DB（stamp_icon_value）へ記録する識別子・パスを返す
     */
    public function store(UploadedFile $file): string;

    /**
     * DB（stamp_icon_value）に保存された識別子・パスから公開表示用のURL（またはData URI）を返す
     */
    public function url(string $storedValue): string;

    /**
     * 不要になったスタンプ画像をストレージから削除する
     */
    public function delete(string $storedValue): void;
}

