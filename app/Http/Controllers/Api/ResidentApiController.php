<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resident;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ResidentApiController extends Controller
{
    /**
     * Get resident data by ID
     *
     * @param string $residentId
     * @return JsonResponse
     */
    public function show(string $residentId): JsonResponse
    {
        try {
            // Find resident by resident_id
            $resident = Resident::with(['household'])
                ->where('resident_id', $residentId)
                ->firstOrFail();

            // Format birth date
            $birthDate = $resident->birth_date
                ? $resident->birth_date->format('F d, Y')
                : 'N/A';

            // Format date issue
            $dateIssue = $resident->date_issue
                ? $resident->date_issue->format('m/d/Y')
                : now()->format('m/d/Y');

            // Get photo URL (full URL)
            $photoUrl = $resident->photo_path
                ? url('storage/' . $resident->photo_path)
                : null;

            // Get signature base64
            $signature = $resident->signature ?? null;

            // Prepare response data
            $data = [
                'resident_id' => $resident->resident_id,
                'first_name' => strtoupper($resident->first_name),
                'middle_name' => $resident->middle_name ? strtoupper($resident->middle_name) : '',
                'last_name' => strtoupper($resident->last_name),
                'full_name' => $resident->full_name,
                'birth_date' => $birthDate,
                'gender' => strtolower($resident->gender),
                'civil_status' => strtolower($resident->civil_status),
                'birthplace' => $resident->birthplace ?? 'N/A',
                'emergency_contact' => $resident->emergency_contact ?? 'N/A',
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

            return response()->json($data, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Resident not found',
                'message' => "No resident found with ID: {$residentId}"
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get multiple residents for batch printing
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function batch(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'resident_ids' => 'required|array',
                'resident_ids.*' => 'required|string'
            ]);

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
                    'emergency_contact' => $resident->emergency_contact ?? 'N/A',
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
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation error',
                'message' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search residents by name
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'query' => 'required|string|min:2'
            ]);

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
                        : null
                ];
            });

            return response()->json([
                'count' => $data->count(),
                'data' => $data
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation error',
                'message' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all residents with allowed IDs for ID card printing
     *
     * @return JsonResponse
     */
    public function getAllowedResidents(): JsonResponse
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
                ];
            });

            return response()->json([
                'count' => $data->count(),
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
