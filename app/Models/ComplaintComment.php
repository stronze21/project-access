<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class ComplaintComment extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;
    public const DISPLAY_TIMEZONE = 'Asia/Manila';

    protected $fillable = [
        'complaint_id',
        'user_id',
        'body',
        'is_staff_response',
        'is_hidden',
        'hidden_by_user_id',
        'hidden_reason',
        'hidden_at',
    ];

    protected $casts = [
        'is_staff_response' => 'boolean',
        'is_hidden' => 'boolean',
        'hidden_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hiddenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hidden_by_user_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(ComplaintCommentReaction::class);
    }

    public function createdAtManila(): ?Carbon
    {
        return $this->created_at?->copy()->timezone(self::DISPLAY_TIMEZONE);
    }
}
