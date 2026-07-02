<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintCommentReaction extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    public const REACTION_LIKE = 'like';
    public const REACTION_DISLIKE = 'dislike';

    protected $fillable = [
        'complaint_comment_id',
        'user_id',
        'reaction',
    ];

    public function comment(): BelongsTo
    {
        return $this->belongsTo(ComplaintComment::class, 'complaint_comment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
