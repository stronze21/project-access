<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CitizenServiceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'resident_id',
        'service_type',
        'service_name',
        'reference_number',
        'status',
        'current_step',
        'submitted_at',
        'status_updated_at',
        'expected_completion_at',
        'completed_at',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'status_updated_at' => 'datetime',
        'expected_completion_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $request): void {
            if (!$request->reference_number) {
                $request->reference_number = 'CSR-' . now()->format('Ymd') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 6));
            }

            $request->submitted_at ??= now();
            $request->status_updated_at ??= now();
        });
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }
}
