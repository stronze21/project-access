<?php

namespace App\Livewire\Admin;

use App\Models\DynamicForm;
use App\Services\DynamicForm\FormSchemaService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Mary\Traits\Toast;

class DynamicFormBuilder extends Component
{
    use AuthorizesRequests, Toast;

    public DynamicForm $form;

    public string $title = '';

    public string $description = '';

    public string $slug = '';

    public bool $isActive = true;

    public bool $linkToResident = false;

    public string $activeTab = 'fields';

    public array $fields = [];

    public array $statuses = [];

    public array $tags = [];

    public array $transitionMap = [];

    public function mount(DynamicForm $form): void
    {
        $this->authorize('manage-forms');
        $this->form = $form;
        $this->hydrateFrom($form);
    }

    public function addField(string $type = 'short_text'): void
    {
        $this->fields[] = $this->blankField($type);
    }

    public function updatedFields($value, $name): void
    {
        if (! str_ends_with((string) $name, '.label')) {
            return;
        }

        $index = (int) explode('.', (string) $name)[0];
        if (! empty($this->fields[$index]['id'])) {
            return;
        }

        $this->fields[$index]['key'] = $this->uniqueFieldKey(
            (string) ($this->fields[$index]['label'] ?? ''),
            $index
        );
    }

    public function setActiveTab(string $tab): void
    {
        if (! in_array($tab, ['fields', 'workflow', 'tags'], true)) {
            return;
        }

        $this->activeTab = $tab;
    }

    public function removeField(int $index): void
    {
        unset($this->fields[$index]);
        $this->fields = array_values($this->fields);
    }

    public function moveField(int $index, int $direction): void
    {
        $swap = $index + $direction;
        if (! isset($this->fields[$index], $this->fields[$swap])) {
            return;
        }

        [$this->fields[$index], $this->fields[$swap]] = [$this->fields[$swap], $this->fields[$index]];
        $this->fields = array_values($this->fields);
    }

    public function addOption(int $index): void
    {
        $this->fields[$index]['options'][] = '';
    }

    public function removeOption(int $fieldIndex, int $optionIndex): void
    {
        unset($this->fields[$fieldIndex]['options'][$optionIndex]);
        $this->fields[$fieldIndex]['options'] = array_values($this->fields[$fieldIndex]['options']);
    }

    public function addStatus(): void
    {
        $tempId = (string) Str::uuid();
        $this->statuses[] = [
            'id' => null,
            'temp_id' => $tempId,
            'key' => '',
            'label' => '',
            'color' => 'slate',
            'is_initial' => $this->statuses === [],
            'is_terminal' => false,
        ];
        $this->transitionMap[$tempId] = [];
    }

    public function removeStatus(int $index): void
    {
        $tempId = $this->statuses[$index]['temp_id'] ?? null;
        unset($this->statuses[$index]);
        $this->statuses = array_values($this->statuses);
        unset($this->transitionMap[$tempId]);

        foreach ($this->transitionMap as $from => $targets) {
            $this->transitionMap[$from] = array_values(array_filter($targets, fn ($id) => $id !== $tempId));
        }
    }

    public function updatedStatuses($value, $name): void
    {
        $name = (string) $name;

        if (str_ends_with($name, 'is_initial') && $value) {
            $index = (int) explode('.', $name)[0];
            foreach ($this->statuses as $i => $status) {
                $this->statuses[$i]['is_initial'] = $i === $index;
            }

            return;
        }

        if (! str_ends_with($name, '.label')) {
            return;
        }

        $index = (int) explode('.', $name)[0];
        if (! empty($this->statuses[$index]['id'])) {
            return;
        }

        $this->statuses[$index]['key'] = $this->uniqueStatusKey(
            (string) ($this->statuses[$index]['label'] ?? ''),
            $index
        );
    }

    public function addTag(): void
    {
        $this->tags[] = [
            'id' => null,
            'label' => '',
            'color' => 'blue',
        ];
    }

    public function removeTag(int $index): void
    {
        unset($this->tags[$index]);
        $this->tags = array_values($this->tags);
    }

