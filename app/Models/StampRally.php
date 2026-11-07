<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StampRally extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'rally_series_id',
        'creator_id',
        'slug',
        'title',
        'description',
        'cover_image_path',
        'visibility',
        'completion_condition',
        'required_locations_count',
        'starts_at',
        'ends_at',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'required_locations_count' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(RallySeries::class, 'rally_series_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(StampLocation::class, 'stamp_rally_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function participations(): HasMany
    {
        return $this->hasMany(RallyParticipation::class, 'stamp_rally_id');
    }

    public function userStamps(): HasMany
    {
        return $this->hasMany(UserStamp::class, 'stamp_rally_id');
    }

    public function isCurrentlyActive(?Carbon $at = null): bool
    {
        $now = $at ?? now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    public function periodLabel(): string
    {
        if (! $this->starts_at && ! $this->ends_at) {
            return '常設（期間制限なし）';
        }

        $start = $this->starts_at ? $this->starts_at->format('Y/m/d H:i') : '開始日指定なし';
        $end = $this->ends_at ? $this->ends_at->format('Y/m/d H:i') : '終了日指定なし';

        return "{$start} 〜 {$end}";
    }

    public function requiredCount(): int
    {
        $total = $this->locations()->count();

        if ($this->completion_condition === 'count' && $this->required_locations_count) {
            return min((int) $this->required_locations_count, max(1, $total));
        }

        return $total;
    }

    public function ownerName(): string
    {
        if ($this->organization) {
            return $this->organization->name;
        }

        if ($this->series && $this->series->organization) {
            return $this->series->organization->name;
        }

        return $this->creator ? $this->creator->name : 'Unknown';
    }
}

