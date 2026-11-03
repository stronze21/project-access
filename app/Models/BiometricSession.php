<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BiometricSession extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $hidden = ['candidates'];

    protected $casts = ['candidates' => 'array', 'expires_at' => 'datetime', 'consented_at' => 'datetime'];
}
