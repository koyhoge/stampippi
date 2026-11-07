<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserStamp extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'stamp_rally_id',
        'stamp_location_id',
        'checkin_lat',
        'checkin_lng',
        'accuracy_meters',
        'distance_meters',
        'stamped_at',
    ];

    protected function casts(): array
    {
        return [
            'checkin_lat' => 'float',
            'checkin_lng' => 'float',
            'accuracy_meters' => 'float',
            'distance_meters' => 'integer',
            'stamped_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rally(): BelongsTo
    {
        return $this->belongsTo(StampRally::class, 'stamp_rally_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(StampLocation::class, 'stamp_location_id');
    }
}

