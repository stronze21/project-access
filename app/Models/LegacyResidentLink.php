<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegacyResidentLink extends Model
{
    protected $fillable = [
        'source_system', 'legacy_pin', 'resident_id', 'source_batch_id',
        'status', 'match_method', 'reviewed_by', 'reviewed_at',
    ];
}
