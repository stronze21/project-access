<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmergencyAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'title',
        'message',
        'severity',
        'status',
        'alert_type',
        'send_push_notification',
        'starts_at',
        'ends_at',
        'metadata',
    ];

    protected $casts = [
        'send_push_notification' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($inner) {
                $inner->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($inner) {
                $inner->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            });
    }
}
