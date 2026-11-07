<?php

namespace App\Models;

use App\Services\StampStorage\StampImageStorageManager;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StampLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'stamp_rally_id',
        'name',
        'description',
        'latitude',
        'longitude',
        'radius_meters',
        'available_from',
        'available_until',
        'stamp_icon_type',
        'stamp_icon_value',
        'stamp_color',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'radius_meters' => 'integer',
            'available_from' => 'datetime',
            'available_until' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    public function rally(): BelongsTo
    {
        return $this->belongsTo(StampRally::class, 'stamp_rally_id');
    }

    public function stamps(): HasMany
    {
        return $this->hasMany(UserStamp::class, 'stamp_location_id');
    }

    public function isAvailableAt(?Carbon $at = null): bool
    {
        $now = $at ?? now();

        if ($this->available_from && $now->lt($this->available_from)) {
            return false;
        }

        if ($this->available_until && $now->gt($this->available_until)) {
            return false;
        }

        return true;
    }

    public function availabilityLabel(): string
    {
        if (! $this->available_from && ! $this->available_until) {
            return '常設（いつでも打刻可）';
        }

        $from = $this->available_from ? $this->available_from->format('Y/m/d H:i') : '指定なし';
        $until = $this->available_until ? $this->available_until->format('Y/m/d H:i') : '指定なし';

        return "{$from} 〜 {$until}";
    }

    public static function presetIcons(): array
    {
        return [
            'elephant' => ['label' => 'エレファント (PHP)', 'emoji' => '🐘'],
            'star' => ['label' => 'スター', 'emoji' => '⭐'],
            'conference' => ['label' => 'カンファレンス・マイク', 'emoji' => '🎤'],
            'castle' => ['label' => 'お城・ランドマーク', 'emoji' => '🏯'],
            'train' => ['label' => '鉄道・駅', 'emoji' => '🚆'],
            'shrine' => ['label' => '神社・仏閣', 'emoji' => '⛩️'],
            'coffee' => ['label' => 'カフェ・グルメ', 'emoji' => '☕'],
            'beer' => ['label' => '乾杯・懇親会', 'emoji' => '🍺'],
            'mountain' => ['label' => '自然・展望', 'emoji' => '🗻'],
            'trophy' => ['label' => 'トロフィー', 'emoji' => '🏆'],
        ];
    }

    public function hasCustomImage(): bool
    {
        return $this->stamp_icon_type === 'custom' && ! empty($this->stamp_icon_value);
    }

    public function iconImageUrl(): ?string
    {
        if (! $this->hasCustomImage()) {
            return null;
        }

        return app(StampImageStorageManager::class)->url($this->stamp_icon_value);
    }

    public function iconEmoji(): string
    {
        if ($this->hasCustomImage()) {
            return '🖼️';
        }

        $presets = self::presetIcons();

        return $presets[$this->stamp_icon_value]['emoji'] ?? '💮';
    }
}

