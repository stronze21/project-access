<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resident;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ResidentController extends Controller
{
    /**
     * Display a listing of residents.
     */
    public function index(Request $request): JsonResponse
    {
        \Log::info('Resident Index Request', [
            'request' => $request->all(),
        ]);
        $query = Resident::with('household');

        // Apply filters if provided
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->search . '%')
                    ->orWhere('last_name', 'like', '%' . $request->search . '%')
                    ->orWhere('middle_name', 'like', '%' . $request->search . '%')
                    ->orWhere('resident_id', 'like', '%' . $request->search . '%')
                    ->orWhere('qr_code', $request->search)
                    ->orWhere('rfid_number', $request->search);
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
            $query->whereHas('household', function ($q) use ($request) {
                $q->where('barangay', $request->barangay);
            });
        }

        if ($request->has('special_sector') && $request->special_sector) {
            $query->where('special_sector', $request->special_sector);
        }

        // Apply sorting
        $sortField = $request->sortField ?? 'created_at';
        $sortDirection = $request->sortDirection ?? 'desc';
        $query->orderBy($sortField, $sortDirection);

        $perPage = $request->perPage ?? 10;
        $residents = $query->paginate($perPage);

        return response()->json([
            'data' => $residents->items(),
            'meta' => [
                'current_page' => $residents->currentPage(),
                'last_page' => $residents->lastPage(),
                'per_page' => $residents->perPage(),
                'total' => $residents->total()
            ]
        ]);
    }

    /**
     * Store a newly created resident in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|min:2|max:50',
            'last_name' => 'required|string|min:2|max:50',
            'middle_name' => 'nullable|string|max:50',
            'suffix' => 'nullable|string|max:10',
            'birth_date' => 'required|date|before:today',
            'birthplace' => 'nullable|string|max:100',
            'gender' => 'required|string|in:male,female,other',
            'civil_status' => 'required|string|in:single,married,widowed,divorced,separated,other',
            'contact_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'occupation' => 'nullable|string|max:100',
            'monthly_income' => 'nullable|numeric|min:0|max:999999.99',
            'educational_attainment' => 'nullable|string',
            'blood_type' => 'nullable|string|max:10',
            'is_registered_voter' => 'boolean',
            'precinct_no' => 'nullable|string|max:50',
            'is_pwd' => 'boolean',
            'is_senior_citizen' => 'boolean',
            'is_solo_parent' => 'boolean',
            'is_pregnant' => 'boolean',
            'is_lactating' => 'boolean',
            'is_indigenous' => 'boolean',
            'is_4ps' => 'boolean',
            'special_sector' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'signature' => 'nullable|string',
            'date_issue' => 'nullable|date',
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_relationship' => 'nullable|string|max:50',
            'emergency_contact_number' => 'nullable|string|max:20',
            'rfid_number' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $resident = Resident::create($validator->validated());

        return response()->json([
            'message' => 'Resident created successfully',
            'data' => $resident
        ], 201);
    }

    /**
     * Display the specified resident.
     */
    public function show(string $id): JsonResponse
    {
        $resident = Resident::with('household')->findOrFail($id);

        return response()->json([
            'data' => $resident
        ]);
    }

    /**
     * Update the specified resident in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $resident = Resident::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|min:2|max:50',
            'last_name' => 'sometimes|required|string|min:2|max:50',
            'middle_name' => 'nullable|string|max:50',
            'suffix' => 'nullable|string|max:10',
            'birth_date' => 'sometimes|required|date|before:today',
            'birthplace' => 'nullable|string|max:100',
            'gender' => 'sometimes|required|string|in:male,female,other',
            'civil_status' => 'sometimes|required|string|in:single,married,widowed,divorced,separated,other',
            'contact_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'occupation' => 'nullable|string|max:100',
            'monthly_income' => 'nullable|numeric|min:0|max:999999.99',
            'educational_attainment' => 'nullable|string',
            'blood_type' => 'nullable|string|max:10',
            'is_registered_voter' => 'boolean',
            'precinct_no' => 'nullable|string|max:50',
            'is_pwd' => 'boolean',
            'is_senior_citizen' => 'boolean',
            'is_solo_parent' => 'boolean',
            'is_pregnant' => 'boolean',
            'is_lactating' => 'boolean',
            'is_indigenous' => 'boolean',
            'is_4ps' => 'boolean',
            'special_sector' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'signature' => 'nullable|string',
            'date_issue' => 'nullable|date',
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_relationship' => 'nullable|string|max:50',
            'emergency_contact_number' => 'nullable|string|max:20',
            'rfid_number' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $resident->update($validator->validated());

        return response()->json([
            'message' => 'Resident updated successfully',
            'data' => $resident
        ]);
    }

    /**
     * Remove the specified resident from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $resident = Resident::findOrFail($id);
        $resident->delete();

        return response()->json([
            'message' => 'Resident deleted successfully'
        ]);
    }

    /**
     * Update resident's household.
     */
    public function updateHousehold(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'household_id' => 'required|exists:households,id',
            'relationship_to_head' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $resident = Resident::findOrFail($id);
        $resident->household_id = $request->household_id;

        if ($request->has('relationship_to_head')) {
            $resident->relationship_to_head = $request->relationship_to_head;
        }

        $resident->save();

        return response()->json([
            'message' => 'Resident\'s household updated successfully',
            'data' => $resident->load('household')
        ]);
    }

    /**
     * Upload photo for a resident.
     */
    public function uploadPhoto(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|max:2048', // 2MB Max
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $resident = Resident::findOrFail($id);

        // Delete old photo if exists
        if ($resident->photo_path && Storage::disk('public')->exists($resident->photo_path)) {
            Storage::disk('public')->delete($resident->photo_path);
        }

        // Store new photo
        $photoPath = $request->file('photo')->store('resident-photos', 'public');
        $resident->photo_path = $photoPath;
        $resident->save();

        return response()->json([
            'message' => 'Photo uploaded successfully',
            'data' => [
                'photo_path' => $photoPath,
                'photo_url' => Storage::url($photoPath)
            ]
        ]);
    }

    /**
     * Get residents by household.
     */
    public function byHousehold(string $householdId): JsonResponse
    {
        $residents = Resident::where('household_id', $householdId)
            ->orderBy('relationship_to_head', 'asc')
            ->orderBy('last_name', 'asc')
            ->get();

        return response()->json($residents);
    }

    /**
     * Generate QR code for a resident.
     */
    public function generateQrCode(string $id): JsonResponse
    {
        $resident = Resident::findOrFail($id);
        $qrCode = $resident->generateQrCode();

        return response()->json([
            'message' => 'QR code generated successfully',
            'data' => [
                'qr_code' => $qrCode
            ]
        ]);
    }

    /**
     * Get residents with pending signatures.
     */
    public function pendingSignatures(): JsonResponse
    {
        $residents = Resident::where('signature_status', 'pending')
            ->orWhere('signature', null)
            ->select(
                'id',
                'resident_id',
                'first_name',
                'last_name',
                'middle_name',
                'contact_number',
                'email',
                'updated_at'
            )
            ->orderBy('updated_at', 'desc')
            ->get();

        // Add full name attribute to each resident
        foreach ($residents as $resident) {
            $resident->append('full_name');
        }
        return response()->json($residents);
    }

    /**
     * Update resident's signature.
     */
    public function updateSignature(Request $request, string $id): JsonResponse
    {
        Log::info('Update Signature Request', [
            'request' => $request->all(),
            'resident_id' => $id,
        ]);
        $validator = Validator::make($request->all(), [
            'signature' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $resident = Resident::findOrFail($id);
        $resident->signature = $request->signature;
        $resident->signature_status = 'completed';
        $resident->save();

        return response()->json([
            'message' => 'Signature updated successfully',
            'data' => [
                'resident_id' => $resident->resident_id,
                'full_name' => $resident->full_name,
                'signature_status' => $resident->signature_status
            ]
        ]);
    }

    // ==================== ID CARD PRINTING ENDPOINTS ====================

    /**
     * Get resident data for ID card printing by resident_id
     */
    public function getForIdCard(string $residentId): JsonResponse
    {
        try {
            $resident = Resident::with(['household'])
                ->where('resident_id', $residentId)
                ->first();

            if (!$resident) {
                return response()->json([
                    'error' => 'Resident not found',
                    'message' => "No resident found with ID: {$residentId}"
                ], 404);
            }

            // Safe formatting
            $birthDate = 'N/A';
            if (isset($resident->birth_date)) {
                try {
                    $birthDate = \Carbon\Carbon::parse($resident->birth_date)->format('F d, Y');
                } catch (\Exception $e) {
                    $birthDate = (string)$resident->birth_date;
                }
            }

            $dateIssue = now()->format('m/d/Y');
            if (isset($resident->date_issue)) {
                try {
                    $dateIssue = \Carbon\Carbon::parse($resident->date_issue)->format('m/d/Y');
                } catch (\Exception $e) {
                }
            }

            $photoUrl = !empty($resident->photo_path)
                ? url('storage/' . $resident->photo_path)
                : null;

            // Handle emergency contact (flexible)
            $emergencyContact = 'N/A';
            if (!empty($resident->emergency_contact)) {
                $emergencyContact = $resident->emergency_contact;
            } elseif (!empty($resident->emergency_contact_name) || !empty($resident->emergency_contact_number)) {
                $parts = array_filter([
                    $resident->emergency_contact_name ?? '',
                    $resident->emergency_contact_number ?? ''
                ]);
                $emergencyContact = implode(' - ', $parts);
            }

            $data = [
                'resident_id' => $resident->resident_id ?? '',
                'first_name' => strtoupper($resident->first_name ?? ''),
                'middle_name' => strtoupper($resident->middle_name ?? ''),
                'last_name' => strtoupper($resident->last_name ?? ''),
                'full_name' => $resident->full_name ?? strtoupper(trim(($resident->first_name ?? '') . ' ' . ($resident->last_name ?? ''))),
                'birth_date' => $birthDate,
                'gender' => strtolower($resident->gender ?? 'N/A'),
                'civil_status' => strtolower($resident->civil_status ?? 'N/A'),
                'birthplace' => $resident->birthplace ?? 'N/A',
                'emergency_contact' => $emergencyContact,
                'occupation' => $resident->occupation ?? 'N/A',
                'special_sector' => $resident->special_sector ?? 'NONE',
                'date_issue' => $dateIssue,
                'photo_path' => $photoUrl,
                'signature' => $resident->signature ?? null,
                'household' => [
                    'address' => optional($resident->household)->address ?? 'N/A',
                    'barangay' => optional($resident->household)->barangay ?? 'N/A',
                    'city_municipality' => optional($resident->household)->city_municipality ?? 'Alicia',
                    'province' => optional($resident->household)->province ?? 'Isabela',
                ]
            ];

            return response()->json($data, 200);
        } catch (\Exception $e) {
            \Log::error('ID Card API Error', [
                'resident_id' => $residentId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get multiple residents for batch ID card printing
     */
    public function batchForIdCard(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'resident_ids' => 'required|array',
                'resident_ids.*' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation error',
                    'message' => $validator->errors()
                ], 422);
            }

            $residentIds = $request->input('resident_ids');

            $residents = Resident::with(['household'])
                ->whereIn('resident_id', $residentIds)
                ->get();

            if ($residents->isEmpty()) {
                return response()->json([
                    'error' => 'No residents found',
                    'message' => 'None of the provided resident IDs were found'
                ], 404);
            }

            $data = $residents->map(function ($resident) {
                // Format birth date
                $birthDate = $resident->birth_date
                    ? $resident->birth_date->format('F d, Y')
                    : 'N/A';

                // Format date issue
                $dateIssue = $resident->date_issue
                    ? $resident->date_issue->format('m/d/Y')
                    : now()->format('m/d/Y');

                // Get photo URL
                $photoUrl = $resident->photo_path
                    ? url('storage/' . $resident->photo_path)
                    : null;

                // Get signature base64
                $signature = $resident->signature ?? null;

                // Format emergency contact
                $emergencyContact = 'N/A';
                if ($resident->emergency_contact_name && $resident->emergency_contact_number) {
                    $emergencyContact = $resident->emergency_contact_name . ' - ' . $resident->emergency_contact_number;
                } elseif ($resident->emergency_contact_name) {
                    $emergencyContact = $resident->emergency_contact_name;
                } elseif ($resident->emergency_contact_number) {
                    $emergencyContact = $resident->emergency_contact_number;
                }

                return [
                    'resident_id' => $resident->resident_id,
                    'first_name' => strtoupper($resident->first_name),
                    'middle_name' => $resident->middle_name ? strtoupper($resident->middle_name) : '',
                    'last_name' => strtoupper($resident->last_name),
                    'full_name' => $resident->full_name,
                    'birth_date' => $birthDate,
                    'gender' => strtolower($resident->gender),
                    'civil_status' => strtolower($resident->civil_status),
                    'birthplace' => $resident->birthplace ?? 'N/A',
                    'emergency_contact' => $emergencyContact,
                    'occupation' => $resident->occupation ?? 'N/A',
                    'special_sector' => $resident->special_sector ?? 'NONE',
                    'date_issue' => $dateIssue,
                    'photo_path' => $photoUrl,
                    'signature' => $signature,
                    'household' => [
                        'address' => $resident->household->address ?? 'N/A',
                        'barangay' => $resident->household->barangay ?? 'N/A',
                        'city_municipality' => $resident->household->city_municipality ?? 'Alicia',
                        'province' => $resident->household->province ?? 'Isabela',
                    ]
                ];
            });

            return response()->json([
                'count' => $data->count(),
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            Log::error('Batch ID Card Error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search residents for ID card printing
     */
    public function searchForIdCard(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'query' => 'required|string|min:2'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation error',
                    'message' => $validator->errors()
                ], 422);
            }

            $query = $request->input('query');

            $residents = Resident::with(['household'])
                ->where(function ($q) use ($query) {
                    $q->where('first_name', 'like', "%{$query}%")
                        ->orWhere('last_name', 'like', "%{$query}%")
                        ->orWhere('resident_id', 'like', "%{$query}%");
                })
                ->limit(50)
                ->get();

            if ($residents->isEmpty()) {
                return response()->json([
                    'count' => 0,
                    'data' => []
                ], 200);
            }

            $data = $residents->map(function ($resident) {
                return [
                    'resident_id' => $resident->resident_id,
                    'full_name' => $resident->full_name,
                    'address' => $resident->household
                        ? $resident->household->address . ', ' . $resident->household->barangay
                        : 'N/A',
                    'photo_url' => $resident->photo_path
                        ? url('storage/' . $resident->photo_path)
                        : null,
                    'has_signature' => !empty($resident->signature)
                ];
            });

            return response()->json([
                'count' => $data->count(),
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            Log::error('Search ID Card Error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all residents allowed to print ID cards
     */
    public function getAllowedForIdCard(): JsonResponse
    {
        try {
            // Get residents with specific IDs that are allowed to print ID cards
            $allowedIds = [
                'R-202503-0618',
                'R-202503-0751',
                'R-202503-0620',
                'R-202503-0755',
                'R-202504-2467'
            ];

            $residents = Resident::with(['household'])
                ->whereIn('resident_id', $allowedIds)
                ->get();

            $data = $residents->map(function ($resident) {
                return [
                    'resident_id' => $resident->resident_id,
                    'full_name' => $resident->full_name,
                    'address' => $resident->household
                        ? $resident->household->address . ', ' . $resident->household->barangay
                        : 'N/A',
                    'has_signature' => !empty($resident->signature),
                    'has_photo' => !empty($resident->photo_path)
                ];
            });

            return response()->json([
                'count' => $data->count(),
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            Log::error('Allowed ID Card Error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
