<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegacyHouseholdLink extends Model
{
    protected $fillable = [
        'source_system', 'legacy_family_number', 'legacy_building_registry_number',
        'household_id', 'source_batch_id', 'status', 'reviewed_by', 'reviewed_at',
    ];
}
