<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'handle',
        'name',
        'email',
        'password',
        'google_id',
        'github_id',
        'avatar_url',
    ];

    public static function isDevToolsEnabled(): bool
    {
        return (bool) config('app.dev_tools') && ! app()->isProduction();
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    /**
     * 環境変数 APP_INITIAL_ADMINS (例: "github:koyhoge,google:admin@example.com") に一致するか判定し、
     * 一致する場合は is_admin = true に昇格させる。
     *
     * セキュリティ方針:
     * - 判定には OAuth プロバイダが検証した値（GitHub の nickname/ID、Google のメール/ID）のみを使用し、
     *   ユーザーが自由に入力できる handle や未検証のメールアドレスでは昇格させない。
     * - パスワードが設定されたアカウントは昇格させない（メールアドレスで先回り登録されたアカウントに
     *   OAuth が自動紐付けされた場合の乗っ取りを防ぐため）。必要なら `php artisan user:admin` を使う。
     */
    public function syncInitialAdminStatus(
        ?string $oauthProvider = null,
        ?string $oauthNickname = null,
        ?string $oauthEmail = null,
        ?string $oauthId = null
    ): bool {
        if ($this->is_admin) {
            return true;
        }

        if (! in_array($oauthProvider, ['github', 'google'], true)) {
            return false;
        }

        if (! $this->matchesInitialAdminRules($oauthProvider, $oauthNickname, $oauthEmail, $oauthId)) {
            return false;
        }

        if (! empty($this->getAttributes()['password'] ?? null)) {
            \Illuminate\Support\Facades\Log::warning('APP_INITIAL_ADMINS に一致しましたが、パスワード設定済みアカウントのため自動昇格をスキップしました。', [
                'user_id' => $this->id,
                'provider' => $oauthProvider,
            ]);

            return false;
        }

        $this->forceFill(['is_admin' => true])->save();

        return true;
    }

    public function matchesInitialAdminRules(
        ?string $oauthProvider = null,
        ?string $oauthNickname = null,
        ?string $oauthEmail = null,
        ?string $oauthId = null
    ): bool {
        $rules = config('app.initial_admins', []);
        if (! is_array($rules) || $rules === []) {
            return false;
        }

        $nick = strtolower(ltrim((string) $oauthNickname, '@'));
        $mail = strtolower((string) $oauthEmail);
        $id = strtolower((string) $oauthId);

        foreach ($rules as $rawRule) {
            $rule = trim((string) $rawRule);
            if (! str_contains($rule, ':')) {
                continue;
            }

            [$prefix, $target] = explode(':', $rule, 2);
            $prefix = strtolower(trim($prefix));
            $target = strtolower(ltrim(trim($target), '@'));
            if ($target === '' || $prefix !== $oauthProvider) {
                continue;
            }

            if ($prefix === 'github' && (($nick !== '' && $nick === $target) || ($id !== '' && $id === $target))) {
                return true;
            }

            if ($prefix === 'google' && (($mail !== '' && $mail === $target) || ($id !== '' && $id === $target))) {
                return true;
            }
        }

        return false;
    }

    public function canAccessManagement(): bool
    {
        if ($this->is_admin) {
            return true;
        }

        return $this->groups()->exists();
    }

    public function canCreateGroup(): bool
    {
        return (bool) $this->is_admin;
    }

    public function canCreateSeries(): bool
    {
        return (bool) $this->is_admin;
    }

    public function canCreateRally(): bool
    {
        if ($this->is_admin) {
            return true;
        }

        $groupIds = $this->groups()->pluck('groups.id');

        return $groupIds->isNotEmpty()
            && RallySeries::whereIn('group_id', $groupIds)->exists();
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function createdSeries(): HasMany
    {
        return $this->hasMany(RallySeries::class, 'creator_id');
    }

    public function createdRallies(): HasMany
    {
        return $this->hasMany(StampRally::class, 'creator_id');
    }

    public function participations(): HasMany
    {
        return $this->hasMany(RallyParticipation::class);
    }

    public function stamps(): HasMany
    {
        return $this->hasMany(UserStamp::class);
    }

    public function canManageGroup(Group $group): bool
    {
        if ($this->is_admin) {
            return true;
        }

        return $this->groups()
            ->where('groups.id', $group->id)
            ->exists();
    }

    public function isOwnerOfGroup(Group $group): bool
    {
        if ($this->is_admin) {
            return true;
        }

        return $this->groups()
            ->where('groups.id', $group->id)
            ->wherePivot('role', 'owner')
            ->exists();
    }

    public function canManageSeries(RallySeries $series): bool
    {
        if ($this->is_admin) {
            return true;
        }

        if ($series->group_id) {
            return $this->groups()
                ->where('groups.id', $series->group_id)
                ->exists();
        }

        return (int) $series->creator_id === (int) $this->id;
    }

    public function canManageRally(StampRally $rally): bool
    {
        if ($this->is_admin) {
            return true;
        }

        if ($rally->group_id) {
            return $this->groups()
                ->where('groups.id', $rally->group_id)
                ->exists();
        }

        if ($rally->rally_series_id && $rally->series && $rally->series->group_id) {
            return $this->groups()
                ->where('groups.id', $rally->series->group_id)
                ->exists();
        }

        return (int) $rally->creator_id === (int) $this->id;
    }
}
