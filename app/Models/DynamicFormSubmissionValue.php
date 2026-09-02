<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DynamicFormSubmissionValue extends Model
{
    protected $fillable = [
        'dynamic_form_submission_id',
        'field_key',
        'value_string',
        'value_number',
        'value_date',
    ];

    protected $casts = [
        'value_number' => 'decimal:4',
        'value_date' => 'date',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(DynamicFormSubmission::class, 'dynamic_form_submission_id');
    }
}
