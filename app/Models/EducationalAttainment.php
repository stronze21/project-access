<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducationalAttainment extends Model
{
    protected $fillable = ['legacy_code', 'name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
