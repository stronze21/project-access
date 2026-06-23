<?php

namespace App\Http\Controllers\Api\ResidentPortal;

use App\Http\Controllers\Controller;
use App\Models\Resident;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Login a resident and issue a Sanctum token.
     *
     * Residents authenticate using their resident_id (or email/contact_number) + MPIN.
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required|string',
            'mpin' => 'required_without:password|nullable|digits:6',
            'password' => 'required_without:mpin|nullable|string',
            'device_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $login = $request->login;

        // Find resident by resident_id, email, or contact_number
        $resident = Resident::where('resident_id', $login)
            ->orWhere('email', $login)
            ->orWhere('contact_number', $login)
            ->first();

        $credential = $request->filled('mpin') ? $request->mpin : $request->password;
        $credentialHash = $request->filled('mpin') ? $resident?->mpin : $resident?->password;
        $usesBirthdayFallback = $resident
            && $request->filled('mpin')
            && !$resident->mpin
            && $resident->birth_date
            && hash_equals($resident->birth_date->format('ymd'), $credential);

        if (!$resident || (!$usesBirthdayFallback && (!$credentialHash || !Hash::check($credential, $credentialHash)))) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.',
                'errors' => ['login' => ['The provided credentials are incorrect.']],
            ], 401);
        }

        if (!$resident->is_active) {
            return response()->json([
                'message' => 'Your account has been deactivated. Please contact your barangay office.',
                'errors' => ['login' => ['Account is deactivated.']],
            ], 403);
        }

        $deviceName = $request->device_name ?? ($request->userAgent() ?? 'mobile-app');

        $token = $resident->createToken($deviceName, ['resident-portal'])->plainTextToken;

        $resident->update(['last_login_at' => now()]);

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'resident' => $resident->load('household'),
            'requires_mpin_update' => $usesBirthdayFallback,
        ]);
    }

    /**
     * Register/activate a resident's mobile account.
     *
     * Residents must already exist in the system (registered by an officer).
     * They activate their mobile account by providing their resident_id and setting an MPIN.
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'resident_id' => 'required|string',
            'birth_date' => 'required|date',
            'last_name' => 'required|string',
            'mpin' => 'required_without:password|nullable|digits:6|confirmed',
            'password' => 'required_without:mpin|nullable|string|min:8|confirmed',
            'device_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Please check your information and try again.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Find the resident by their ID and verify identity
        $resident = Resident::where('resident_id', $request->resident_id)
            ->whereRaw('LOWER(last_name) = ?', [strtolower($request->last_name)])
            ->whereDate('birth_date', $request->birth_date)
            ->first();

        if (!$resident) {
            return response()->json([
                'message' => 'No matching resident record found. Please verify your information or contact your barangay office.',
                'errors' => ['resident_id' => ['No matching resident record found.']],
            ], 404);
        }

        if ($resident->mpin || $resident->password) {
            return response()->json([
                'message' => 'This resident account has already been activated. Please login instead.',
                'errors' => ['resident_id' => ['Account already activated.']],
            ], 409);
        }

        $resident->update([
            'mpin' => $request->filled('mpin') ? Hash::make($request->mpin) : null,
            'password' => $request->filled('password') ? Hash::make($request->password) : null,
            'last_login_at' => now(),
        ]);

        $deviceName = $request->device_name ?? ($request->userAgent() ?? 'mobile-app');
        $token = $resident->createToken($deviceName, ['resident-portal'])->plainTextToken;

        return response()->json([
            'message' => 'Account activated successfully',
            'token' => $token,
            'resident' => $resident->load('household'),
            'requires_mpin_update' => false,
        ], 201);
    }

    /**
     * Logout (revoke token).
     */
    public function logout(Request $request): JsonResponse
    {
        if ($request->has('all_devices') && $request->all_devices) {
            $request->user()->tokens()->delete();
        } else {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Change the resident's MPIN.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_mpin' => 'required_without:current_password|nullable|digits:6',
            'mpin' => 'required_without:password|nullable|digits:6|confirmed',
            'current_password' => 'required_without:current_mpin|nullable|string',
            'password' => 'required_without:mpin|nullable|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $resident = $request->user();

        $currentCredential = $request->filled('current_mpin') ? $request->current_mpin : $request->current_password;
        $currentHash = $request->filled('current_mpin') ? $resident->mpin : $resident->password;

        $usesBirthdayFallback = $request->filled('current_mpin')
            && !$resident->mpin
            && $resident->birth_date
            && hash_equals($resident->birth_date->format('ymd'), $currentCredential);

        if (!$usesBirthdayFallback && (!$currentHash || !Hash::check($currentCredential, $currentHash))) {
            return response()->json([
                'message' => 'Current credential is incorrect.',
                'errors' => ['current_mpin' => ['Current MPIN is incorrect.']],
            ], 422);
        }

        if ($request->filled('mpin')) {
            $resident->update(['mpin' => Hash::make($request->mpin)]);

            return response()->json(['message' => 'MPIN changed successfully']);
        }

        $resident->update(['password' => Hash::make($request->password)]);

        return response()->json(['message' => 'Password changed successfully']);
    }

    /**
     * Refresh the token.
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $resident = $request->user();

        $request->user()->currentAccessToken()->delete();

        $token = $resident->createToken(
            $request->device_name ?? 'refreshed_token',
            ['resident-portal']
        )->plainTextToken;

        return response()->json([
            'message' => 'Token refreshed successfully',
            'token' => $token,
        ]);
    }
}
