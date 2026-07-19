<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PublicServiceLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'service_type',
        'description',
        'url',
        'icon',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $link): void {
            if (! $link->slug) {
                $link->slug = Str::slug($link->title);
            }
        });
    }

    public function getMaterialIconAttribute(): string
    {
        return match ($this->icon) {
            'briefcase' => 'business_center',
            'document-text', 'document' => 'description',
            'banknotes', 'cash' => 'payments',
            'heart' => 'favorite',
            'globe-alt', 'globe' => 'public',
            'building-office', 'government' => 'account_balance',
            'phone' => 'call',
            'map-pin' => 'location_on',
            'user-group' => 'groups',
            'shield-check' => 'verified_user',
            'academic-cap' => 'school',
            'truck' => 'local_shipping',
            default => in_array($this->icon, [
                'public', 'business_center', 'description', 'payments', 'favorite',
                'account_balance', 'call', 'location_on', 'groups', 'verified_user',
                'school', 'local_shipping', 'medical_services', 'work', 'language',
            ], true) ? $this->icon : 'public',
        };
    }
}
