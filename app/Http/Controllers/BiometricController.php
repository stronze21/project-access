<?php

namespace App\Http\Controllers;

use App\Models\BiometricSession;
use App\Models\BiometricWorkstation;
use App\Models\ResidentFingerprint;
use App\Services\Biometrics\BiometricAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BiometricController extends Controller
{
    public function store(Request $request, BiometricAccess $access)
    {
        abort_unless(config('biometrics.enabled'), 503, 'Fingerprint lookup is not enabled yet. Use QR, RFID or search.');
        $data = $request->validate([
            'purpose' => ['required', Rule::in(['registry', 'ayuda', 'enroll'])],
            'workstation_id' => ['required', 'uuid'],
            'resident_id' => ['required_if:purpose,enroll', 'nullable', 'integer'],
            'finger_position' => ['required_if:purpose,enroll', 'nullable', Rule::in(['right_thumb', 'right_index', 'right_middle', 'right_ring', 'right_little', 'left_thumb', 'left_index', 'left_middle', 'left_ring', 'left_little'])],
            'consent' => ['required_if:purpose,enroll', 'accepted_if:purpose,enroll'],
        ]);
        $access->authorize($request->user(), $data['purpose']);
        BiometricWorkstation::whereKey($data['workstation_id'])->where('active', true)->firstOrFail();
        $resident = $data['purpose'] === 'enroll' ? $access->residents($request->user())->findOrFail($data['resident_id']) : null;
        $session = BiometricSession::create([
            'id' => (string) Str::uuid(), 'user_id' => $request->user()->id,
            'workstation_id' => $data['workstation_id'], 'purpose' => $data['purpose'],
            'resident_id' => $resident?->id,
            'finger_position' => $resident ? $data['finger_position'] : null,
            'consented_at' => $resident ? now() : null,
            'expires_at' => now()->addSeconds(config('biometrics.session_seconds')),
        ]);

        return response()->json(['id' => $session->id])->header('Cache-Control', 'no-store');
    }

    public function show(Request $request, string $id)
    {
        $session = BiometricSession::whereKey($id)->where('user_id', $request->user()->id)->firstOrFail();
        app(BiometricAccess::class)->authorize($request->user(), $session->purpose);

        return response()->json([
            'status' => $session->expires_at->isPast() ? 'expired' : $session->status,
            'outcome' => $session->outcome,
        ])->header('Cache-Control', 'no-store');
    }

    public function cancel(Request $request, string $id)
    {
        BiometricSession::whereKey($id)->where('user_id', $request->user()->id)
            ->whereIn('status', ['pending', 'capturing', 'completed'])
            ->update(['status' => 'cancelled', 'candidates' => null]);

        return response()->noContent();
    }

    public function destroy(Request $request, int $resident, BiometricAccess $access)
    {
        $access->authorize($request->user(), 'enroll');
        // Inactive residents can also have their stored enrollment removed.
        \App\Models\Resident::findOrFail($resident);
        DB::transaction(function () use ($resident) {
            BiometricSession::where('resident_id', $resident)->whereIn('status', ['pending', 'capturing'])
                ->update(['status' => 'cancelled', 'candidates' => null]);
            ResidentFingerprint::where('resident_id', $resident)->delete();
            \Illuminate\Support\Facades\Log::info('Resident fingerprint removed', ['resident_id' => $resident, 'operator_id' => auth()->id()]);
        });

        return response()->noContent();
    }
}
