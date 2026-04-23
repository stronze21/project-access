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
            if (!$link->slug) {
                $link->slug = Str::slug($link->title);
            }
        });
    }
}
