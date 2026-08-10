<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Resident;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResidentPinService
{
    public const VALIDATION_REGEX = 'regex:/^\d{2}-\d{5}$/';

    public static function normalize(?string $pin): ?string
    {
        $pin = strtoupper(trim((string) $pin));

        return $pin === '' ? null : $pin;
    }

    public static function generate(): string
    {
        return DB::transaction(function (): string {
            $sequenceRow = DB::table('resident_pin_sequences')->where('id', 1)->lockForUpdate()->first();
            $maximumExisting = Resident::query()
                ->withTrashed()
                ->pluck('resident_id')
                ->reduce(function (int $maximum, string $pin): int {
                    return preg_match('/^\d{2}-(\d{5})$/', $pin, $matches)
                        ? max($maximum, (int) $matches[1])
                        : $maximum;
                }, 0);
            $sequence = max((int) ($sequenceRow?->last_sequence ?? 0), $maximumExisting) + 1;
            if ($sequence > 99999) {
                throw ValidationException::withMessages(['residentPin' => 'The available five-digit Resident ID sequence has been exhausted.']);
            }

            DB::table('resident_pin_sequences')->updateOrInsert(
                ['id' => 1],
                ['last_sequence' => $sequence, 'updated_at' => now(), 'created_at' => $sequenceRow?->created_at ?? now()]
            );

            return now()->format('y').'-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
        });
    }

    public function create(array $attributes, ?string $requestedPin = null, string $errorKey = 'resident_id'): Resident
    {
        $requestedPin = self::normalize($requestedPin);
        if ($requestedPin !== null && ! preg_match('/^\d{2}-\d{5}$/', $requestedPin)) {
            throw ValidationException::withMessages([$errorKey => 'Resident ID must use the YY-XXXXX format.']);
        }

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return Resident::create($attributes + ['resident_id' => $requestedPin ?? self::generate()]);
            } catch (QueryException $exception) {
                if ($requestedPin !== null && $this->isDuplicateKey($exception)) {
                    throw ValidationException::withMessages([$errorKey => 'This Resident ID is already assigned to another resident.']);
                }

                if (! $this->isDuplicateKey($exception) || $attempt === 2) {
                    throw $exception;
                }
            }
        }

        throw ValidationException::withMessages([$errorKey => 'A unique Resident ID could not be generated. Please try again.']);
    }

    public function change(Resident $resident, string $newPin, string $errorKey = 'resident_id'): Resident
    {
        $newPin = self::normalize($newPin) ?? '';
        $oldPin = $resident->resident_id;

        if (! preg_match('/^\d{2}-\d{5}$/', $newPin)) {
            throw ValidationException::withMessages([$errorKey => 'Resident ID must use the YY-XXXXX format.']);
        }

        if ($newPin === $oldPin) {
            throw ValidationException::withMessages([$errorKey => 'Enter a different Resident ID.']);
        }

        return DB::transaction(function () use ($resident, $oldPin, $newPin) {
            $resident->forceFill(['resident_id' => $newPin])->save();
            $resident->generateQrCode();

            ActivityLog::log([
                'module' => 'residents',
                'action' => 'resident_pin_changed',
                'description' => "Resident ID changed from {$oldPin} to {$newPin}.",
                'loggable_id' => $resident->id,
                'loggable_type' => Resident::class,
                'old_values' => ['resident_id' => $oldPin],
                'new_values' => ['resident_id' => $newPin],
            ]);

            return $resident->refresh();
        });
    }

    private function isDuplicateKey(QueryException $exception): bool
    {
        return in_array((string) ($exception->errorInfo[0] ?? ''), ['23000', '23505'], true);
    }
}
