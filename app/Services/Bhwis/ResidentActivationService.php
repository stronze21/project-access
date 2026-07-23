<?php

namespace App\Services\Bhwis;

use App\Exceptions\ActivationRateLimitedException;
use App\Exceptions\BhwisUnavailableException;
use App\Exceptions\ResidentAlreadyActivatedException;
use App\Exceptions\ResidentIdentityMismatchException;
use App\Models\ActivationConsentAudit;
use App\Models\Resident;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Throwable;

class ResidentActivationService
{
    public function __construct(
        private readonly BhwisGateway $gateway,
        private readonly BhwisResidentImporter $importer,
    ) {}

    public function activate(array $data, Request $request, string $channel): Resident
    {
        $pin = trim((string) $data['resident_id']);
        $audit = $this->startAudit($pin, $data, $request, $channel);
        $this->guardRateLimit($pin, $request, $audit);
        $resident = null;

        try {
            $localResident = Resident::query()->where('resident_id', $pin)->first();
            $records = null;

            if ($localResident) {
                $this->assertIdentity($localResident, $data);
            } else {
                $personal = $this->gateway->findResident($pin, $data['last_name'], $data['birth_date']);
                if (! $personal) {
                    throw new ResidentIdentityMismatchException;
                }
                $records = $this->gateway->linkedRecords($pin, $personal);
            }

            $resident = DB::transaction(function () use ($pin, $data, $records, &$resident) {
                $resident = Resident::query()->where('resident_id', $pin)->lockForUpdate()->first();
                if ($resident) {
                    $this->assertIdentity($resident, $data);
                } else {
                    if (! $records) {
                        throw new ResidentIdentityMismatchException;
                    }
                    $resident = $this->importer->import($records);
                }

                if ($resident->mpin || $resident->password) {
                    throw new ResidentAlreadyActivatedException;
                }
                if (! $resident->is_active) {
                    throw new ResidentIdentityMismatchException;
                }

                $credential = $data['mpin'] ?? $data['password'] ?? null;
                $resident->forceFill([
                    isset($data['mpin']) ? 'mpin' : 'password' => $credential,
                    'email' => strtolower(trim($data['email'])),
                    'last_login_at' => now(),
                ])->save();

                return $resident;
            });
            $audit->update(['resident_id' => $resident->id, 'outcome' => 'activated']);

            return $resident->fresh(['household', 'sourceIncomeType']);
        } catch (ResidentIdentityMismatchException $e) {
            $audit->update(['outcome' => 'identity_mismatch']);
            throw $e;
        } catch (ResidentAlreadyActivatedException $e) {
            $audit->update(['resident_id' => $resident?->id, 'outcome' => 'already_activated']);
            throw $e;
        } catch (BhwisUnavailableException $e) {
            $audit->update(['outcome' => 'bhwis_unavailable']);
            Log::warning('BHWIS activation unavailable', [
                'attempt_id' => $audit->attempt_id,
                'exception' => $e::class,
                'reason' => $e->getMessage(),
                'previous_exception' => $e->getPrevious() ? $e->getPrevious()::class : null,
                'previous_code' => $e->getPrevious()?->getCode(),
            ]);
            throw $e;
        } catch (Throwable $e) {
            $audit->update(['outcome' => 'failed']);
            Log::error('Resident activation failed', [
                'attempt_id' => $audit->attempt_id,
                'exception' => $e::class,
            ]);
            throw $e;
        }
    }

    public function assertCanActivate(array $data): void
    {
        $pin = trim((string) $data['resident_id']);
        $resident = Resident::query()->where('resident_id', $pin)->first();

        if ($resident) {
            $this->assertIdentity($resident, $data);
            if ($resident->mpin || $resident->password) {
                throw new ResidentAlreadyActivatedException;
            }
            if (! $resident->is_active) {
                throw new ResidentIdentityMismatchException;
            }

            return;
        }

        if (! $this->gateway->findResident($pin, $data['last_name'], $data['birth_date'])) {
            throw new ResidentIdentityMismatchException;
        }
    }

    private function startAudit(string $pin, array $data, Request $request, string $channel): ActivationConsentAudit
    {
        $now = now();
        $versions = config('bhwis.consent_versions');

        return ActivationConsentAudit::create([
            'attempt_id' => (string) Str::uuid(),
            'resident_identifier' => $pin,
            'channel' => $channel,
            'terms_version' => $versions['terms'],
            'privacy_version' => $versions['privacy'],
            'bhwis_consent_version' => $versions['bhwis'],
            'terms_accepted_at' => $now,
            'privacy_acknowledged_at' => $now,
            'bhwis_consented_at' => $now,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            'device_name' => $data['device_name'] ?? null,
            'outcome' => 'started',
        ]);
    }

    private function guardRateLimit(string $pin, Request $request, ActivationConsentAudit $audit): void
    {
        $max = config('bhwis.activation_rate_limit', 5);
        $decay = config('bhwis.activation_decay_seconds', 60);
        $keys = ['activation:ip:'.hash('sha256', (string) $request->ip()), 'activation:pin:'.hash('sha256', Str::lower($pin))];

        foreach ($keys as $key) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                $audit->update(['outcome' => 'rate_limited']);
                throw new ActivationRateLimitedException(RateLimiter::availableIn($key));
            }
        }
        foreach ($keys as $key) {
            RateLimiter::hit($key, $decay);
        }
    }

    private function assertIdentity(Resident $resident, array $data): void
    {
        $birthDate = Carbon::parse($data['birth_date'])->toDateString();
        if (! hash_equals(Str::lower(trim($resident->last_name)), Str::lower(trim($data['last_name'])))
            || $resident->birthDateIso() !== $birthDate) {
            throw new ResidentIdentityMismatchException;
        }
    }
}
