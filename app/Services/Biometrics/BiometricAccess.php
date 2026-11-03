<?php

namespace App\Services\Biometrics;

use App\Models\Resident;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class BiometricAccess
{
    public function authorize(User $user, string $purpose): void
    {
        abort_unless(in_array($purpose, ['registry', 'ayuda', 'enroll'], true), 422);
        abort_unless($user->can('view-residents'), 403);
        if ($purpose === 'ayuda') {
            abort_unless($user->can('view-distributions') && $user->can('create-distributions'), 403);
        }
        if ($purpose === 'enroll') {
            abort_unless($user->can('edit-residents') && $user->can('manage-resident-fingerprints'), 403);
        }
    }

    public function residents(User $user): Builder
    {
        // The current registry is permission scoped, not barangay scoped. Keep
        // candidate filtering and result resolution together if that changes.
        abort_unless($user->can('view-residents'), 403);

        return Resident::query()->where('is_active', true);
    }
}
