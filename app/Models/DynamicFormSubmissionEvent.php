<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DynamicFormSubmissionEvent extends Model
{
    protected $fillable = [
        'dynamic_form_submission_id',
        'from_status_id',
        'to_status_id',
        'actor_id',
        'note',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(DynamicFormSubmission::class, 'dynamic_form_submission_id');
    }

    public function fromStatus(): BelongsTo
    {
        return $this->belongsTo(DynamicFormStatus::class, 'from_status_id');
    }

    public function toStatus(): BelongsTo
    {
        return $this->belongsTo(DynamicFormStatus::class, 'to_status_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
