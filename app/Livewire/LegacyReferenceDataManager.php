<?php

namespace App\Livewire;

use App\Models\Barangay;
use App\Models\CivilStatus;
use App\Models\EducationalAttainment;
use App\Models\LegacyBarangayMapping;
use App\Models\SourceIncomeType;
use App\Services\Legacy\LegacyCsvImporter;
use Database\Seeders\LocationsSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class LegacyReferenceDataManager extends Component
{
    use WithPagination;

    public string $type;

    public array $form = [];

    public ?int $editingId = null;

    public ?string $notice = null;

    public function mount(string $type): void
    {
        $this->authorizeManager();
        $this->type = $type;
        $this->config();
        $this->resetForm();
    }

    public function edit(int $recordId): void
    {
        $this->authorizeManager();
        $record = $this->modelQuery()->findOrFail($recordId);
        $this->editingId = $record->getKey();
        $this->form = collect(array_keys($this->config()['fields']))
            ->mapWithKeys(fn (string $field) => [$field => $record->{$field}])
            ->all();
        $this->notice = null;
        $this->resetValidation();
    }

    public function save(): void
    {
        $this->authorizeManager();
        $config = $this->config();
        $data = $this->validate($this->rules($config))['form'];
        $data = $this->normalize($data);

        /** @var Model|null $record */
        $record = $this->editingId ? $this->modelQuery()->findOrFail($this->editingId) : null;
        if ($record) {
            $record->update($data);
            $this->notice = "{$config['singular']} updated.";
        } else {
            $config['model']::create($data);
            $this->notice = "{$config['singular']} created.";
        }

        $this->resetForm();
        $this->resetPage();
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
        $this->notice = null;
    }

    public function render()
    {
        $config = $this->config();

        return view('livewire.legacy-reference-data-manager', [
            'config' => $config,
            'records' => $this->modelQuery()->orderBy('legacy_code')->paginate(25),
            'barangays' => $this->barangays(),
        ])->layout('layouts.app');
    }

    private function rules(array $config): array
    {
        $rules = [];
        foreach ($config['rules'] as $field => $fieldRules) {
            $rules["form.{$field}"] = $fieldRules;
        }
        $rules['form.legacy_code'][] = Rule::unique($config['table'], 'legacy_code')->ignore($this->editingId);

        return $rules;
    }

    private function normalize(array $data): array
    {
        if (array_key_exists('is_active', $this->config()['fields'])) {
            $data['is_active'] = (bool) ($data['is_active'] ?? false);
        }
        if ($this->type === 'barangays') {
            $data['source_system'] = LegacyCsvImporter::SOURCE_SYSTEM;
            $data['brgy_code'] = ($data['brgy_code'] ?? null) ?: null;
        }

        return $data;
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->form = collect($this->config()['fields'])
            ->mapWithKeys(fn (array $definition, string $field) => [
                $field => $definition['type'] === 'checkbox' ? true : ($definition['default'] ?? ''),
            ])->all();
        $this->resetValidation();
    }

    private function modelQuery()
    {
        $model = $this->config()['model'];

        return $model::query();
    }

    private function barangays(): array
    {
        if ($this->type !== 'barangays' || ! Schema::hasTable('refbrgy')) {
            return [];
        }

        return Barangay::query()
            ->where('citymunCode', LocationsSeeder::CITY_CODE)
            ->orderBy('brgyDesc')
            ->get(['brgyCode', 'brgyDesc'])
            ->map(fn (Barangay $barangay) => [
                'value' => $barangay->brgyCode,
                'label' => $barangay->brgyDesc,
            ])->all();
    }

    private function authorizeManager(): void
    {
        abort_unless(auth()->user()?->can('manage-legacy-reference-data'), 403);
    }

    private function config(): array
    {
        return match ($this->type) {
            'source-income-types' => [
                'title' => 'Source Income Types',
                'singular' => 'Source income type',
                'model' => SourceIncomeType::class,
                'table' => 'source_income_types',
                'fields' => [
                    'legacy_code' => ['label' => 'Legacy code', 'type' => 'text'],
                    'name' => ['label' => 'Description', 'type' => 'text'],
                    'is_active' => ['label' => 'Active', 'type' => 'checkbox'],
                ],
                'rules' => [
                    'legacy_code' => ['required', 'string', 'max:50'],
                    'name' => ['required', 'string', 'max:255'],
                    'is_active' => ['boolean'],
                ],
            ],
            'educational-attainments' => [
                'title' => 'Educational Attainments',
                'singular' => 'Educational attainment',
                'model' => EducationalAttainment::class,
                'table' => 'educational_attainments',
                'fields' => [
                    'legacy_code' => ['label' => 'Legacy code', 'type' => 'text'],
                    'name' => ['label' => 'Attainment', 'type' => 'text'],
                    'is_active' => ['label' => 'Active', 'type' => 'checkbox'],
                ],
                'rules' => [
                    'legacy_code' => ['required', 'string', 'max:50'],
                    'name' => ['required', 'string', 'max:255'],
                    'is_active' => ['boolean'],
                ],
            ],
            'civil-statuses' => [
                'title' => 'Civil Statuses',
                'singular' => 'Civil status',
                'model' => CivilStatus::class,
                'table' => 'civil_statuses',
                'fields' => [
                    'legacy_code' => ['label' => 'Legacy code', 'type' => 'text'],
                    'name' => ['label' => 'Source label', 'type' => 'text'],
                    'canonical_value' => [
                        'label' => 'Project equivalent',
                        'type' => 'select',
                        'default' => 'single',
                        'options' => collect(CivilStatus::CANONICAL_VALUES)
                            ->map(fn (string $value) => ['value' => $value, 'label' => str($value)->title()])
                            ->all(),
                    ],
                    'is_active' => ['label' => 'Active', 'type' => 'checkbox'],
                ],
                'rules' => [
                    'legacy_code' => ['required', 'string', 'max:50'],
                    'name' => ['required', 'string', 'max:255'],
                    'canonical_value' => ['required', Rule::in(CivilStatus::CANONICAL_VALUES)],
                    'is_active' => ['boolean'],
                ],
            ],
            'barangays' => [
                'title' => 'Legacy Barangay Mappings',
                'singular' => 'Barangay mapping',
                'model' => LegacyBarangayMapping::class,
                'table' => 'legacy_barangay_mappings',
                'fields' => [
                    'legacy_code' => ['label' => 'Legacy code', 'type' => 'text'],
                    'legacy_name' => ['label' => 'Legacy name', 'type' => 'text'],
                    'brgy_code' => ['label' => 'Project barangay', 'type' => 'barangay'],
                    'status' => [
                        'label' => 'Status',
                        'type' => 'select',
                        'default' => 'pending',
                        'options' => [
                            ['value' => 'mapped', 'label' => 'Mapped'],
                            ['value' => 'pending', 'label' => 'Pending'],
                            ['value' => 'ignored', 'label' => 'Ignored'],
                        ],
                    ],
                ],
                'rules' => [
                    'legacy_code' => ['required', 'string', 'max:50'],
                    'legacy_name' => ['required', 'string', 'max:255'],
                    'brgy_code' => array_values(array_filter([
                        'nullable',
                        'string',
                        Schema::hasTable('refbrgy') ? 'exists:refbrgy,brgyCode' : null,
                    ])),
                    'status' => ['required', Rule::in(['mapped', 'pending', 'ignored'])],
                ],
            ],
            default => abort(404),
        };
    }
}
