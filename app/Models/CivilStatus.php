<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CivilStatus extends Model
{
    public const CANONICAL_VALUES = [
        'single',
        'married',
        'widowed',
        'divorced',
        'separated',
        'other',
    ];

    protected $fillable = ['legacy_code', 'name', 'canonical_value', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
