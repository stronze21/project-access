<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Distribution;
use App\Models\AyudaProgram;
use App\Models\Resident;
use App\Models\DistributionBatch;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DistributionController extends Controller
{
    /**
     * Display a listing of distributions.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Distribution::with(['resident', 'household', 'ayudaProgram', 'batch']);

        // Apply filters if provided
        if ($request->has('search') && $request->search) {
            $query->where('reference_number', 'like', '%' . $request->search . '%')
                ->orWhereHas('resident', function ($q) use ($request) {
                    $q->where('first_name', 'like', '%' . $request->search . '%')
                        ->orWhere('last_name', 'like', '%' . $request->search . '%')
                        ->orWhere('resident_id', 'like', '%' . $request->search . '%');
                });
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('program_id') && $request->program_id) {
            $query->where('ayuda_program_id', $request->program_id);
        }

        if ($request->has('batch_id') && $request->batch_id) {
            $query->where('batch_id', $request->batch_id);
        }

        if ($request->has('resident_id') && $request->resident_id) {
            $query->where('resident_id', $request->resident_id);
        }

        if ($request->has('household_id') && $request->household_id) {
            $query->where('household_id', $request->household_id);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Apply sorting
        $sortField = $request->sortField ?? 'created_at';
        $sortDirection = $request->sortDirection ?? 'desc';
        $query->orderBy($sortField, $sortDirection);

        $perPage = $request->perPage ?? 10;
        $distributions = $query->paginate($perPage);

        return response()->json([
            'data' => $distributions->items(),
            'meta' => [
                'current_page' => $distributions->currentPage(),
                'last_page' => $distributions->lastPage(),
                'per_page' => $distributions->perPage(),
                'total' => $distributions->total()
            ]
        ]);
    }

    /**
     * Store a newly created distribution in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ayuda_program_id' => 'required|exists:ayuda_programs,id',
            'resident_id' => 'required|exists:residents,id',
            'batch_id' => 'nullable|exists:distribution_batches,id',
            'created_at' => 'required|date',
            'amount' => 'nullable|numeric|min:0',
            'goods_details' => 'nullable|string|max:1000',
            'services_details' => 'nullable|string|max:1000',
            'status' => 'required|in:pending,distributed,cancelled',
            'notes' => 'nullable|string|max:1000',
            'verification_data' => 'nullable|json',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $program = AyudaProgram::findOrFail($request->ayuda_program_id);
        $resident = Resident::findOrFail($request->resident_id);

        // Check if the resident is eligible for the program
        if ($request->status === 'distributed' && !$program->isResidentEligible($resident)) {
            return response()->json([
                'message' => 'Resident is not eligible for this program.',
                'errors' => ['resident_id' => ['Resident is not eligible for this program.']]
            ], 422);
        }

        // Check budget and beneficiary limits if status is distributed
        if ($request->status === 'distributed') {
            // Check budget limit
            if ($program->total_budget && ($program->budget_used + ($request->amount ?? $program->amount)) > $program->total_budget) {
                return response()->json([
                    'message' => 'Program budget limit has been reached.',
                    'errors' => ['amount' => ['Program budget limit has been reached.']]
                ], 422);
            }

            // Check beneficiary limit
            if ($program->max_beneficiaries && ($program->current_beneficiaries + 1) > $program->max_beneficiaries) {
                return response()->json([
                    'message' => 'Program maximum beneficiaries limit has been reached.',
                    'errors' => ['resident_id' => ['Program maximum beneficiaries limit has been reached.']]
                ], 422);
            }
        }

        // Set household_id based on resident
        $data = $validator->validated();
        $data['household_id'] = $resident->household_id;

        // Set distributed_by to current user
        $data['distributed_by'] = Auth::id();

        // If amount not provided but program has a fixed amount
        if (!isset($data['amount']) && $program->amount) {
            $data['amount'] = $program->amount;
        }

        $distribution = Distribution::create($data);

        // Update batch statistics if assigned to a batch
        if ($distribution->batch_id) {
            $distribution->batch->updateStats();
        }

        return response()->json([
            'message' => 'Distribution created successfully',
            'data' => $distribution->load(['resident', 'household', 'ayudaProgram', 'batch'])
        ], 201);
    }

    /**
     * Display the specified distribution.
     */
    public function show(string $id): JsonResponse
    {
        $distribution = Distribution::with([
            'resident',
            'household',
            'ayudaProgram',
            'batch',
            'distributor',
            'verifier'
        ])->findOrFail($id);

        return response()->json([
            'data' => $distribution
        ]);
    }

    /**
     * Update the specified distribution in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $distribution = Distribution::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'batch_id' => 'nullable|exists:distribution_batches,id',
            'created_at' => 'sometimes|required|date',
            'amount' => 'nullable|numeric|min:0',
            'goods_details' => 'nullable|string|max:1000',
            'services_details' => 'nullable|string|max:1000',
            'status' => 'sometimes|required|in:pending,distributed,cancelled',
            'notes' => 'nullable|string|max:1000',
            'verification_data' => 'nullable|json',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if status changed to distributed
        $statusChanged = $request->has('status') &&
            $request->status === 'distributed' &&
            $distribution->status !== 'distributed';

        if ($statusChanged) {
            $program = $distribution->ayudaProgram;
            $resident = $distribution->resident;

            // Check if the resident is eligible
            if (!$program->isResidentEligible($resident)) {
                return response()->json([
                    'message' => 'Resident is not eligible for this program.',
                    'errors' => ['status' => ['Resident is not eligible for this program.']]
                ], 422);
            }

            // Check budget and beneficiary limits
            $amount = $request->amount ?? $distribution->amount;

            // Check budget limit
            if ($program->total_budget && ($program->budget_used + $amount) > $program->total_budget) {
                return response()->json([
                    'message' => 'Program budget limit has been reached.',
                    'errors' => ['status' => ['Program budget limit has been reached.']]
                ], 422);
            }

            // Check beneficiary limit
            if ($program->max_beneficiaries && ($program->current_beneficiaries + 1) > $program->max_beneficiaries) {
                return response()->json([
                    'message' => 'Program maximum beneficiaries limit has been reached.',
                    'errors' => ['status' => ['Program maximum beneficiaries limit has been reached.']]
                ], 422);
            }

            // Set verification timestamp and user if not set
            if ($program->requires_verification && !$distribution->verified_by) {
                $data = $validator->validated();
                $data['verified_by'] = Auth::id();
            }
        }

        // Update the distribution
        $data = $validator->validated();
        $distribution->update($data);

        // Update batch statistics if assigned to a batch
        if ($distribution->batch_id) {
            $distribution->batch->updateStats();
        }

        return response()->json([
            'message' => 'Distribution updated successfully',
            'data' => $distribution->load(['resident', 'household', 'ayudaProgram', 'batch'])
        ]);
    }

    /**
     * Remove the specified distribution from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $distribution = Distribution::findOrFail($id);

        // Don't allow deletion of distributed aid
        if ($distribution->status === 'distributed') {
            return response()->json([
                'message' => 'Cannot delete a distribution that has already been distributed.',
                'errors' => ['status' => ['Cannot delete a distributed aid.']]
            ], 422);
        }

        $distribution->delete();

        // Update batch statistics if assigned to a batch
        if ($distribution->batch_id) {
            $distribution->batch->updateStats();
        }

        return response()->json([
            'message' => 'Distribution deleted successfully'
        ]);
    }

    /**
     * Upload a receipt for a distribution.
     */
    public function uploadReceipt(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'receipt' => 'required|file|max:5120', // 5MB Max
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $distribution = Distribution::findOrFail($id);

        // Delete old receipt if exists
        if ($distribution->receipt_path && Storage::disk('public')->exists($distribution->receipt_path)) {
            Storage::disk('public')->delete($distribution->receipt_path);
        }

        // Store new receipt
        $receiptPath = $request->file('receipt')->store('distribution-receipts', 'public');
        $distribution->receipt_path = $receiptPath;
        $distribution->save();

        return response()->json([
            'message' => 'Receipt uploaded successfully',
            'data' => [
                'receipt_path' => $receiptPath,
                'receipt_url' => Storage::url($receiptPath)
            ]
        ]);
    }

    /**
     * Verify a distribution.
     */
    public function verify(Request $request, string $id): JsonResponse
    {
        $distribution = Distribution::findOrFail($id);

        if ($distribution->verified_by) {
            return response()->json([
                'message' => 'Distribution has already been verified.',
                'errors' => ['verified_by' => ['This distribution has already been verified.']]
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'verification_data' => 'nullable|json',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Update verification data
        $distribution->verified_by = Auth::id();

        if ($request->has('verification_data')) {
            $distribution->verification_data = $request->verification_data;
        }

        if ($request->has('notes')) {
            $distribution->notes = $request->notes;
        }

        $distribution->save();

        return response()->json([
            'message' => 'Distribution verified successfully',
            'data' => $distribution->load(['verifier'])
        ]);
    }

    /**
     * Get distributions by resident.
     */
    public function byResident(string $residentId): JsonResponse
    {
        $distributions = Distribution::with(['ayudaProgram', 'batch'])
            ->where('resident_id', $residentId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($distributions);
    }

    /**
     * Get distributions by household.
     */
    public function byHousehold(string $householdId): JsonResponse
    {
        $distributions = Distribution::with(['ayudaProgram', 'batch', 'resident'])
            ->where('household_id', $householdId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($distributions);
    }

    /**
     * Get distributions by program.
     */
    public function byProgram(string $programId, Request $request): JsonResponse
    {
        $query = Distribution::with(['resident', 'household', 'batch'])
            ->where('ayuda_program_id', $programId);

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $sortField = $request->sortField ?? 'created_at';
        $sortDirection = $request->sortDirection ?? 'desc';
        $query->orderBy($sortField, $sortDirection);

        $perPage = $request->perPage ?? 10;
        $distributions = $query->paginate($perPage);

        return response()->json([
            'data' => $distributions->items(),
            'meta' => [
                'current_page' => $distributions->currentPage(),
                'last_page' => $distributions->lastPage(),
                'per_page' => $distributions->perPage(),
                'total' => $distributions->total()
            ]
        ]);
    }

    /**
     * Get distributions by batch.
     */
    public function byBatch(string $batchId, Request $request): JsonResponse
    {
        $query = Distribution::with(['resident', 'household', 'ayudaProgram'])
            ->where('batch_id', $batchId);

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $sortField = $request->sortField ?? 'created_at';
        $sortDirection = $request->sortDirection ?? 'desc';
        $query->orderBy($sortField, $sortDirection);

        $perPage = $request->perPage ?? 10;
        $distributions = $query->paginate($perPage);

        return response()->json([
            'data' => $distributions->items(),
            'meta' => [
                'current_page' => $distributions->currentPage(),
                'last_page' => $distributions->lastPage(),
                'per_page' => $distributions->perPage(),
                'total' => $distributions->total()
            ]
        ]);
    }
}
