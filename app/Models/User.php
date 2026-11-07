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

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)
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

    public function canManageOrganization(Organization $organization): bool
    {
        return $this->organizations()
            ->where('organizations.id', $organization->id)
            ->exists();
    }

    public function isOwnerOfOrganization(Organization $organization): bool
    {
        return $this->organizations()
            ->where('organizations.id', $organization->id)
            ->wherePivot('role', 'owner')
            ->exists();
    }

    public function canManageSeries(RallySeries $series): bool
    {
        if ($series->organization_id) {
            return $this->organizations()
                ->where('organizations.id', $series->organization_id)
                ->exists();
        }

        return (int) $series->creator_id === (int) $this->id;
    }

    public function canManageRally(StampRally $rally): bool
    {
        if ($rally->organization_id) {
            return $this->organizations()
                ->where('organizations.id', $rally->organization_id)
                ->exists();
        }

        if ($rally->rally_series_id && $rally->series && $rally->series->organization_id) {
            return $this->organizations()
                ->where('organizations.id', $rally->series->organization_id)
                ->exists();
        }

        return (int) $rally->creator_id === (int) $this->id;
    }
}

