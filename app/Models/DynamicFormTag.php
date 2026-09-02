<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DynamicFormTag extends Model
{
    protected $fillable = [
        'dynamic_form_id',
        'label',
        'color',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(DynamicForm::class, 'dynamic_form_id');
    }

    public function submissions(): BelongsToMany
    {
        return $this->belongsToMany(
            DynamicFormSubmission::class,
            'dynamic_form_submission_tag',
            'dynamic_form_tag_id',
            'dynamic_form_submission_id'
        )->withTimestamps();
    }

    public function badgeClass(): string
    {
        return match ($this->color) {
            'blue' => 'badge-info',
            'amber', 'yellow' => 'badge-warning',
            'green' => 'badge-success',
            'red' => 'badge-error',
            'purple', 'pink' => 'badge-secondary',
            default => 'badge-ghost',
        };
    }
}
