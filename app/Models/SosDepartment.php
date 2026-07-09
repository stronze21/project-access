<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SosDepartment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'hotline',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function alerts(): HasMany
    {
        return $this->hasMany(SosAlert::class);
    }
}
