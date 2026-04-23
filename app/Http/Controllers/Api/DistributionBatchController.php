<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DistributionBatch;
use App\Models\AyudaProgram;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class DistributionBatchController extends Controller
{
    /**
     * Display a listing of distribution batches.
     */
    public function index(Request $request): JsonResponse
    {
        $query = DistributionBatch::with('ayudaProgram');

        // Apply filters if provided
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('batch_number', 'like', '%' . $request->search . '%')
                  ->orWhere('location', 'like', '%' . $request->search . '%')
                  ->orWhereHas('ayudaProgram', function ($sq) use ($request) {
                      $sq->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('code', 'like', '%' . $request->search . '%');
                  });
            });
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('program_id') && $request->program_id) {
            $query->where('ayuda_program_id', $request->program_id);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('batch_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('batch_date', '<=', $request->date_to);
        }

        // Apply today filter
        if ($request->has('today') && $request->today) {
            $query->whereDate('batch_date', now()->toDateString());
        }

        // Apply sorting
        $sortField = $request->sortField ?? 'batch_date';
        $sortDirection = $request->sortDirection ?? 'desc';
        $query->orderBy($sortField, $sortDirection);

        $perPage = $request->perPage ?? 10;
        $batches = $query->paginate($perPage);

        // Append completion percentage to each batch
        foreach ($batches as $batch) {
            $batch->append('completion_percentage');
        }

        return response()->json([
            'data' => $batches->items(),
            'meta' => [
                'current_page' => $batches->currentPage(),
                'last_page' => $batches->lastPage(),
                'per_page' => $batches->perPage(),
                'total' => $batches->total()
            ]
        ]);
    }

    /**
     * Store a newly created distribution batch in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ayuda_program_id' => 'required|exists:ayuda_programs,id',
            'batch_number' => 'nullable|string|max:50',
            'location' => 'required|string|max:255',
            'batch_date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'target_beneficiaries' => 'nullable|integer|min:1',
            'status' => 'required|in:scheduled,ongoing,completed,cancelled',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Set created_by to current user
        $data = $validator->validated();
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        // Generate batch number if not provided
        if (!isset($data['batch_number']) || !$data['batch_number']) {
            $data['batch_number'] = DistributionBatch::generateBatchNumber($data['ayuda_program_id']);
        }

        // Set initial values for actual beneficiaries and total amount
        $data['actual_beneficiaries'] = 0;
        $data['total_amount'] = 0;

        $batch = DistributionBatch::create($data);

        return response()->json([
            'message' => 'Distribution batch created successfully',
            'data' => $batch->load('ayudaProgram')
        ], 201);
    }

    /**
     * Display the specified distribution batch.
     */
    public function show(string $id): JsonResponse
    {
        $batch = DistributionBatch::with(['ayudaProgram', 'creator', 'updater'])
            ->withCount('distributions')
            ->findOrFail($id);

        // Append completion percentage
        $batch->append('completion_percentage');

        return response()->json([
            'data' => $batch
        ]);
    }

    /**
     * Update the specified distribution batch in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'location' => 'sometimes|required|string|max:255',
            'batch_date' => 'sometimes|required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'target_beneficiaries' => 'nullable|integer|min:1',
            'status' => 'sometimes|required|in:scheduled,ongoing,completed,cancelled',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $batch = DistributionBatch::findOrFail($id);

        // Set updated_by to current user
        $data = $validator->validated();
        $data['updated_by'] = Auth::id();

        $batch->update($data);

        // Append completion percentage
        $batch->append('completion_percentage');

        return response()->json([
            'message' => 'Distribution batch updated successfully',
            'data' => $batch->load(['ayudaProgram', 'creator', 'updater'])
        ]);
    }

    /**
     * Remove the specified distribution batch from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $batch = DistributionBatch::withCount('distributions')->findOrFail($id);

        // Check if batch has distributions
        if ($batch->distributions_count > 0) {
            return response()->json([
                'message' => 'Cannot delete batch with distributions',
                'errors' => ['batch' => ['This batch has associated distributions and cannot be deleted.']]
            ], 422);
        }

        $batch->delete();

        return response()->json([
            'message' => 'Distribution batch deleted successfully'
        ]);
    }

    /**
     * Get all distributions for a batch.
     */
    public function distributions(string $id, Request $request): JsonResponse
    {
        $batch = DistributionBatch::findOrFail($id);

        $query = $batch->distributions()->with(['resident', 'household', 'ayudaProgram']);

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
     * Update batch statistics (total beneficiaries and amount).
     */
    public function updateStats(string $id): JsonResponse
    {
        $batch = DistributionBatch::findOrFail($id);
        $batch->updateStats();

        return response()->json([
            'message' => 'Batch statistics updated successfully',
            'data' => [
                'actual_beneficiaries' => $batch->actual_beneficiaries,
                'total_amount' => $batch->total_amount,
                'completion_percentage' => $batch->completion_percentage
            ]
        ]);
    }

    /**
     * Get today's batches.
     */
    public function today(): JsonResponse
    {
        $batches = DistributionBatch::with('ayudaProgram')
            ->whereDate('batch_date', now()->toDateString())
            ->orderBy('start_time')
            ->get();

        // Append completion percentage
        foreach ($batches as $batch) {
            $batch->append('completion_percentage');
        }

        return response()->json($batches);
    }

    /**
     * Get active (scheduled or ongoing) batches.
     */
    public function active(): JsonResponse
    {
        $batches = DistributionBatch::with('ayudaProgram')
            ->whereIn('status', ['scheduled', 'ongoing'])
            ->orderBy('batch_date')
            ->orderBy('start_time')
            ->get();

        // Append completion percentage
        foreach ($batches as $batch) {
            $batch->append('completion_percentage');
        }

        return response()->json($batches);
    }
}
