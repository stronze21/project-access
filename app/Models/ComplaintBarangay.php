<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComplaintBarangay extends Model
{
    use HasFactory;

    protected $table = 'bosesmoto_barangays';

    protected $fillable = [
        'name',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'barangay_id');
    }
}
