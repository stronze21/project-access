<?php

namespace App\Http\Controllers\Api\ResidentPortal;

use App\Http\Controllers\Controller;
use App\Models\AyudaProgram;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    /**
     * List active ayuda programs visible to residents.
     */
    public function index(Request $request): JsonResponse
    {
        $query = AyudaProgram::where('is_active', true);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        $programs = $query->select([
                'id', 'name', 'code', 'description', 'type', 'amount',
                'goods_description', 'services_description',
                'start_date', 'end_date', 'frequency',
                'max_beneficiaries', 'current_beneficiaries',
                'is_active',
            ])
            ->orderBy('start_date', 'desc')
            ->paginate($request->per_page ?? 10);

        return response()->json([
            'data' => $programs->items(),
            'meta' => [
                'current_page' => $programs->currentPage(),
                'last_page' => $programs->lastPage(),
                'per_page' => $programs->perPage(),
                'total' => $programs->total(),
            ],
        ]);
    }

    /**
     * Get details of a specific program.
     */
    public function show(int $id): JsonResponse
    {
        $program = AyudaProgram::where('is_active', true)
            ->select([
                'id', 'name', 'code', 'description', 'type', 'amount',
                'goods_description', 'services_description',
                'start_date', 'end_date', 'frequency', 'distribution_count',
                'max_beneficiaries', 'current_beneficiaries',
                'requires_verification', 'is_active',
            ])
            ->with('eligibilityCriteria:id,ayuda_program_id,criterion_name,criterion_type,operator,value,is_required')
            ->findOrFail($id);

        return response()->json(['data' => $program]);
    }

    /**
     * Check if the authenticated resident is eligible for a program.
     */
    public function checkEligibility(Request $request, int $id): JsonResponse
    {
        $program = AyudaProgram::where('is_active', true)
            ->with('eligibilityCriteria')
            ->findOrFail($id);

        $resident = $request->user();
        $isEligible = $resident->isEligibleFor($program);

        $criteriaResults = [];
        foreach ($program->eligibilityCriteria as $criterion) {
            $criteriaResults[] = [
                'criterion' => $criterion->criterion_name,
                'type' => $criterion->criterion_type,
                'required' => $criterion->is_required,
                'met' => $criterion->checkEligibility($resident),
            ];
        }

        return response()->json([
            'eligible' => $isEligible,
            'program' => [
                'id' => $program->id,
                'name' => $program->name,
                'status' => $program->status,
                'remaining_slots' => $program->getRemainingBeneficiarySlots(),
            ],
            'criteria_results' => $criteriaResults,
        ]);
    }
}
