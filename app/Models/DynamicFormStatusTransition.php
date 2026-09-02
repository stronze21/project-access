<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DynamicFormStatusTransition extends Model
{
    protected $fillable = [
        'dynamic_form_id',
        'from_status_id',
        'to_status_id',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(DynamicForm::class, 'dynamic_form_id');
    }

    public function fromStatus(): BelongsTo
    {
        return $this->belongsTo(DynamicFormStatus::class, 'from_status_id');
    }

    public function toStatus(): BelongsTo
    {
        return $this->belongsTo(DynamicFormStatus::class, 'to_status_id');
    }
}
