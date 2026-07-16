<?php

namespace App\Services;

use App\Models\Resident;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class ResidentCitizenAccountService
{
    public function resolve(Resident $resident): User
    {
        $user = User::query()->where('resident_id', $resident->id)->first();

        if (! $user) {
            $email = $resident->email ?: $this->generatedEmail($resident);
            $existing = User::query()->where('email', $email)->first();
            $user = $existing && ! $existing->isInternalUser()
                ? $existing
                : User::query()->create([
                    'resident_id' => $resident->id,
                    'name' => $resident->full_name,
                    'email' => $existing ? $this->generatedEmail($resident) : $email,
                    'email_verified_at' => now(),
                    'password' => Hash::make(Str::random(64)),
                ]);
        }

        $user->forceFill([
            'resident_id' => $resident->id,
            'name' => $resident->full_name,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        if (! $user->hasRole('citizen')) {
            Role::findOrCreate('citizen', 'web');
            $user->assignRole('citizen');
        }

        return $user->fresh();
    }

    private function generatedEmail(Resident $resident): string
    {
        $identifier = Str::lower(Str::slug($resident->resident_id ?: 'resident-'.$resident->id));

        return $identifier.'@resident.bosesmoto.local';
    }
}
