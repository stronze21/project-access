<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResidentEmailVerificationCode extends Model
{
    protected $fillable = [
        'challenge_id', 'resident_identifier', 'last_name', 'birth_date', 'email',
        'code_hash', 'attempts', 'expires_at', 'verified_at', 'consumed_at',
    ];

    protected $hidden = ['code_hash'];

    protected $casts = [
        'birth_date' => 'date',
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];
}
