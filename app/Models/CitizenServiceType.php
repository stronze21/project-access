<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CitizenServiceType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
