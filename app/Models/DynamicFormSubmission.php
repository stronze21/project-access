<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DynamicFormSubmission extends Model
{
    protected $fillable = [
        'dynamic_form_id',
        'reference_number',
        'status_id',
        'resident_id',
        'created_by',
        'updated_by',
        'schema_snapshot',
        'answers',
    ];

    protected $casts = [
        'schema_snapshot' => 'array',
        'answers' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $submission): void {
            if (! $submission->reference_number) {
                $submission->reference_number = 'FRM-'.now()->format('Ymd').'-'.strtoupper(substr(md5(uniqid('', true)), 0, 6));
            }
        });
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(DynamicForm::class, 'dynamic_form_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(DynamicFormStatus::class, 'status_id');
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function values(): HasMany
    {
        return $this->hasMany(DynamicFormSubmissionValue::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(DynamicFormSubmissionEvent::class)->latest();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            DynamicFormTag::class,
            'dynamic_form_submission_tag',
            'dynamic_form_submission_id',
            'dynamic_form_tag_id'
        )->withTimestamps();
    }

    public function isInInitialStatus(): bool
    {
        return (bool) $this->status?->is_initial;
    }

    public function visibleTo(User $user): bool
    {
        if ($user->can('process-forms') || $user->can('view-forms')) {
            return true;
        }

        return $user->can('fill-forms') && (int) $this->created_by === (int) $user->id;
    }

    public function answerFor(string $key): mixed
    {
        return data_get($this->answers ?? [], $key);
    }

    public function schemaFields(): array
    {
        return $this->schema_snapshot ?? [];
    }
}
