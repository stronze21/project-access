<?php

namespace App\Services\Biometrics;

use App\Models\BiometricSession;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BiometricSessions
{
    public function consume(User $user, string $id, string $purpose): Resident
    {
        abort_unless(config('biometrics.enabled'), 503);
        $access = app(BiometricAccess::class);
        $access->authorize($user, $purpose);

        return DB::transaction(function () use ($user, $id, $purpose, $access) {
            $session = BiometricSession::whereKey($id)->where('user_id', $user->id)
                ->where('purpose', $purpose)->lockForUpdate()->firstOrFail();
            abort_unless($session->status === 'completed' && $session->outcome === 'matched'
                && $session->expires_at->isFuture(), 409, 'Fingerprint lookup expired or already used.');
            abort_unless(\App\Models\BiometricWorkstation::whereKey($session->workstation_id)->where('active', true)->exists(), 403);
            $resident = $access->residents($user)->findOrFail($session->matched_resident_id);
            $revision = \App\Models\ResidentFingerprint::where('resident_id', $resident->id)->value('revision');
            abort_unless($revision && ($session->candidates[(string) $resident->id] ?? null) === $revision, 409, 'Fingerprint enrollment changed. Please scan again.');
            $session->update(['status' => 'consumed', 'candidates' => null]);

            return $resident;
        });
    }
}
