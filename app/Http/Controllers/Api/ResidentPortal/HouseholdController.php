<?php

namespace App\Http\Controllers\Api\ResidentPortal;

use App\Http\Controllers\Controller;
use App\Models\Resident;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HouseholdController extends Controller
{
    /**
     * Get the authenticated resident's household information.
     */
    public function show(Request $request): JsonResponse
    {
        $resident = $request->user();

        if (!$resident->household_id) {
            return response()->json([
                'message' => 'You are not assigned to any household.',
            ], 404);
        }

        $household = $resident->household;

        return response()->json([
            'data' => $household,
        ]);
    }

    /**
     * Get the members of the authenticated resident's household.
     */
    public function members(Request $request): JsonResponse
    {
        $resident = $request->user();

        if (!$resident->household_id) {
            return response()->json([
                'message' => 'You are not assigned to any household.',
            ], 404);
        }

        $members = Resident::where('household_id', $resident->household_id)
            ->where('is_active', true)
            ->select([
                'id', 'resident_id', 'first_name', 'last_name', 'middle_name',
                'suffix', 'birth_date', 'gender', 'relationship_to_head',
                'contact_number', 'photo_path',
            ])
            ->orderByRaw("FIELD(relationship_to_head, 'head', 'spouse') DESC, first_name ASC")
            ->get()
            ->map(function ($member) {
                $member->age = $member->getAge();
                $member->full_name = $member->full_name;
                return $member;
            });

        return response()->json([
            'data' => $members,
            'member_count' => $members->count(),
        ]);
    }
}
