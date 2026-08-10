<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScholarshipProgram extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'academic_year',
        'description',
        'open_at',
        'close_at',
        'office_address',
        'office_hours',
        'required_originals',
        'is_active',
    ];

    protected $casts = [
        'open_at' => 'datetime',
        'close_at' => 'datetime',
        'required_originals' => 'array',
        'is_active' => 'boolean',
    ];

    public function applications(): HasMany
    {
        return $this->hasMany(ScholarshipApplication::class);
    }

    public function scopeOpen($query)
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('open_at')->orWhere('open_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('close_at')->orWhere('close_at', '>=', $now);
            });
    }

    public function isOpen(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->open_at && $this->open_at->isFuture()) {
            return false;
        }

        if ($this->close_at && $this->close_at->isPast()) {
            return false;
        }

        return true;
    }
}
