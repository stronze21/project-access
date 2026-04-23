<?php

namespace App\Http\Controllers\Api\ResidentPortal;

use App\Http\Controllers\Controller;
use App\Models\Distribution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DistributionController extends Controller
{
    /**
     * Get the authenticated resident's distribution history.
     */
    public function index(Request $request): JsonResponse
    {
        $resident = $request->user();

        $query = Distribution::where('resident_id', $resident->id)
            ->with([
                'ayudaProgram:id,name,code,type',
                'batch:id,batch_number,location,batch_date',
            ]);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->program_id) {
            $query->where('ayuda_program_id', $request->program_id);
        }

        if ($request->year) {
            $query->whereYear('distribution_date', $request->year);
        }

        $distributions = $query->orderByRaw("CASE WHEN status IN ('pending', 'verified') THEN 0 ELSE 1 END")
            ->orderBy('distribution_date', 'desc')
            ->paginate($request->per_page ?? 10);

        return response()->json([
            'data' => $distributions->items(),
            'meta' => [
                'current_page' => $distributions->currentPage(),
                'last_page' => $distributions->lastPage(),
                'per_page' => $distributions->perPage(),
                'total' => $distributions->total(),
            ],
        ]);
    }

    /**
     * Get details of a specific distribution.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $resident = $request->user();

        $distribution = Distribution::where('resident_id', $resident->id)
            ->with([
                'ayudaProgram:id,name,code,type,description',
                'batch:id,batch_number,location,batch_date,start_time,end_time',
            ])
            ->findOrFail($id);

        return response()->json(['data' => $distribution]);
    }

    /**
     * Get a summary of the resident's distribution history.
     */
    public function summary(Request $request): JsonResponse
    {
        $resident = $request->user();

        $distributions = Distribution::where('resident_id', $resident->id);

        $totalReceived = (clone $distributions)->where('status', 'distributed')->count();
        $totalAmount = (clone $distributions)->where('status', 'distributed')->sum('amount');
        $pending = (clone $distributions)->where('status', 'pending')->count();

        $byProgram = Distribution::where('resident_id', $resident->id)
            ->where('status', 'distributed')
            ->selectRaw('ayuda_program_id, COUNT(*) as count, SUM(amount) as total_amount')
            ->groupBy('ayuda_program_id')
            ->with('ayudaProgram:id,name,type')
            ->get();

        $byYear = Distribution::where('resident_id', $resident->id)
            ->where('status', 'distributed')
            ->selectRaw('YEAR(distribution_date) as year, COUNT(*) as count, SUM(amount) as total_amount')
            ->groupBy('year')
            ->orderBy('year', 'desc')
            ->get();

        return response()->json([
            'total_distributions_received' => $totalReceived,
            'total_amount_received' => (float) $totalAmount,
            'pending_distributions' => $pending,
            'by_program' => $byProgram,
            'by_year' => $byYear,
        ]);
    }

    /**
     * Get the resident's ayuda list — distributions related to the user,
     * ordered by undistributed (pending/verified) first.
     */
    public function ayuda(Request $request): JsonResponse
    {
        $resident = $request->user();

        $query = Distribution::where('resident_id', $resident->id)
            ->with([
                'ayudaProgram:id,name,code,type,amount,description,start_date,end_date,frequency',
                'batch:id,batch_number,location,batch_date,start_time,end_time',
            ]);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->program_id) {
            $query->where('ayuda_program_id', $request->program_id);
        }

        $distributions = $query
            ->orderByRaw("CASE WHEN status IN ('pending', 'verified') THEN 0 ELSE 1 END")
            ->orderBy('distribution_date', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'data' => $distributions->items(),
            'meta' => [
                'current_page' => $distributions->currentPage(),
                'last_page' => $distributions->lastPage(),
                'per_page' => $distributions->perPage(),
                'total' => $distributions->total(),
            ],
        ]);
    }

    /**
     * Get upcoming distribution schedules for the resident.
     */
    public function upcoming(Request $request): JsonResponse
    {
        $resident = $request->user();

        $upcoming = Distribution::where('resident_id', $resident->id)
            ->whereIn('status', ['pending', 'verified'])
            ->with([
                'ayudaProgram:id,name,code,type',
                'batch:id,batch_number,location,batch_date,start_time,end_time',
            ])
            ->orderBy('distribution_date', 'asc')
            ->limit(10)
            ->get();

        return response()->json(['data' => $upcoming]);
    }
}
