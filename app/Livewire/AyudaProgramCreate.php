<?php

namespace App\Livewire;

use App\Models\AyudaProgram;
use App\Models\EligibilityCriteria;
use Livewire\Component;
use Livewire\Attributes\Validate;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\DB;

class AyudaProgramCreate extends Component
{
    use Toast;

    // Program details
    #[Validate('required|string|max:100')]
    public $name = '';

    #[Validate('nullable|string|max:20')]
    public $code = '';

    #[Validate('nullable|string|max:1000')]
    public $description = '';

    #[Validate('required|in:cash,goods,services,mixed')]
    public $type = 'cash';

    #[Validate('nullable|numeric|min:0|max:9999999.99')]
    public $amount;

    #[Validate('nullable|string|max:1000')]
    public $goodsDescription = '';

    #[Validate('nullable|string|max:1000')]
    public $servicesDescription = '';

    #[Validate('required|date')]
    public $startDate;

    #[Validate('nullable|date|after_or_equal:startDate')]
    public $endDate;

    #[Validate('required|in:one-time,weekly,monthly,quarterly,annual')]
    public $frequency = 'one-time';

    #[Validate('required|integer|min:1|max:100')]
    public $distributionCount = 1;

    #[Validate('nullable|numeric|min:0|max:9999999999.99')]
    public $totalBudget;

    #[Validate('nullable|integer|min:0')]
    public $maxBeneficiaries;

    public $requiresVerification = false;
    public $isActive = true;

    // Eligibility criteria
    public $criteria = [];

    public $assistanceTypes = [
            ['id' => 'cash', 'name' => 'Cash Aid'],
            ['id' => 'goods', 'name' => 'Goods/Supplies'],
            ['id' => 'services', 'name' => 'Services'],
            ['id' => 'mixed', 'name' => 'Mixed (Cash & Goods/Services)'],
        ],
        $assistanceFrequencies = [
            ['id' => 'one-time', 'name' => 'One-time'],
            ['id' => 'weekly', 'name' => 'Weekly'],
            ['id' => 'monthly', 'name' => 'Monthly'],
            ['id' => 'quarterly', 'name' => 'Quarterly'],
            ['id' => 'annual', 'name' => 'Annual'],
        ];

    // Mode
    public $isEdit = false;
    public $programId = null;

    // Criterion types for dropdown
    public $criterionTypes = [
        ['key' => 'age', 'name' => 'Age'],
        ['key' => 'income', 'name' => 'Individual Income'],
        ['key' => 'household_income', 'name' => 'Household Income'],
        ['key' => 'household_size', 'name' => 'Household Size'],
        ['key' => 'location', 'name' => 'Barangay'],
        ['key' => 'city', 'name' => 'City/Municipality'],
        ['key' => 'gender', 'name' => 'Gender'],
        ['key' => 'civil_status', 'name' => 'Civil Status'],
        ['key' => 'voter', 'name' => 'Registered Voter'],
        ['key' => 'pwd', 'name' => 'Person with Disability'],
        ['key' => 'senior', 'name' => 'Senior Citizen'],
        ['key' => 'solo_parent', 'name' => 'Solo Parent'],
        ['key' => 'pregnant', 'name' => 'Pregnant'],
        ['key' => 'lactating', 'name' => 'Lactating Mother'],
        ['key' => 'indigenous', 'name' => 'Indigenous Person'],
        ['key' => 'education', 'name' => 'Educational Attainment'],
        ['key' => 'occupation', 'name' => 'Occupation']
    ];

    // Operators for dropdown
    public $operators = [
        ['key' => 'equals', 'name' => 'Equals (=)'],
        ['key' => 'not_equals', 'name' => 'Not Equals (!=)'],
        ['key' => 'greater_than', 'name' => 'Greater Than (>)'],
        ['key' => 'greater_than_or_equal', 'name' => 'Greater Than or Equal (>=)'],
        ['key' => 'less_than', 'name' => 'Less Than (<)'],
        ['key' => 'less_than_or_equal', 'name' => 'Less Than or Equal (<=)'],
        ['key' => 'in', 'name' => 'In (comma separated values)'],
        ['key' => 'not_in', 'name' => 'Not In (comma separated values)'],
        ['key' => 'contains', 'name' => 'Contains'],
        ['key' => 'not_contains', 'name' => 'Does Not Contain'],
    ];

    public $rules = [
        // Program details
        'name' => 'required|string|max:100',
        'code' => 'nullable|string|max:20|unique:ayuda_programs,code',
        'description' => 'nullable|string|max:1000',
        'type' => 'required|in:cash,goods,services,mixed',
        'amount' => 'nullable|numeric|min:0|max:9999999.99',
        'goodsDescription' => 'nullable|string|max:1000',
        'servicesDescription' => 'nullable|string|max:1000',
        'startDate' => 'required|date',
        'endDate' => 'nullable|date|after_or_equal:startDate',
        'frequency' => 'required|in:one-time,weekly,monthly,quarterly,annual',
        'distributionCount' => 'required|integer|min:1|max:100',
        'totalBudget' => 'nullable|numeric|min:0|max:9999999999.99',
        'maxBeneficiaries' => 'nullable|integer|min:0',
        'requiresVerification' => 'boolean',
        'isActive' => 'boolean',
    ];



    /**
     * Mount the component
     */
    public function mount($programId = null)
    {
        $this->startDate = now()->format('Y-m-d');

        // Add an empty criterion
        $this->addCriterion();

        if ($programId) {
            $this->loadProgram($programId);
        }
    }

