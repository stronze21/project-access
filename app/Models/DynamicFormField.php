<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DynamicFormField extends Model
{
    protected $fillable = [
        'dynamic_form_id',
        'key',
        'label',
        'type',
        'help_text',
        'is_required',
        'is_filterable',
        'is_active',
        'sort_order',
        'options',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_filterable' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'options' => 'array',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(DynamicForm::class, 'dynamic_form_id');
    }

    public function toSchemaArray(): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'label' => $this->label,
            'type' => $this->type,
            'help_text' => $this->help_text,
            'is_required' => $this->is_required,
            'is_filterable' => $this->is_filterable,
            'options' => $this->normalizedOptions(),
        ];
    }

    public function normalizedOptions(): array
    {
        $options = $this->options ?? [];

        return collect($options)
            ->map(function ($option): array {
                if (is_array($option)) {
                    $value = (string) ($option['value'] ?? $option['label'] ?? '');
                    $label = (string) ($option['label'] ?? $option['value'] ?? $value);

                    return ['value' => $value, 'label' => $label];
                }

                $value = (string) $option;

                return ['value' => $value, 'label' => $value];
            })
            ->filter(fn (array $option): bool => $option['value'] !== '')
            ->values()
            ->all();
    }

    public function usesOptions(): bool
    {
        return in_array($this->type, DynamicForm::OPTION_TYPES, true);
    }
}
