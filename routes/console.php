<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

Artisan::command('user:admin {identifier} {--revoke : 管理者権限を解除する}', function (string $identifier) {
    $clean = ltrim(trim($identifier), '@');
    $user = User::where('handle', strtolower($clean))
        ->orWhere('email', strtolower($clean))
        ->first();

    if (! $user) {
        $this->error("ユーザー「{$identifier}」が見つかりません。");
        return 1;
    }

    $revoke = (bool) $this->option('revoke');
    $user->forceFill(['is_admin' => ! $revoke])->save();

    if ($revoke) {
        $this->info("{$user->name} (@{$user->handle}) の管理者権限を解除しました。");
    } else {
        $this->info("{$user->name} (@{$user->handle}) を管理者に設定しました。");
    }

    return 0;
})->purpose('指定した @handle またはメールアドレスのユーザーに管理者権限を付与・解除します');
