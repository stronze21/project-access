<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BarangayHealthWorkerAssignment extends Model
{
    protected $fillable = [
        'barangay_zone_id',
        'resident_id',
        'source_batch_id',
        'legacy_pin',
        'assignment_slot',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(BarangayZone::class, 'barangay_zone_id');
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }
}
