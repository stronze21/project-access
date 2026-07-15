<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SourceIncomeType extends Model
{
    protected $fillable = ['legacy_code', 'name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function residents(): HasMany
    {
        return $this->hasMany(Resident::class);
    }
}
