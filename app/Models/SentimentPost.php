<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class SentimentPost extends Model
{
    use HasFactory, SoftDeletes;

    public const MEDIA_NONE = 'none';
    public const MEDIA_IMAGE = 'image';
    public const MEDIA_VIDEO = 'video';
    public const MEDIA_EXTERNAL = 'external';

    protected $fillable = [
        'user_id',
        'body',
        'media_kind',
        'media_disk',
        'media_path',
        'media_mime_type',
        'media_original_name',
        'external_url',
        'is_pinned',
        'is_comments_locked',
        'reports_count',
        'hidden_at',
        'hidden_reason',
        'is_permanently_deleted',
        'edited_at',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_comments_locked' => 'boolean',
        'is_permanently_deleted' => 'boolean',
        'hidden_at' => 'datetime',
        'edited_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(SentimentComment::class, 'post_id');
    }

    public function reactions(): MorphMany
    {
        return $this->morphMany(SentimentReaction::class, 'reactionable');
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(SentimentReport::class, 'reportable');
    }

    public function createdAtManila(): ?Carbon
    {
        return $this->created_at?->copy()->timezone(config('sentiments.timezone', 'Asia/Manila'));
    }

    public function editedAtManila(): ?Carbon
    {
        return $this->edited_at?->copy()->timezone(config('sentiments.timezone', 'Asia/Manila'));
    }

    public function isVisibleTo(User $viewer): bool
    {
        if ($viewer->isAdmin() || $viewer->isMayor()) {
            return true;
        }

        if ($this->is_permanently_deleted) {
            return false;
        }

        if ($this->hidden_at === null) {
            return true;
        }

        return (int) $this->user_id === (int) $viewer->id;
    }

    public function scopeVisibleTo(Builder $query, User $viewer): Builder
    {
        if ($viewer->isAdmin() || $viewer->isMayor()) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($viewer): void {
            $builder
                ->where(function (Builder $visibleBuilder): void {
                    $visibleBuilder
                        ->whereNull('hidden_at')
                        ->where('is_permanently_deleted', false);
                })
                ->orWhere(function (Builder $ownerBuilder) use ($viewer): void {
                    $ownerBuilder
                        ->where('user_id', $viewer->id)
                        ->where('is_permanently_deleted', false);
                });
        });
    }
}

