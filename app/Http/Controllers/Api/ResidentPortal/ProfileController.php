<?php

namespace App\Http\Controllers\Api\ResidentPortal;

use App\Http\Controllers\Controller;
use App\Services\ResidentIdentityChangeRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function __construct(private ResidentIdentityChangeRequestService $identityChanges) {}

    /**
     * Get the authenticated resident's profile.
     */
    public function show(Request $request): JsonResponse
    {
        $resident = $request->user()->load(['household', 'sourceIncomeType']);

        return response()->json([
            'data' => $resident,
            'age' => $resident->getAge(),
            'full_name' => $resident->full_name,
        ]);
    }

    /**
     * Update the authenticated resident's profile (limited fields).
     */
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'contact_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'occupation' => 'nullable|string|max:100',
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_relationship' => 'nullable|string|max:50',
            'emergency_contact_number' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $resident = $request->user();

        // Only allow residents to update limited contact/emergency fields
        $resident->update($request->only([
            'contact_number',
            'email',
            'occupation',
            'emergency_contact_name',
            'emergency_contact_relationship',
            'emergency_contact_number',
        ]));

        return response()->json([
            'message' => 'Profile updated successfully',
            'data' => $resident->fresh()->load(['household', 'sourceIncomeType']),
        ]);
    }

    /** Submit a verified replacement request; never directly changes identity media. */
    public function uploadPhoto(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'reason' => 'required|string|min:10|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $changeRequest = $this->identityChanges->submitPhoto($request->user(), $request->file('photo'), $request->string('reason')->toString());

        return response()->json([
            'message' => 'Profile photo replacement request submitted for identity verification.',
            'request' => ['reference_number' => $changeRequest->reference_number, 'status' => $changeRequest->status],
            'photo_url' => null,
        ], 202);
    }

    /** Submit a verified replacement request; never directly changes identity media. */
    public function uploadSignature(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'signature' => 'required|string|starts_with:data:image/png;base64,|max:1500000',
            'reason' => 'required|string|min:10|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $changeRequest = $this->identityChanges->submitSignature($request->user(), $request->string('signature')->toString(), $request->string('reason')->toString());

        return response()->json([
            'message' => 'Signature replacement request submitted for identity verification.',
            'request' => ['reference_number' => $changeRequest->reference_number, 'status' => $changeRequest->status],
        ], 202);
    }

    /**
     * Get the resident's QR code data.
     */
    public function qrCode(Request $request): JsonResponse
    {
        $resident = $request->user();

        $resident->generateQrCode();

        return response()->json([
            'qr_code' => $resident->qr_code,
            'resident_id' => $resident->resident_id,
            'full_name' => $resident->full_name,
        ]);
    }

    /**
     * Get the resident's digital ID card data.
     */
    public function idCard(Request $request): JsonResponse
    {
        $resident = $request->user()->load(['household', 'sourceIncomeType']);
        $resident->generateQrCode();

        return response()->json([
            'data' => [
                'resident_id' => $resident->resident_id,
                'full_name' => $resident->full_name,
                'first_name' => $resident->first_name,
                'last_name' => $resident->last_name,
                'middle_name' => $resident->middle_name,
                'suffix' => $resident->suffix,
                'birth_date' => $resident->birthDateIso(),
                'gender' => $resident->gender,
                'civil_status' => $resident->civil_status,
                'blood_type' => $resident->blood_type,
                'contact_number' => $resident->contact_number,
                'email' => $resident->email,
                'occupation' => $resident->occupation,
                'source_income_type_id' => $resident->source_income_type_id,
                'source_income_type' => $resident->sourceIncomeType,
                'monthly_income' => $resident->monthly_income,
                'educational_attainment' => $resident->educational_attainment,
                'ethnicity' => $resident->ethnicity,
                'is_registered_voter' => $resident->is_registered_voter,
                'is_pwd' => $resident->is_pwd,
                'is_senior_citizen' => $resident->is_senior_citizen,
                'is_solo_parent' => $resident->is_solo_parent,
                'is_pregnant' => $resident->is_pregnant,
                'is_lactating' => $resident->is_lactating,
                'is_indigenous' => $resident->is_indigenous,
                'is_4ps' => $resident->is_4ps,
                'is_scholar' => $resident->is_scholar,
                'is_bhw' => $resident->is_bhw,
                'is_legacy_imported' => $resident->is_legacy_imported,
                'address' => $resident->household?->full_address ?? null,
                'building_registry_number' => $resident->household?->building_registry_number,
                'photo_url' => $resident->photo_path
                    ? Storage::disk('public')->url($resident->photo_path)
                    : null,
                'qr_code' => $resident->qr_code,
                'date_issue' => $resident->date_issue?->format('Y-m-d'),
                'emergency_contact' => $resident->emergency_contact,
            ],
        ]);
    }
}
