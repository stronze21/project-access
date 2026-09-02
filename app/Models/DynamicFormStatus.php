<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DynamicFormStatus extends Model
{
    protected $fillable = [
        'dynamic_form_id',
        'key',
        'label',
        'color',
        'is_initial',
        'is_terminal',
        'sort_order',
    ];

    protected $casts = [
        'is_initial' => 'boolean',
        'is_terminal' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(DynamicForm::class, 'dynamic_form_id');
    }

    public function outgoingTransitions(): HasMany
    {
        return $this->hasMany(DynamicFormStatusTransition::class, 'from_status_id');
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

    public function boardClass(): string
    {
        return match ($this->color) {
            'blue' => 'border-sky-200 bg-sky-50',
            'amber', 'yellow' => 'border-amber-200 bg-amber-50',
            'green' => 'border-emerald-200 bg-emerald-50',
            'red' => 'border-rose-200 bg-rose-50',
            'purple' => 'border-violet-200 bg-violet-50',
            'pink' => 'border-pink-200 bg-pink-50',
            default => 'border-slate-200 bg-slate-50',
        };
    }
}
