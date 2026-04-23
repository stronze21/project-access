<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SosAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'resident_id',
        'reference_number',
        'status',
        'contact_number',
        'message',
        'latitude',
        'longitude',
        'location_label',
        'acknowledged_at',
        'resolved_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $alert): void {
            if (!$alert->reference_number) {
                $alert->reference_number = 'SOS-' . now()->format('Ymd') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 6));
            }
        });
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }
}
