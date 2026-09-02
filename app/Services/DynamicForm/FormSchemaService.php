<?php

namespace App\Services\DynamicForm;

use App\Models\DynamicForm;
use App\Models\DynamicFormField;
use App\Models\DynamicFormTag;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FormSchemaService
{
    /**
     * @param  array{
     *     title?: string,
     *     description?: ?string,
     *     slug?: ?string,
     *     is_active?: bool,
     *     link_to_resident?: bool,
     *     fields?: array<int, array<string, mixed>>,
     *     statuses?: array<int, array<string, mixed>>,
     *     transitions?: array<int, array<string, mixed>>,
     *     tags?: array<int, array<string, mixed>>
     * }  $payload
     */
    public function save(DynamicForm $form, array $payload, ?int $actorId = null): DynamicForm
    {
        $this->validate($payload, $form);

        return DB::transaction(function () use ($form, $payload, $actorId) {
            $title = trim((string) ($payload['title'] ?? $form->title));

            $form->fill([
                'title' => $title,
                'description' => $payload['description'] ?? $form->description,
                'is_active' => (bool) ($payload['is_active'] ?? $form->is_active),
                'link_to_resident' => (bool) ($payload['link_to_resident'] ?? $form->link_to_resident),
            ]);

            if (! $form->slug) {
                $form->slug = DynamicForm::uniqueSlugFromTitle($title, $form->id);
            }

            if (! $form->created_by && $actorId) {
                $form->created_by = $actorId;
            }

            $form->save();

            $statusIdMap = $this->syncStatuses($form, $payload['statuses'] ?? []);
            $this->syncTransitions($form, $payload['transitions'] ?? [], $statusIdMap);
            $this->syncFields($form, $payload['fields'] ?? []);
            $this->syncTags($form, $payload['tags'] ?? []);

            return $form->fresh(['fields', 'statuses', 'transitions', 'tags']);
        });
    }

    public function createBlank(string $title, ?int $actorId = null): DynamicForm
    {
        $form = DynamicForm::create([
            'title' => $title,
            'slug' => DynamicForm::uniqueSlugFromTitle($title),
            'is_active' => true,
            'created_by' => $actorId,
        ]);

        $this->seedDefaults($form);

        return $form->fresh(['fields', 'statuses', 'transitions', 'tags']);
    }

    public function seedDefaults(DynamicForm $form): void
    {
        if ($form->statuses()->exists()) {
            return;
        }

        $statusIds = [];
        foreach (DynamicForm::DEFAULT_STATUSES as $index => $status) {
            $created = $form->statuses()->create($status + ['sort_order' => $index]);
            $statusIds[$status['key']] = $created->id;
        }

        foreach (DynamicForm::DEFAULT_TRANSITIONS as $fromKey => $toKeys) {
            foreach ($toKeys as $toKey) {
                if (! isset($statusIds[$fromKey], $statusIds[$toKey])) {
                    continue;
                }

                $form->transitions()->create([
                    'from_status_id' => $statusIds[$fromKey],
                    'to_status_id' => $statusIds[$toKey],
                ]);
            }
        }
    }

    public function duplicate(DynamicForm $form, ?int $actorId = null): DynamicForm
    {
        $form->load(['fields', 'statuses', 'transitions', 'tags']);

        return DB::transaction(function () use ($form, $actorId) {
            $copy = DynamicForm::create([
                'title' => $form->title.' (copy)',
                'slug' => DynamicForm::uniqueSlugFromTitle($form->title.' copy'),
                'description' => $form->description,
                'is_active' => true,
                'link_to_resident' => $form->link_to_resident,
                'published_at' => null,
                'created_by' => $actorId,
            ]);

            $statusIdMap = [];
            foreach ($form->statuses as $status) {
                $cloned = $copy->statuses()->create($status->only([
                    'key', 'label', 'color', 'is_initial', 'is_terminal', 'sort_order',
                ]));
                $statusIdMap[$status->id] = $cloned->id;
            }

            foreach ($form->transitions as $transition) {
                if (! isset($statusIdMap[$transition->from_status_id], $statusIdMap[$transition->to_status_id])) {
                    continue;
                }

                $copy->transitions()->create([
                    'from_status_id' => $statusIdMap[$transition->from_status_id],
                    'to_status_id' => $statusIdMap[$transition->to_status_id],
                ]);
            }

            foreach ($form->fields as $field) {
                $copy->fields()->create($field->only([
                    'key', 'label', 'type', 'help_text', 'is_required', 'is_filterable',
                    'is_active', 'sort_order', 'options',
                ]));
            }

            foreach ($form->tags as $tag) {
                $copy->tags()->create($tag->only(['label', 'color', 'sort_order']));
            }

            return $copy->fresh(['fields', 'statuses', 'transitions', 'tags']);
        });
    }

    public function publish(DynamicForm $form): DynamicForm
    {
        $form->load(['fields', 'statuses']);

        if ($form->activeFields()->count() === 0) {
            throw ValidationException::withMessages([
                'fields' => 'Publish a form only after adding at least one field.',
            ]);
        }

        if (! $form->initialStatus()) {
            throw ValidationException::withMessages([
                'statuses' => 'A published form needs one initial status.',
            ]);
        }

        $form->published_at = now();
        $form->is_active = true;
        $form->save();

        return $form;
    }

    public function archive(DynamicForm $form): DynamicForm
    {
        $form->is_active = false;
        $form->save();

        return $form;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function validate(array $payload, ?DynamicForm $form = null): void
    {
        $errors = [];

        $title = trim((string) ($payload['title'] ?? $form?->title ?? ''));
        if ($title === '') {
            $errors['title'] = 'A form title is required.';
        }

        $fields = $payload['fields'] ?? [];
        $keys = [];
        foreach ($fields as $index => $field) {
            $prefix = "fields.{$index}";
            $label = trim((string) ($field['label'] ?? ''));
            $type = (string) ($field['type'] ?? '');

            if ($label === '') {
                $errors["{$prefix}.label"] = 'Each field needs a label.';
            } else {
                $key = DynamicForm::uniqueKeyFromLabel($label, array_keys($keys), 'question');
                $keys[$key] = true;
            }

            if (! in_array($type, DynamicForm::FIELD_TYPES, true)) {
                $errors["{$prefix}.type"] = 'Unknown field type.';
            }

            if (in_array($type, DynamicForm::OPTION_TYPES, true) && $this->normalizedOptions($field['options'] ?? []) === []) {
                $errors["{$prefix}.options"] = 'This field type needs at least one option.';
            }

            if ($type === 'file' && ! empty($field['is_filterable'])) {
                $errors["{$prefix}.is_filterable"] = 'File fields cannot be marked filterable.';
            }
        }

        $statuses = $payload['statuses'] ?? [];
        if ($statuses === []) {
            $errors['statuses'] = 'Add at least one workflow status.';
        }

        $statusKeys = [];
        $statusTempIds = [];
        $initialCount = 0;

        foreach ($statuses as $index => $status) {
            $prefix = "statuses.{$index}";
            $label = trim((string) ($status['label'] ?? ''));
            $tempId = (string) ($status['temp_id'] ?? $status['id'] ?? $index);

            if ($label === '') {
                $errors["{$prefix}.label"] = 'Each status needs a label.';
            } else {
                $key = DynamicForm::uniqueKeyFromLabel($label, array_keys($statusKeys), 'status');
                $statusKeys[$key] = true;
            }

            $statusTempIds[$tempId] = true;

            if (! empty($status['is_initial'])) {
                $initialCount++;
            }
        }

        if ($statuses !== [] && $initialCount !== 1) {
            $errors['statuses'] = 'Exactly one status must be marked as the initial status.';
        }

        foreach ($payload['transitions'] ?? [] as $index => $transition) {
            $from = (string) ($transition['from_temp_id'] ?? $transition['from_status_id'] ?? '');
            $to = (string) ($transition['to_temp_id'] ?? $transition['to_status_id'] ?? '');

            if ($from === '' || $to === '') {
                $errors["transitions.{$index}"] = 'Each transition needs a from and to status.';

                continue;
            }

            if ($from === $to) {
                $errors["transitions.{$index}"] = 'A status cannot transition to itself.';
            }

            if (! isset($statusTempIds[$from]) || ! isset($statusTempIds[$to])) {
                $errors["transitions.{$index}"] = 'Transitions must point to statuses on this form.';
            }
        }

        foreach ($payload['tags'] ?? [] as $index => $tag) {
            if (trim((string) ($tag['label'] ?? '')) === '') {
                $errors["tags.{$index}.label"] = 'Each tag needs a label.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     */
    private function syncFields(DynamicForm $form, array $fields): void
    {
        $keptIds = [];
        $usedKeys = [];

        foreach (array_values($fields) as $index => $field) {
            $label = trim((string) ($field['label'] ?? ''));
            $existing = isset($field['id']) && $field['id']
                ? $form->fields()->whereKey($field['id'])->first()
                : null;

            $key = $existing
                ? $existing->key
                : DynamicForm::uniqueKeyFromLabel($label, $usedKeys, 'question');

            $usedKeys[] = $key;

            $attributes = [
                'key' => $key,
                'label' => $label,
                'type' => $field['type'],
                'help_text' => $field['help_text'] ?? null,
                'is_required' => (bool) ($field['is_required'] ?? false),
                'is_filterable' => ($field['type'] ?? '') === 'file' ? false : (bool) ($field['is_filterable'] ?? false),
                'is_active' => array_key_exists('is_active', $field) ? (bool) $field['is_active'] : true,
                'sort_order' => $index,
                'options' => in_array($field['type'] ?? '', DynamicForm::OPTION_TYPES, true)
                    ? $this->normalizedOptions($field['options'] ?? [])
                    : null,
            ];

            if ($existing) {
                $existing->fill($attributes)->save();
                $keptIds[] = $existing->id;

                continue;
            }

            $keptIds[] = $form->fields()->create($attributes)->id;
        }

        $removable = $form->fields()->whereNotIn('id', $keptIds)->get();
        foreach ($removable as $field) {
            if ($this->fieldHasAnswers($form, $field)) {
                $field->is_active = false;
                $field->save();

                continue;
            }

            $field->delete();
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $statuses
     * @return array<string, int>
     */
    private function syncStatuses(DynamicForm $form, array $statuses): array
    {
        $map = [];
        $keptIds = [];
        $usedKeys = [];

        foreach (array_values($statuses) as $index => $status) {
            $label = trim((string) ($status['label'] ?? ''));
            $tempId = (string) ($status['temp_id'] ?? $status['id'] ?? $index);
            $existing = isset($status['id']) && $status['id']
                ? $form->statuses()->whereKey($status['id'])->first()
                : null;

            $key = $existing
                ? $existing->key
                : DynamicForm::uniqueKeyFromLabel($label, $usedKeys, 'status');
            $usedKeys[] = $key;

            $attributes = [
                'key' => $key,
                'label' => $label,
                'color' => $status['color'] ?? 'slate',
                'is_initial' => (bool) ($status['is_initial'] ?? false),
                'is_terminal' => (bool) ($status['is_terminal'] ?? false),
                'sort_order' => $index,
            ];

            if ($existing) {
                $existing->fill($attributes)->save();
                $keptIds[] = $existing->id;
                $map[$tempId] = $existing->id;
                $map[(string) $existing->id] = $existing->id;

                continue;
            }

            $created = $form->statuses()->create($attributes);
            $keptIds[] = $created->id;
            $map[$tempId] = $created->id;
            $map[(string) $created->id] = $created->id;
        }

        $removable = $form->statuses()->whereNotIn('id', $keptIds)->get();
        foreach ($removable as $status) {
            if ($status->form->submissions()->where('status_id', $status->id)->exists()) {
                throw ValidationException::withMessages([
                    'statuses' => "Status \"{$status->label}\" is in use and cannot be removed.",
                ]);
            }

            $status->delete();
        }

        return $map;
    }

    /**
     * @param  array<int, array<string, mixed>>  $transitions
     * @param  array<string, int>  $statusIdMap
     */
    private function syncTransitions(DynamicForm $form, array $transitions, array $statusIdMap): void
    {
        $form->transitions()->delete();

        $seen = [];
        foreach ($transitions as $transition) {
            $fromTemp = (string) ($transition['from_temp_id'] ?? $transition['from_status_id'] ?? '');
            $toTemp = (string) ($transition['to_temp_id'] ?? $transition['to_status_id'] ?? '');
            $fromId = $statusIdMap[$fromTemp] ?? null;
            $toId = $statusIdMap[$toTemp] ?? null;

            if (! $fromId || ! $toId || $fromId === $toId) {
                continue;
            }

            $pair = $fromId.':'.$toId;
            if (isset($seen[$pair])) {
                continue;
            }
            $seen[$pair] = true;

            $form->transitions()->create([
                'from_status_id' => $fromId,
                'to_status_id' => $toId,
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $tags
     */
    private function syncTags(DynamicForm $form, array $tags): void
    {
        $keptIds = [];

        foreach (array_values($tags) as $index => $tag) {
            $label = trim((string) ($tag['label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $attributes = [
                'label' => $label,
                'color' => $tag['color'] ?? 'blue',
                'sort_order' => $index,
            ];

            $existing = isset($tag['id']) && $tag['id']
                ? $form->tags()->whereKey($tag['id'])->first()
                : null;

            if ($existing) {
                $existing->fill($attributes)->save();
                $keptIds[] = $existing->id;

                continue;
            }

            $keptIds[] = $form->tags()->create($attributes)->id;
        }

        $form->tags()->whereNotIn('id', $keptIds)->each(function (DynamicFormTag $tag): void {
            $tag->delete();
        });
    }

    private function fieldHasAnswers(DynamicForm $form, DynamicFormField $field): bool
    {
        if ($form->submissions()->whereHas('values', fn ($query) => $query->where('field_key', $field->key))->exists()) {
            return true;
        }

        return $form->submissions()
            ->select('answers')
            ->get()
            ->contains(function ($submission) use ($field) {
                $answers = $submission->answers ?? [];

                return array_key_exists($field->key, $answers)
                    && $answers[$field->key] !== null
                    && $answers[$field->key] !== ''
                    && $answers[$field->key] !== [];
            });
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function normalizedOptions(mixed $options): array
    {
        if (is_string($options)) {
            $options = preg_split('/\r\n|\r|\n/', $options) ?: [];
        }

        if (! is_array($options)) {
            return [];
        }

        return collect($options)
            ->map(function ($option): ?array {
                if (is_array($option)) {
                    $value = trim((string) ($option['value'] ?? $option['label'] ?? ''));
                    $label = trim((string) ($option['label'] ?? $option['value'] ?? $value));

                    return $value === '' ? null : ['value' => $value, 'label' => $label !== '' ? $label : $value];
                }

                $value = trim((string) $option);

                return $value === '' ? null : ['value' => $value, 'label' => $value];
            })
            ->filter()
            ->values()
            ->all();
    }
}
