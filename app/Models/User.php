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
        'avatar_url',
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
        ];
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
        return $this->groups()
            ->where('groups.id', $group->id)
            ->exists();
    }

    public function isOwnerOfGroup(Group $group): bool
    {
        return $this->groups()
            ->where('groups.id', $group->id)
            ->wherePivot('role', 'owner')
            ->exists();
    }

    public function canManageSeries(RallySeries $series): bool
    {
        if ($series->group_id) {
            return $this->groups()
                ->where('groups.id', $series->group_id)
                ->exists();
        }

        return (int) $series->creator_id === (int) $this->id;
    }

    public function canManageRally(StampRally $rally): bool
    {
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
