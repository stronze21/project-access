<?php

namespace App\Http\Controllers;

use App\Models\BiometricSession;
use App\Models\BiometricWorkstation;
use App\Models\ResidentFingerprint;
use App\Models\User;
use App\Services\Biometrics\BiometricAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BiometricDeviceController extends Controller
{
    private function workstation(Request $request): BiometricWorkstation
    {
        abort_unless(config('biometrics.enabled'), 503);
        $token = $request->bearerToken();
        abort_unless(is_string($token) && strlen($token) >= 40, 401);

        return BiometricWorkstation::where('token_hash', hash('sha256', $token))
            ->where('active', true)->firstOrFail();
    }

    private function session(Request $request, string $id): BiometricSession
    {
        $workstation = $this->workstation($request);
        $session = BiometricSession::whereKey($id)->where('workstation_id', $workstation->id)
            ->lockForUpdate()->firstOrFail();
        abort_unless($session->expires_at->isFuture(), 410, 'Fingerprint session expired.');
        app(BiometricAccess::class)->authorize(User::findOrFail($session->user_id), $session->purpose);

        return $session;
    }

    public function claim(Request $request, string $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $session = $this->session($request, $id);
            abort_unless($session->status === 'pending', 409, 'Session already claimed or cancelled.');
            $access = app(BiometricAccess::class);
            $user = User::findOrFail($session->user_id);
            $candidates = [];
            $revisions = [];
            if ($session->purpose === 'enroll') {
                $access->residents($user)->findOrFail($session->resident_id);
                $revisions[(string) $session->resident_id] = ResidentFingerprint::where('resident_id', $session->resident_id)->value('revision');
            } else {
                $query = ResidentFingerprint::whereIn('resident_id', $access->residents($user)->select('id'));
                abort_if((clone $query)->count() > config('biometrics.max_candidates'), 422, 'Enrollment set exceeds the validated workstation capacity.');
                foreach ($query->get() as $fingerprint) {
                    // Never silently omit an incompatible enrollment from lookup.
                    abort_unless($fingerprint->format === config('biometrics.template_format'), 409, 'Enrollment format requires migration.');
                    $candidates[] = ['resident_id' => $fingerprint->resident_id, 'template' => $fingerprint->template];
                    $revisions[(string) $fingerprint->resident_id] = $fingerprint->revision;
                }
            }
            $session->update(['status' => 'capturing', 'candidates' => $revisions]);

            return response()->json([
                'id' => $session->id, 'purpose' => $session->purpose,
                'expires_at' => $session->expires_at->toIso8601String(),
                'format' => config('biometrics.template_format'),
                'candidates' => $candidates,
            ])->header('Cache-Control', 'no-store');
        });
    }

    public function complete(Request $request, string $id)
    {
        // Device-only endpoint: templates never pass through browser forms or
        // their validation redirect/session flash mechanism.
        $data = $request->validate([
            'outcome' => ['required', Rule::in(['matched', 'enrolled', 'no_match', 'no_enrollments', 'ambiguous', 'poor_capture', 'cancelled', 'reader_unavailable', 'sdk_unavailable'])],
            'resident_id' => ['required_if:outcome,matched', 'nullable', 'integer'],
            'template' => ['required_if:outcome,enrolled', 'nullable', 'string', 'max:32768'],
            'format' => ['required_if:outcome,enrolled', 'nullable', Rule::in([config('biometrics.template_format')])],
            'sdk_version' => ['required_if:outcome,enrolled', 'nullable', 'string', 'max:100'],
        ]);

        return DB::transaction(function () use ($request, $id, $data) {
            $session = $this->session($request, $id);
            abort_unless($session->status === 'capturing', 409, 'Session already completed or cancelled.');
            $user = User::findOrFail($session->user_id);
            $access = app(BiometricAccess::class);
            if ($data['outcome'] === 'no_enrollments') {
                abort_if($session->purpose === 'enroll' || ! empty($session->candidates), 422, 'This session is not an empty fingerprint lookup.');
            }
            if ($data['outcome'] === 'enrolled') {
                abort_unless($session->purpose === 'enroll', 422);
                $access->residents($user)->lockForUpdate()->findOrFail($session->resident_id);
                $current = ResidentFingerprint::where('resident_id', $session->resident_id)->first();
                abort_unless(($session->candidates[(string) $session->resident_id] ?? null) === $current?->revision, 409, 'Enrollment changed. Start again.');
                ResidentFingerprint::updateOrCreate(['resident_id' => $session->resident_id], [
                    'template' => $data['template'], 'format' => $data['format'],
                    'sdk_version' => $data['sdk_version'], 'finger_position' => $session->finger_position,
                    'enrolled_by' => $session->user_id, 'consented_at' => $session->consented_at,
                    'revision' => (string) Str::uuid(),
                ]);
                Log::info('Resident fingerprint enrolled', ['resident_id' => $session->resident_id, 'operator_id' => $user->id]);
            } elseif ($data['outcome'] === 'matched') {
                abort_if($session->purpose === 'enroll', 422);
                $resident = $access->residents($user)->findOrFail($data['resident_id']);
                $revision = ResidentFingerprint::where('resident_id', $resident->id)->value('revision');
                abort_unless($revision && ($session->candidates[(string) $resident->id] ?? null) === $revision, 409, 'Enrollment changed. Scan again.');
                $session->matched_resident_id = $resident->id;
            }
            $session->outcome = $data['outcome'];
            $session->status = 'completed';
            if ($data['outcome'] !== 'matched') {
                $session->candidates = null;
            }
            $session->save();

            return response()->noContent();
        });
    }
}
