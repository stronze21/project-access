<?php

namespace App\Http\Controllers\Api\ResidentPortal;

use App\Exceptions\ActivationRateLimitedException;
use App\Exceptions\BhwisUnavailableException;
use App\Exceptions\ResidentAlreadyActivatedException;
use App\Exceptions\ResidentIdentityMismatchException;
use App\Http\Controllers\Controller;
use App\Models\Resident;
use App\Services\Bhwis\ResidentActivationService;
use App\Services\ResidentEmailVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function sendEmailCode(Request $request, ResidentEmailVerificationService $verification): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'resident_id' => 'required|string|max:100',
            'birth_date' => 'required|date|before:today',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email:rfc|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Please check your information and try again.', 'errors' => $validator->errors()], 422);
        }

        try {
            $challenge = $verification->send($validator->validated(), $request);
        } catch (ResidentIdentityMismatchException) {
            return response()->json([
                'message' => 'No matching resident record found. Check the Resident ID/PIN, last name (surname), and birth date exactly as recorded in the local resident record.',
            ], 404);
        } catch (ResidentAlreadyActivatedException) {
            return response()->json(['message' => 'This resident account has already been activated.'], 409);
        } catch (BhwisUnavailableException) {
            return response()->json(['message' => 'Resident verification is temporarily unavailable.', 'retryable' => true], 503);
        }

        return response()->json([
            'message' => 'A confirmation code was sent to your email address.',
            'challenge_id' => $challenge->challenge_id,
            'expires_in' => 600,
        ]);
    }

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
            && ! $resident->mpin
            && $resident->birth_date
            && hash_equals($resident->birth_date->format('ymd'), $credential);

        if (! $resident || (! $usesBirthdayFallback && (! $credentialHash || ! Hash::check($credential, $credentialHash)))) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.',
                'errors' => ['login' => ['The provided credentials are incorrect.']],
            ], 401);
        }

        if (! $resident->is_active) {
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
            'resident' => $resident->load(['household', 'sourceIncomeType']),
            'requires_mpin_update' => $usesBirthdayFallback,
        ]);
    }

    /**
     * Register/activate a resident's mobile account.
     *
     * Project ACCESS validates an existing local resident first. BHWIS is only
     * queried to validate and import a resident who is missing locally.
     */
    public function register(Request $request, ResidentActivationService $activation, ResidentEmailVerificationService $verification): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'resident_id' => 'required|string',
            'birth_date' => 'required|date',
            'last_name' => 'required|string',
            'email' => 'required|email:rfc|max:255',
            'email_challenge_id' => 'required|uuid',
            'email_code' => 'required|digits:6',
            'mpin' => 'required_without:password|nullable|digits:6|confirmed',
            'password' => 'required_without:mpin|nullable|string|min:8|confirmed',
            'device_name' => 'nullable|string|max:255',
            'terms_accepted' => 'required|accepted',
            'privacy_notice_acknowledged' => 'required|accepted',
            'bhwis_import_consented' => 'required|accepted',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Please check your information and try again.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $challenge = $verification->verify($validator->validated());

        try {
            $resident = $activation->activate($validator->validated(), $request, 'api');
        } catch (ResidentIdentityMismatchException) {
            return response()->json([
                'message' => 'No matching resident record found. Check the Resident ID/PIN, last name (surname), and birth date exactly as recorded in the local resident record.',
                'errors' => ['resident_id' => ['No matching resident record found.']],
            ], 404);
        } catch (ResidentAlreadyActivatedException) {
            return response()->json([
                'message' => 'This resident account has already been activated. Please login instead.',
                'errors' => ['resident_id' => ['Account already activated.']],
            ], 409);
        } catch (BhwisUnavailableException) {
            return response()->json([
                'message' => 'Resident verification is temporarily unavailable. Please try again later.',
                'errors' => ['resident_id' => ['Resident verification is temporarily unavailable.']],
                'retryable' => true,
            ], 503);
        } catch (ActivationRateLimitedException $e) {
            return response()->json([
                'message' => 'Too many activation attempts. Please try again later.',
                'retry_after' => $e->retryAfter,
            ], 429)->header('Retry-After', (string) $e->retryAfter);
        }

        $verification->consume($challenge);

        $deviceName = $request->device_name ?? ($request->userAgent() ?? 'mobile-app');
        $token = $resident->createToken($deviceName, ['resident-portal'])->plainTextToken;

        return response()->json([
            'message' => 'Account activated successfully',
            'token' => $token,
            'resident' => $resident->load(['household', 'sourceIncomeType']),
            'requires_mpin_update' => false,
        ], 201);
    }

    public function resetMpin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'resident_id' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date', 'before:today'],
            'mpin' => ['required', 'digits:6', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Please check the information you entered.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $resident = Resident::query()
            ->where('resident_id', trim($validated['resident_id']))
            ->whereDate('birth_date', $validated['birth_date'])
            ->whereRaw('LOWER(last_name) = ?', [mb_strtolower(trim($validated['last_name']))])
            ->where('is_active', true)
            ->first();

        if (! $resident) {
            return response()->json([
                'message' => 'We could not verify those resident details. Please check them or contact your barangay office.',
            ], 422);
        }

        $resident->forceFill(['mpin' => Hash::make($validated['mpin'])])->save();
        $resident->tokens()->delete();

        return response()->json(['message' => 'Your MPIN has been reset. You can now sign in.']);
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

    public function sessions(Request $request): JsonResponse
    {
        $currentId = $request->user()->currentAccessToken()?->id;

        return response()->json(['data' => $request->user()->tokens()
            ->latest('last_used_at')
            ->get()
            ->map(fn ($token) => [
                'id' => $token->id,
                'device_name' => $token->name,
                'last_used_at' => optional($token->last_used_at)->toIso8601String(),
                'created_at' => optional($token->created_at)->toIso8601String(),
                'is_current' => $token->id === $currentId,
            ])->values()]);
    }

    public function revokeSession(Request $request, int $tokenId): JsonResponse
    {
        $token = $request->user()->tokens()->findOrFail($tokenId);
        $wasCurrent = $token->id === $request->user()->currentAccessToken()?->id;
        $token->delete();

        return response()->json(['message' => 'Device session revoked.', 'revoked_current' => $wasCurrent]);
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
            && ! $resident->mpin
            && $resident->birth_date
            && hash_equals($resident->birth_date->format('ymd'), $currentCredential);

        if (! $usesBirthdayFallback && (! $currentHash || ! Hash::check($currentCredential, $currentHash))) {
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
