<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SentimentReaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reactionable_type',
        'reactionable_id',
        'reaction',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reactionable(): MorphTo
    {
        return $this->morphTo();
    }
}

