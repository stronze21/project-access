<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BarangayZone extends Model
{
    protected $fillable = [
        'source_system',
        'legacy_barangay_code',
        'legacy_zone_id',
        'name',
        'brgy_code',
        'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function healthWorkerAssignments(): HasMany
    {
        return $this->hasMany(BarangayHealthWorkerAssignment::class);
    }
}
