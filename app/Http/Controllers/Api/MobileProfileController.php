<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class MobileProfileController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->serializeUser($request->user()),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'current_password' => ['nullable', 'string', 'required_with:password'],
            'password' => [
                'sometimes',
                'required',
                'string',
                'confirmed',
                Password::defaults(),
            ],
            'password_confirmation' => ['nullable', 'string', 'required_with:password'],
        ]);

        if (isset($validated['password'])) {
            if (!Hash::check((string) ($validated['current_password'] ?? ''), (string) $user->password)) {
                return response()->json([
                    'message' => 'Current password is incorrect.',
                ], 422);
            }
        }

        $user->fill([
            'name' => $validated['name'] ?? $user->name,
            'email' => $validated['email'] ?? $user->email,
        ]);

        if (isset($validated['password'])) {
            $user->password = Hash::make((string) $validated['password']);
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated.',
            'data' => $this->serializeUser($user->fresh()),
        ]);
    }

    public function updatePhoto(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'profile_photo' => [
                'required',
                'file',
                'image',
                'max:20480',
                'mimetypes:image/jpeg,image/png,image/webp',
            ],
        ]);

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $user->profile_photo_path = $validated['profile_photo']->store('profile-photos', 'public');
        $user->save();

        return response()->json([
            'message' => 'Profile photo updated.',
            'data' => $this->serializeUser($user->fresh()),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeUser($user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'email_verified_at' => $user->email_verified_at?->toISOString(),
            'profile_photo_url' => $user->profilePhotoUrl(),
        ];
    }
}
