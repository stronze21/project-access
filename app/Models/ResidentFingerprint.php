<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResidentFingerprint extends Model
{
    protected $guarded = [];

    // Templates must never be serialized by generic model/API/export code.
    protected $hidden = ['template'];

    protected $casts = ['template' => 'encrypted', 'consented_at' => 'datetime'];
}
