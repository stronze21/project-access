<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DynamicForm extends Model
{
    use HasFactory;

    public const FIELD_TYPES = [
        'short_text',
        'long_text',
        'number',
        'date',
        'dropdown',
        'radio',
        'checkboxes',
        'yes_no',
        'file',
        'resident',
    ];

    public const OPTION_TYPES = [
        'dropdown',
        'radio',
        'checkboxes',
    ];

    public const DEFAULT_STATUSES = [
        ['key' => 'draft', 'label' => 'Draft', 'color' => 'slate', 'is_initial' => true, 'is_terminal' => false],
        ['key' => 'submitted', 'label' => 'Submitted', 'color' => 'blue', 'is_initial' => false, 'is_terminal' => false],
        ['key' => 'in_progress', 'label' => 'In Progress', 'color' => 'amber', 'is_initial' => false, 'is_terminal' => false],
        ['key' => 'done', 'label' => 'Done', 'color' => 'green', 'is_initial' => false, 'is_terminal' => true],
        ['key' => 'cancelled', 'label' => 'Cancelled', 'color' => 'red', 'is_initial' => false, 'is_terminal' => true],
    ];

    public const DEFAULT_TRANSITIONS = [
        'draft' => ['submitted', 'cancelled'],
        'submitted' => ['in_progress', 'cancelled'],
        'in_progress' => ['done', 'cancelled'],
    ];

    protected $fillable = [
        'title',
        'slug',
        'description',
        'is_active',
        'link_to_resident',
        'published_at',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'link_to_resident' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $form): void {
            if (! $form->slug) {
                $form->slug = static::uniqueSlugFromTitle($form->title);
            }
        });
    }

    public static function uniqueSlugFromTitle(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'form';
        $slug = $base;
        $i = 2;

        while (static::query()
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    /**
     * @param  array<int, string>  $taken
     */
    public static function uniqueKeyFromLabel(string $label, array $taken = [], string $fallback = 'field'): string
    {
        $normalized = Str::of($label)->snake()->lower()->replaceMatches('/[^a-z0-9_]/', '')->toString();

        if ($normalized === '') {
            $normalized = $fallback;
        }

        if (preg_match('/^[0-9]/', $normalized)) {
            $normalized = $fallback.'_'.$normalized;
        }

        $key = $normalized;
        $i = 2;
        $taken = array_values(array_filter($taken));

        while (in_array($key, $taken, true)) {
            $key = $normalized.'_'.$i;
            $i++;
        }

        return $key;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(DynamicFormField::class)->orderBy('sort_order');
    }

    public function activeFields(): HasMany
    {
        return $this->fields()->where('is_active', true);
    }

    public function statuses(): HasMany
    {
        return $this->hasMany(DynamicFormStatus::class)->orderBy('sort_order');
    }

    public function transitions(): HasMany
    {
        return $this->hasMany(DynamicFormStatusTransition::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(DynamicFormTag::class)->orderBy('sort_order');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(DynamicFormSubmission::class);
    }

    public function initialStatus(): ?DynamicFormStatus
    {
        return $this->statuses->firstWhere('is_initial', true)
            ?? $this->statuses()->where('is_initial', true)->first();
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->is_active;
    }

    public function schemaSnapshot(): array
    {
        return $this->activeFields()
            ->get()
            ->map(fn (DynamicFormField $field): array => $field->toSchemaArray())
            ->values()
            ->all();
    }
}
