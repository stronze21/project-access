<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Household;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class HouseholdController extends Controller
{
    /**
     * Display a listing of households.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Household::query();

        // Apply filters if provided
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('household_id', 'like', '%' . $request->search . '%')
                  ->orWhere('address', 'like', '%' . $request->search . '%')
                  ->orWhere('barangay', 'like', '%' . $request->search . '%')
                  ->orWhere('qr_code', $request->search);
            });
        }

        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->has('barangay') && $request->barangay) {
            $query->where('barangay', $request->barangay);
        }

        // Apply sorting
        $sortField = $request->sortField ?? 'created_at';
        $sortDirection = $request->sortDirection ?? 'desc';
        $query->orderBy($sortField, $sortDirection);

        $perPage = $request->perPage ?? 10;
        $households = $query->withCount('residents')->paginate($perPage);

        return response()->json([
            'data' => $households->items(),
            'meta' => [
                'current_page' => $households->currentPage(),
                'last_page' => $households->lastPage(),
                'per_page' => $households->perPage(),
                'total' => $households->total()
            ]
        ]);
    }

    /**
     * Store a newly created household in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'address' => 'required|string|max:255',
            'barangay' => 'required|string|max:100',
            'barangay_code' => 'required|string|max:20',
            'city_municipality' => 'required|string|max:100',
            'city_municipality_code' => 'required|string|max:20',
            'province' => 'required|string|max:100',
            'province_code' => 'required|string|max:20',
            'region' => 'required|string|max:100',
            'region_code' => 'required|string|max:20',
            'postal_code' => 'nullable|string|max:20',
            'monthly_income' => 'nullable|numeric|min:0|max:999999.99',
            'dwelling_type' => 'nullable|string|max:50',
            'has_electricity' => 'boolean',
            'has_water_supply' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Generate household ID if not provided
        $data = $validator->validated();
        if (!isset($data['household_id'])) {
            $data['household_id'] = Household::generateHouseholdId();
        }

        $household = Household::create($data);

        return response()->json([
            'message' => 'Household created successfully',
            'data' => $household
        ], 201);
    }

    /**
     * Display the specified household.
     */
    public function show(string $id): JsonResponse
    {
        $household = Household::with('residents')->findOrFail($id);

        return response()->json([
            'data' => $household
        ]);
    }

    /**
     * Update the specified household in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'address' => 'sometimes|required|string|max:255',
            'barangay' => 'sometimes|required|string|max:100',
            'barangay_code' => 'sometimes|required|string|max:20',
            'city_municipality' => 'sometimes|required|string|max:100',
            'city_municipality_code' => 'sometimes|required|string|max:20',
            'province' => 'sometimes|required|string|max:100',
            'province_code' => 'sometimes|required|string|max:20',
            'region' => 'sometimes|required|string|max:100',
            'region_code' => 'sometimes|required|string|max:20',
            'postal_code' => 'nullable|string|max:20',
            'monthly_income' => 'nullable|numeric|min:0|max:999999.99',
            'dwelling_type' => 'nullable|string|max:50',
            'has_electricity' => 'boolean',
            'has_water_supply' => 'boolean',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $household = Household::findOrFail($id);
        $household->update($validator->validated());

        return response()->json([
            'message' => 'Household updated successfully',
            'data' => $household
        ]);
    }

    /**
     * Remove the specified household from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $household = Household::findOrFail($id);

        // Check if the household has residents
        $residentCount = $household->residents()->count();
        if ($residentCount > 0) {
            return response()->json([
                'message' => 'Cannot delete household with residents',
                'resident_count' => $residentCount
            ], 422);
        }

        $household->delete();

        return response()->json([
            'message' => 'Household deleted successfully'
        ]);
    }

    /**
     * Get all residents in a household.
     */
    public function residents(string $id): JsonResponse
    {
        $household = Household::findOrFail($id);
        $residents = $household->residents()->orderBy('relationship_to_head', 'asc')->get();

        return response()->json($residents);
    }

    /**
     * Generate QR code for a household.
     */
    public function generateQrCode(string $id): JsonResponse
    {
        $household = Household::findOrFail($id);

        // Using the QrCodeService logic from the model
        if (!$household->qr_code) {
            $qrCode = 'HH-' . strtoupper(substr(md5($household->id . time()), 0, 10));
            $household->qr_code = $qrCode;
            $household->save();
        }

        return response()->json([
            'message' => 'QR code generated successfully',
            'data' => [
                'qr_code' => $household->qr_code
            ]
        ]);
    }

    /**
     * Update member count and total income for a household.
     */
    public function updateStats(string $id): JsonResponse
    {
        $household = Household::findOrFail($id);
        $household->updateMemberCount();
        $household->calculateTotalIncome();

        return response()->json([
            'message' => 'Household statistics updated successfully',
            'data' => [
                'member_count' => $household->member_count,
                'monthly_income' => $household->monthly_income
            ]
        ]);
    }
}
