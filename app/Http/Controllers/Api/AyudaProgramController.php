<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AyudaProgram;
use App\Models\Resident;
use App\Models\EligibilityCriteria;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AyudaProgramController extends Controller
{
    /**
     * Display a listing of the ayuda programs.
     */
    public function index(Request $request): JsonResponse
    {
        $query = AyudaProgram::query();

        // Apply filters if provided
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        // Apply sorting
        $sortField = $request->sortField ?? 'created_at';
        $sortDirection = $request->sortDirection ?? 'desc';
        $query->orderBy($sortField, $sortDirection);

        $perPage = $request->perPage ?? 10;
        $programs = $query->paginate($perPage);

        foreach ($programs as $program) {
            $program->append(['status', 'progress_percentage', 'remaining_budget']);
        }

        return response()->json([
            'data' => $programs->items(),
            'meta' => [
                'current_page' => $programs->currentPage(),
                'last_page' => $programs->lastPage(),
                'per_page' => $programs->perPage(),
                'total' => $programs->total()
            ]
        ]);
    }

    /**
     * Store a newly created ayuda program in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:50|unique:ayuda_programs,code',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|string|in:cash,goods,services,mixed',
            'amount' => 'nullable|numeric|min:0',
            'goods_description' => 'nullable|string|max:1000',
            'services_description' => 'nullable|string|max:1000',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'frequency' => 'nullable|string|max:50',
            'distribution_count' => 'nullable|integer|min:0',
            'total_budget' => 'nullable|numeric|min:0',
            'max_beneficiaries' => 'nullable|integer|min:0',
            'requires_verification' => 'boolean',
            'is_active' => 'boolean',
            'eligibility_criteria' => 'nullable|array',
            'eligibility_criteria.*.criterion_name' => 'required|string|max:100',
            'eligibility_criteria.*.criterion_type' => 'required|string|max:50',
            'eligibility_criteria.*.operator' => 'required|string|max:50',
            'eligibility_criteria.*.value' => 'required|string|max:255',
            'eligibility_criteria.*.is_required' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create the program
        $programData = $validator->validated();
        $eligibilityCriteria = null;

        if (isset($programData['eligibility_criteria'])) {
            $eligibilityCriteria = $programData['eligibility_criteria'];
            unset($programData['eligibility_criteria']);
        }

        // Ensure budget_used and current_beneficiaries start at 0
        $programData['budget_used'] = 0;
        $programData['current_beneficiaries'] = 0;

        $program = AyudaProgram::create($programData);

        // Add eligibility criteria if provided
        if ($eligibilityCriteria) {
            foreach ($eligibilityCriteria as $criterion) {
                $criterion['ayuda_program_id'] = $program->id;
                EligibilityCriteria::create($criterion);
            }
        }

        // Load the created criteria
        $program->load('eligibilityCriteria');

        return response()->json([
            'message' => 'Ayuda program created successfully',
            'data' => $program
        ], 201);
    }

    /**
     * Display the specified ayuda program.
     */
    public function show(string $id): JsonResponse
    {
        $program = AyudaProgram::with('eligibilityCriteria')->findOrFail($id);

        // Add computed attributes
        $program->append(['status', 'progress_percentage', 'remaining_budget']);

        return response()->json([
            'data' => $program
        ]);
    }

    /**
     * Update the specified ayuda program in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:100',
            'code' => 'nullable|string|max:50|unique:ayuda_programs,code,' . $id,
            'description' => 'nullable|string|max:1000',
            'type' => 'sometimes|required|string|in:cash,goods,services,mixed',
            'amount' => 'nullable|numeric|min:0',
            'goods_description' => 'nullable|string|max:1000',
            'services_description' => 'nullable|string|max:1000',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'frequency' => 'nullable|string|max:50',
            'distribution_count' => 'nullable|integer|min:0',
            'total_budget' => 'nullable|numeric|min:0',
            'max_beneficiaries' => 'nullable|integer|min:0',
            'requires_verification' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $program = AyudaProgram::findOrFail($id);
        $program->update($validator->validated());

        // Load the updated program with its criteria
        $program->load('eligibilityCriteria');

        // Add computed attributes
        $program->append(['status', 'progress_percentage', 'remaining_budget']);

        return response()->json([
            'message' => 'Ayuda program updated successfully',
            'data' => $program
        ]);
    }

    /**
     * Remove the specified ayuda program from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $program = AyudaProgram::findOrFail($id);

        // Check if the program has distributions
        $distributionCount = $program->distributions()->count();
        if ($distributionCount > 0) {
            return response()->json([
                'message' => 'Cannot delete program with existing distributions',
                'distribution_count' => $distributionCount
            ], 422);
        }

        // Delete associated eligibility criteria
        $program->eligibilityCriteria()->delete();

        // Delete the program
        $program->delete();

        return response()->json([
            'message' => 'Ayuda program deleted successfully'
        ]);
    }

    /**
     * Update the eligibility criteria for an ayuda program.
     */
    public function updateCriteria(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'criteria' => 'required|array',
            'criteria.*.id' => 'nullable|exists:eligibility_criteria,id',
            'criteria.*.criterion_name' => 'required|string|max:100',
            'criteria.*.criterion_type' => 'required|string|max:50',
            'criteria.*.operator' => 'required|string|max:50',
            'criteria.*.value' => 'required|string|max:255',
            'criteria.*.is_required' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $program = AyudaProgram::findOrFail($id);
        $criteria = $request->criteria;

        // Process each criterion
        foreach ($criteria as $criterionData) {
            if (isset($criterionData['id']) && $criterionData['id']) {
                // Update existing criterion
                $criterion = EligibilityCriteria::findOrFail($criterionData['id']);
                $criterion->update($criterionData);
            } else {
                // Create new criterion
                $criterionData['ayuda_program_id'] = $program->id;
                EligibilityCriteria::create($criterionData);
            }
        }

        // Handle deleted criteria (if any)
        if ($request->has('deleted_criteria_ids') && is_array($request->deleted_criteria_ids)) {
            EligibilityCriteria::whereIn('id', $request->deleted_criteria_ids)
                ->where('ayuda_program_id', $program->id)
                ->delete();
        }

        // Reload the program with updated criteria
        $program->load('eligibilityCriteria');

        return response()->json([
            'message' => 'Eligibility criteria updated successfully',
            'data' => $program->eligibilityCriteria
        ]);
    }

    /**
     * Check if a resident is eligible for a program.
     */
    public function checkEligibility(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'resident_id' => 'required|exists:residents,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $program = AyudaProgram::with('eligibilityCriteria')->findOrFail($id);
        $resident = Resident::with('household')->findOrFail($request->resident_id);

        $isEligible = true;
        $failedCriteria = [];

        // Check each criterion
        foreach ($program->eligibilityCriteria as $criterion) {
            $residentValue = null;

            // Get the resident value for this criterion
            switch ($criterion->criterion_type) {
                case 'age':
                    $residentValue = $resident->getAge();
                    break;
                case 'gender':
                    $residentValue = $resident->gender;
                    break;
                case 'civil_status':
                    $residentValue = $resident->civil_status;
                    break;
                case 'income':
                    $residentValue = $resident->monthly_income;
                    break;
                case 'household_income':
                    $residentValue = $resident->household ? $resident->household->monthly_income : null;
                    break;
                case 'household_size':
                    $residentValue = $resident->household ? $resident->household->member_count : null;
                    break;
                case 'location':
                case 'barangay':
                    $residentValue = $resident->household ? $resident->household->barangay : null;
                    break;
                case 'city':
                    $residentValue = $resident->household ? $resident->household->city_municipality : null;
                    break;
                case 'voter':
                    $residentValue = $resident->is_registered_voter;
                    break;
                case 'pwd':
                    $residentValue = $resident->is_pwd;
                    break;
                case 'senior':
                    $residentValue = $resident->is_senior_citizen;
                    break;
                case 'solo_parent':
                    $residentValue = $resident->is_solo_parent;
                    break;
                case 'pregnant':
                    $residentValue = $resident->is_pregnant;
                    break;
                case 'lactating':
                    $residentValue = $resident->is_lactating;
                    break;
                case 'indigenous':
                    $residentValue = $resident->is_indigenous;
                    break;
                case '4ps':
                    $residentValue = $resident->is_4ps;
                    break;
                case 'occupation':
                    $residentValue = $resident->occupation;
                    break;
                case 'education':
                    $residentValue = $resident->educational_attainment;
                    break;
            }

            // For non-required criteria, if the value is null, skip the check
            if (!$criterion->is_required && ($residentValue === null || $residentValue === '')) {
                continue;
            }

            // Check if the resident meets this criterion
            $meets = false;

            // Convert boolean strings to actual booleans
            $criterionValue = $criterion->value;
            if (in_array($criterionValue, ['true', 'false'])) {
                $criterionValue = $criterionValue === 'true';
            }

            switch ($criterion->operator) {
                case '=':
                case 'equals':
                case 'equal':
                    $meets = $residentValue == $criterionValue;
                    break;
                case '!=':
                case 'not_equals':
                case 'not_equal':
                    $meets = $residentValue != $criterionValue;
                    break;
                case '>':
                case 'greater_than':
                    $meets = $residentValue > $criterionValue;
                    break;
                case '>=':
                case 'greater_than_or_equal':
                    $meets = $residentValue >= $criterionValue;
                    break;
                case '<':
                case 'less_than':
                    $meets = $residentValue < $criterionValue;
                    break;
                case '<=':
                case 'less_than_or_equal':
                    $meets = $residentValue <= $criterionValue;
                    break;
                case 'in':
                    $values = explode(',', $criterionValue);
                    $meets = in_array($residentValue, $values);
                    break;
                case 'not_in':
                    $values = explode(',', $criterionValue);
                    $meets = !in_array($residentValue, $values);
                    break;
                case 'contains':
                    $meets = is_string($residentValue) &&
                             is_string($criterionValue) &&
                             str_contains($residentValue, $criterionValue);
                    break;
                case 'starts_with':
                    $meets = is_string($residentValue) &&
                             is_string($criterionValue) &&
                             str_starts_with($residentValue, $criterionValue);
                    break;
                case 'ends_with':
                    $meets = is_string($residentValue) &&
                             is_string($criterionValue) &&
                             str_ends_with($residentValue, $criterionValue);
                    break;
                case 'between':
                    list($min, $max) = explode(',', $criterionValue);
                    $meets = $residentValue >= $min && $residentValue <= $max;
                    break;
            }

            if (!$meets) {
                $isEligible = false;
                $failedCriteria[] = [
                    'criterion' => $criterion->criterion_name,
                    'resident_value' => $residentValue,
                    'required_value' => $criterion->value,
                    'operator' => $criterion->operator
                ];
            }
        }

        // Check for already received distributions
        $alreadyReceived = $resident->distributions()
            ->where('ayuda_program_id', $program->id)
            ->where('status', 'distributed')
            ->exists();

        if ($alreadyReceived) {
            $isEligible = false;
            $failedCriteria[] = [
                'criterion' => 'Previous Distribution',
                'resident_value' => 'Already received',
                'required_value' => 'No previous distribution',
                'operator' => 'Not applicable'
            ];
        }

        return response()->json([
            'data' => [
                'is_eligible' => $isEligible,
                'failed_criteria' => $failedCriteria,
                'resident' => [
                    'id' => $resident->id,
                    'name' => $resident->full_name
                ],
                'program' => [
                    'id' => $program->id,
                    'name' => $program->name
                ]
            ]
        ]);
    }

    /**
     * Get active programs.
     */
    public function active(): JsonResponse
    {
        $programs = AyudaProgram::active()->get();

        foreach ($programs as $program) {
            $program->append(['status', 'progress_percentage', 'remaining_budget']);
        }

        return response()->json($programs);
    }
}