<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class MobileAuthController extends Controller
{
    public function residentSession(Request $request): JsonResponse
    {
        $resident = $request->user();
        if (!$resident instanceof Resident) {
            return response()->json([
                'message' => 'Resident portal session required.',
            ], 403);
        }

        if (!$resident->is_active) {
            return response()->json([
                'message' => 'Your resident portal account is inactive.',
            ], 403);
        }

        $user = $this->findOrCreateResidentUser($resident);
        $token = $user
            ->createToken($request->string('device_name')->toString() ?: 'mobile-app')
            ->plainTextToken;

        return response()->json([
            'message' => 'BosesMoTo session ready.',
            'token' => $token,
            'user' => $this->serializeUser($user->fresh()),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'email_verified_at' => now(),
            'password' => Hash::make($validated['password']),
        ]);

        Role::findOrCreate('citizen', 'web');
        $user->assignRole('citizen');

        event(new Registered($user));

        $token = $user
            ->createToken($validated['device_name'] ?? 'mobile-app')
            ->plainTextToken;

        return response()->json([
            'message' => 'Registered successfully.',
            'token' => $token,
            'user' => $this->serializeUser($user),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();
        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user
            ->createToken($validated['device_name'] ?? 'mobile-app')
            ->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'user' => $this->serializeUser($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeUser(User $user): array
    {
        return [
            'id' => $user->id,
            'resident_id' => $user->resident_id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'email_verified_at' => $user->email_verified_at?->toISOString(),
            'profile_photo_url' => $user->profilePhotoUrl(),
        ];
    }

    private function findOrCreateResidentUser(Resident $resident): User
    {
        $user = User::query()->where('resident_id', $resident->id)->first();
        if ($user) {
            return $this->syncResidentUser($user, $resident);
        }

        $email = $this->residentEmail($resident);
        $existing = User::query()->where('email', $email)->first();

        if ($existing && !$existing->isInternalUser()) {
            return $this->syncResidentUser($existing, $resident);
        }

        if ($existing) {
            $email = $this->generatedResidentEmail($resident);
        }

        $user = User::query()->create([
            'resident_id' => $resident->id,
            'name' => $resident->full_name,
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Hash::make(Str::random(64)),
        ]);

        Role::findOrCreate('citizen', 'web');
        $user->assignRole('citizen');

        return $user;
    }

    private function syncResidentUser(User $user, Resident $resident): User
    {
        $user->forceFill([
            'resident_id' => $resident->id,
            'name' => $resident->full_name,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ]);

        if (!$user->hasRole('citizen')) {
            Role::findOrCreate('citizen', 'web');
            $user->assignRole('citizen');
        }

        $user->save();

        return $user;
    }

    private function residentEmail(Resident $resident): string
    {
        return $resident->email ?: $this->generatedResidentEmail($resident);
    }

    private function generatedResidentEmail(Resident $resident): string
    {
        $identifier = Str::lower(Str::slug($resident->resident_id ?: 'resident-'.$resident->id));

        return $identifier.'@resident.bosesmoto.local';
    }
}