    public function save(FormSchemaService $schema): void
    {
        $this->authorize('manage-forms');

        try {
            $this->form = $schema->save($this->form, $this->payload(), auth()->id());
            $this->hydrateFrom($this->form);
            $this->success('Form saved.');
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());
        }
    }

    public function publish(FormSchemaService $schema): void
    {
        $this->save($schema);
        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $schema->publish($this->form->fresh());
        $this->form->refresh();
        $this->success('Form published.');
    }

    public function render()
    {
        return view('livewire.admin.dynamic-form-builder', [
            'fieldTypes' => $this->fieldTypes(),
            'colorOptions' => $this->colorOptions(),
        ])->layout('layouts.app');
    }

    private function hydrateFrom(DynamicForm $form): void
    {
        $form->load(['fields', 'statuses', 'transitions', 'tags']);

        $this->title = $form->title;
        $this->description = (string) $form->description;
        $this->slug = $form->slug;
        $this->isActive = $form->is_active;
        $this->linkToResident = $form->link_to_resident;

        $this->fields = $form->fields->map(function ($field) {
            return [
                'id' => $field->id,
                'temp_id' => 'field-'.$field->id,
                'key' => $field->key,
                'label' => $field->label,
                'type' => $field->type,
                'help_text' => $field->help_text,
                'is_required' => $field->is_required,
                'is_filterable' => $field->is_filterable,
                'is_active' => $field->is_active,
                'options' => collect($field->normalizedOptions())->pluck('label')->values()->all(),
            ];
        })->all();

        $this->statuses = $form->statuses->map(function ($status) {
            return [
                'id' => $status->id,
                'temp_id' => (string) $status->id,
                'key' => $status->key,
                'label' => $status->label,
                'color' => $status->color,
                'is_initial' => $status->is_initial,
                'is_terminal' => $status->is_terminal,
            ];
        })->all();

        $this->tags = $form->tags->map(fn ($tag) => [
            'id' => $tag->id,
            'label' => $tag->label,
            'color' => $tag->color,
        ])->all();

        $this->transitionMap = [];
        foreach ($this->statuses as $status) {
            $this->transitionMap[$status['temp_id']] = $form->transitions
                ->where('from_status_id', $status['id'])
                ->pluck('to_status_id')
                ->map(fn ($id) => (string) $id)
                ->values()
                ->all();
        }
    }

    private function payload(): array
    {
        $transitions = [];
        foreach ($this->transitionMap as $from => $targets) {
            foreach ($targets as $to) {
                $transitions[] = [
                    'from_temp_id' => (string) $from,
                    'to_temp_id' => (string) $to,
                ];
            }
        }

        return [
            'title' => $this->title,
            'description' => $this->description,
            'is_active' => $this->isActive,
            'link_to_resident' => $this->linkToResident,
            'fields' => $this->fields,
            'statuses' => $this->statuses,
            'transitions' => $transitions,
            'tags' => $this->tags,
        ];
    }

    private function blankField(string $type): array
    {
        return [
            'id' => null,
            'temp_id' => (string) Str::uuid(),
            'key' => '',
            'label' => '',
            'type' => $type,
            'help_text' => '',
            'is_required' => false,
            'is_filterable' => false,
            'is_active' => true,
            'options' => in_array($type, DynamicForm::OPTION_TYPES, true) ? [''] : [],
        ];
    }

    private function uniqueFieldKey(string $label, int $currentIndex): string
    {
        $taken = collect($this->fields)
            ->filter(fn ($field, $index) => $index !== $currentIndex)
            ->pluck('key')
            ->filter()
            ->values()
            ->all();

        return DynamicForm::uniqueKeyFromLabel($label, $taken, 'question');
    }

    private function uniqueStatusKey(string $label, int $currentIndex): string
    {
        $taken = collect($this->statuses)
            ->filter(fn ($status, $index) => $index !== $currentIndex)
            ->pluck('key')
            ->filter()
            ->values()
            ->all();

        return DynamicForm::uniqueKeyFromLabel($label, $taken, 'status');
    }

    private function fieldTypes(): array
    {
        return [
            'short_text' => 'Short text',
            'long_text' => 'Long text',
            'number' => 'Number',
            'date' => 'Date',
            'dropdown' => 'Dropdown',
            'radio' => 'Multiple choice',
            'checkboxes' => 'Checkboxes',
            'yes_no' => 'Yes / No',
            'file' => 'File upload',
            'resident' => 'Resident lookup',
        ];
    }

    private function colorOptions(): array
    {
        return [
            'slate' => 'Slate',
            'blue' => 'Blue',
            'amber' => 'Amber',
            'green' => 'Green',
            'red' => 'Red',
            'purple' => 'Purple',
            'pink' => 'Pink',
        ];
    }
}
