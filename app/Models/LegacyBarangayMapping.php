<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegacyBarangayMapping extends Model
{
    protected $table = 'legacy_barangay_mappings';

    protected $fillable = [
        'source_system',
        'legacy_code',
        'legacy_name',
        'brgy_code',
        'status',
    ];
}
