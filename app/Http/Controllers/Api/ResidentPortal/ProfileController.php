<?php

namespace App\Http\Controllers\Api\ResidentPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * Get the authenticated resident's profile.
     */
    public function show(Request $request): JsonResponse
    {
        $resident = $request->user()->load('household');

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
            'data' => $resident->fresh()->load('household'),
        ]);
    }

    /**
     * Upload/update the resident's profile photo.
     */
    public function uploadPhoto(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $resident = $request->user();

        // Delete old photo if exists
        if ($resident->photo_path && Storage::disk('public')->exists($resident->photo_path)) {
            Storage::disk('public')->delete($resident->photo_path);
        }

        $path = $request->file('photo')->store('resident-photos', 'public');

        $resident->update(['photo_path' => $path]);

        return response()->json([
            'message' => 'Photo uploaded successfully',
            'photo_url' => Storage::disk('public')->url($path),
        ]);
    }

    /**
     * Upload/update the resident's signature (base64 data URL).
     */
    public function uploadSignature(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'signature' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $resident = $request->user();
        $resident->signature = $request->signature;
        $resident->signature_status = 'completed';
        $resident->save();

        return response()->json([
            'message' => 'Signature updated successfully',
        ]);
    }

    /**
     * Get the resident's QR code data.
     */
    public function qrCode(Request $request): JsonResponse
    {
        $resident = $request->user();

        if (!$resident->qr_code) {
            $resident->generateQrCode();
        }

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
        $resident = $request->user()->load('household');

        return response()->json([
            'data' => [
                'resident_id' => $resident->resident_id,
                'full_name' => $resident->full_name,
                'first_name' => $resident->first_name,
                'last_name' => $resident->last_name,
                'middle_name' => $resident->middle_name,
                'suffix' => $resident->suffix,
                'birth_date' => $resident->birth_date?->format('Y-m-d'),
                'gender' => $resident->gender,
                'civil_status' => $resident->civil_status,
                'blood_type' => $resident->blood_type,
                'contact_number' => $resident->contact_number,
                'email' => $resident->email,
                'address' => $resident->household?->full_address ?? null,
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
