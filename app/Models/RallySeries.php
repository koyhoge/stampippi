<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RallySeries extends Model
{
    use HasFactory;

    protected $table = 'rally_series';

    protected $fillable = [
        'organization_id',
        'creator_id',
        'slug',
        'title',
        'description',
        'visibility',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function rallies(): HasMany
    {
        return $this->hasMany(StampRally::class, 'rally_series_id')
            ->orderBy('sort_order')
            ->orderByDesc('starts_at')
            ->orderByDesc('id');
    }

    public function ownerName(): string
    {
        if ($this->organization) {
            return $this->organization->name;
        }

        return $this->creator ? $this->creator->name : 'Unknown';
    }
}

