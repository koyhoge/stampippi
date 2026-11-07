<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RallyParticipation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'stamp_rally_id',
        'joined_at',
        'completed_at',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'completed_at' => 'datetime',
            'archived_at' => 'datetime',
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

    public function isArchived(): bool
    {
        if ($this->archived_at !== null) {
            return true;
        }

        if ($this->rally && $this->rally->ends_at && $this->rally->ends_at->isPast()) {
            return true;
        }

        return false;
    }
}
