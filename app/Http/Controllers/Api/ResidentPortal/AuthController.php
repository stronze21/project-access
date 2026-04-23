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
     * Residents authenticate using their resident_id (or email/contact_number) + password.
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required|string',
            'password' => 'required|string',
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

        if (!$resident || !$resident->password || !Hash::check($request->password, $resident->password)) {
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
        ]);
    }

    /**
     * Register/activate a resident's mobile account.
     *
     * Residents must already exist in the system (registered by an officer).
     * They activate their mobile account by providing their resident_id and setting a password.
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'resident_id' => 'required|string',
            'birth_date' => 'required|date',
            'last_name' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
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

        if ($resident->password) {
            return response()->json([
                'message' => 'This resident account has already been activated. Please login instead.',
                'errors' => ['resident_id' => ['Account already activated.']],
            ], 409);
        }

        $resident->update([
            'password' => Hash::make($request->password),
            'last_login_at' => now(),
        ]);

        $deviceName = $request->device_name ?? ($request->userAgent() ?? 'mobile-app');
        $token = $resident->createToken($deviceName, ['resident-portal'])->plainTextToken;

        return response()->json([
            'message' => 'Account activated successfully',
            'token' => $token,
            'resident' => $resident->load('household'),
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
     * Change the resident's password.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $resident = $request->user();

        if (!Hash::check($request->current_password, $resident->password)) {
            return response()->json([
                'message' => 'Current password is incorrect.',
                'errors' => ['current_password' => ['Current password is incorrect.']],
            ], 422);
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
