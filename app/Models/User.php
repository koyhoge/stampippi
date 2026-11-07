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
