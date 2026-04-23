<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrievanceReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'resident_id',
        'reference_number',
        'category',
        'subject',
        'description',
        'status',
        'latitude',
        'longitude',
        'location_label',
        'photo_path',
        'admin_response',
        'resolved_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'resolved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $report): void {
            if (!$report->reference_number) {
                $report->reference_number = 'GRV-' . now()->format('Ymd') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 6));
            }
        });
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }
}
