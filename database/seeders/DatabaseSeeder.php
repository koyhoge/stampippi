<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 本番・初期デプロイ時は初期DBを空にするためシードデータを投入しない。
        // 開発用サンプルデータを投入する場合は `php artisan db:seed --class=SampleDataSeeder` を実行してください。
    }
}
