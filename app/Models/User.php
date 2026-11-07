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
        'is_admin',
    ];

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

        if ($this->matchesInitialAdminRules($oauthProvider, $oauthNickname, $oauthEmail, $oauthId)) {
            $this->forceFill(['is_admin' => true])->save();

            return true;
        }

        return false;
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

        foreach ($rules as $rawRule) {
            $rule = trim((string) $rawRule);
            if ($rule === '') {
                continue;
            }

            if (str_contains($rule, ':')) {
                [$prefix, $target] = explode(':', $rule, 2);
                $prefix = strtolower(trim($prefix));
                $target = strtolower(trim($target));
                if ($target === '') {
                    continue;
                }

                if ($prefix === 'github' && $oauthProvider === 'github') {
                    $nick = strtolower(ltrim((string) $oauthNickname, '@'));
                    $id = strtolower((string) ($oauthId ?: $this->github_id));
                    if ($nick === $target || $id === $target) {
                        return true;
                    }
                } elseif ($prefix === 'google' && $oauthProvider === 'google') {
                    $mail = strtolower((string) ($oauthEmail ?: $this->email));
                    $id = strtolower((string) ($oauthId ?: $this->google_id));
                    if ($mail === $target || $id === $target) {
                        return true;
                    }
                } elseif ($prefix === 'email') {
                    if (strtolower((string) $this->email) === $target) {
                        return true;
                    }
                } elseif ($prefix === 'handle') {
                    if (strtolower((string) $this->handle) === ltrim($target, '@')) {
                        return true;
                    }
                }

                continue;
            }

            // プレフィックスなし: "@handle" は handle 判定、メールアドレス形式は email 判定
            if (str_starts_with($rule, '@')) {
                if (strtolower((string) $this->handle) === strtolower(ltrim($rule, '@'))) {
                    return true;
                }
            } elseif (str_contains($rule, '@')) {
                if (strtolower((string) $this->email) === strtolower($rule)) {
                    return true;
                }
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