    /**
     * Load program for editing
     */
    protected function loadProgram($programId)
    {
        $program = AyudaProgram::with('eligibilityCriteria')->findOrFail($programId);
        $this->programId = $program->id;
        $this->isEdit = true;

        // Program details
        $this->name = $program->name;
        $this->code = $program->code;
        $this->description = $program->description;
        $this->type = $program->type;
        $this->amount = $program->amount;
        $this->goodsDescription = $program->goods_description;
        $this->servicesDescription = $program->services_description;
        $this->startDate = $program->start_date->format('Y-m-d');
        $this->endDate = $program->end_date ? $program->end_date->format('Y-m-d') : null;
        $this->frequency = $program->frequency;
        $this->distributionCount = $program->distribution_count;
        $this->totalBudget = $program->total_budget;
        $this->maxBeneficiaries = $program->max_beneficiaries;
        $this->requiresVerification = $program->requires_verification;
        $this->isActive = $program->is_active;

        // Load criteria
        $this->criteria = [];
        foreach ($program->eligibilityCriteria as $criterion) {
            $this->criteria[] = [
                'id' => $criterion->id,
                'name' => $criterion->criterion_name,
                'type' => $criterion->criterion_type,
                'operator' => $criterion->operator,
                'value' => $criterion->value,
                'required' => $criterion->is_required
            ];
        }

        // Add empty criterion if none exist
        if (empty($this->criteria)) {
            $this->addCriterion();
        }
    }

    /**
     * Add a new criterion
     */
    public function addCriterion()
    {
        $this->criteria[] = [
            'id' => null,
            'name' => '',
            'type' => 'age',
            'operator' => 'greater_than_or_equal',
            'value' => '',
            'required' => true
        ];
    }

    /**
     * Remove a criterion
     */
    public function removeCriterion($index)
    {
        if (isset($this->criteria[$index])) {
            array_splice($this->criteria, $index, 1);
        }
    }

    /**
     * Validate criteria before submission
     */
    protected function validateCriteria()
    {
        $criteriaRules = [];

        foreach ($this->criteria as $index => $criterion) {
            if (!empty($criterion['name']) || !empty($criterion['value'])) {
                $criteriaRules["criteria.{$index}.name"] = 'required|string|max:100';
                $criteriaRules["criteria.{$index}.type"] = 'required|string';
                $criteriaRules["criteria.{$index}.operator"] = 'required|string';
                $criteriaRules["criteria.{$index}.value"] = 'required|string|max:255';
            }
        }

        return $this->validate($criteriaRules);
    }

    /**
     * Save the program
     */
    public function save()
    {
        $this->validate();

        if (!empty($this->criteria)) {
            $this->validateCriteria();
        }

        try {
            DB::beginTransaction();

            // Prepare program data
            $programData = [
                'name' => $this->name,
                'code' => $this->code,
                'description' => $this->description,
                'type' => $this->type,
                'amount' => $this->amount,
                'goods_description' => $this->goodsDescription,
                'services_description' => $this->servicesDescription,
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'frequency' => $this->frequency,
                'distribution_count' => $this->distributionCount,
                'total_budget' => $this->totalBudget,
                'max_beneficiaries' => $this->maxBeneficiaries,
                'requires_verification' => $this->requiresVerification,
                'is_active' => $this->isActive,
            ];

            // Create or update program
            if ($this->isEdit) {
                $program = AyudaProgram::findOrFail($this->programId);
                $program->update($programData);
            } else {
                $program = AyudaProgram::create($programData);
                $this->programId = $program->id;
            }

            // Handle eligibility criteria
            if (!empty($this->criteria)) {
                $this->saveCriteria($program);
            }

            DB::commit();

            $this->success($this->isEdit ? 'Program updated successfully!' : 'Program created successfully!');

            return redirect()->route('programs.show', $program->id);
        } catch (\Exception $e) {
            DB::rollBack();

            $this->error('Error: ' . $e->getMessage());
        }
    }

    /**
     * Save the eligibility criteria
     */
    protected function saveCriteria($program)
    {
        // Get existing criteria ids
        $existingIds = [];
        if ($this->isEdit) {
            $existingIds = $program->eligibilityCriteria()->pluck('id')->toArray();
        }

        // Track saved criteria ids
        $savedIds = [];

        // Create or update criteria
        foreach ($this->criteria as $criterion) {
            // Skip empty criteria
            if (empty($criterion['name']) && empty($criterion['value'])) {
                continue;
            }

            $criterionData = [
                'criterion_name' => $criterion['name'],
                'criterion_type' => $criterion['type'],
                'operator' => $criterion['operator'],
                'value' => $criterion['value'],
                'is_required' => $criterion['required'] ?? true,
            ];

            if (!empty($criterion['id'])) {
                // Update existing criterion
                $existingCriterion = EligibilityCriteria::find($criterion['id']);
                if ($existingCriterion) {
                    $existingCriterion->update($criterionData);
                    $savedIds[] = $existingCriterion->id;
                }
            } else {
                // Create new criterion
                $newCriterion = $program->eligibilityCriteria()->create($criterionData);
                $savedIds[] = $newCriterion->id;
            }
        }

        // Delete criteria that weren't updated
        $toDelete = array_diff($existingIds, $savedIds);
        if (!empty($toDelete)) {
            EligibilityCriteria::whereIn('id', $toDelete)->delete();
        }
    }

    /**
     * Reset the form
     */
    public function resetForm()
    {
        if (!$this->isEdit) {
            $this->reset([
                'name',
                'code',
                'description',
                'type',
                'amount',
                'goodsDescription',
                'servicesDescription',
                'startDate',
                'endDate',
                'frequency',
                'distributionCount',
                'totalBudget',
                'maxBeneficiaries',
                'requiresVerification',
                'isActive'
            ]);

            $this->startDate = now()->format('Y-m-d');
            $this->criteria = [];
            $this->addCriterion();
        } else {
            $this->loadProgram($this->programId);
        }
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.ayuda-program-create');
    }
}
