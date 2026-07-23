<?php

namespace App\Services;

use App\Models\Resident;
use App\Models\ResidentIdentityChangeRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResidentIdentityChangeRequestService
{
    public function submitPhoto(Resident $resident, UploadedFile $photo, string $reason): ResidentIdentityChangeRequest
    {
        return DB::transaction(function () use ($resident, $photo, $reason): ResidentIdentityChangeRequest {
            $lockedResident = Resident::lockForUpdate()->findOrFail($resident->id);
            $this->ensureNoPendingRequest($lockedResident, ResidentIdentityChangeRequest::TYPE_PHOTO);

            $path = $photo->store("identity-change-requests/{$resident->id}", 'local');

            return ResidentIdentityChangeRequest::create([
                'resident_id' => $resident->id,
                'type' => ResidentIdentityChangeRequest::TYPE_PHOTO,
                'requested_file_path' => $path,
                'request_reason' => $reason,
            ]);
        });
    }

    public function submitSignature(Resident $resident, string $signature, string $reason): ResidentIdentityChangeRequest
    {
        return DB::transaction(function () use ($resident, $signature, $reason): ResidentIdentityChangeRequest {
            $lockedResident = Resident::lockForUpdate()->findOrFail($resident->id);
            $this->ensureNoPendingRequest($lockedResident, ResidentIdentityChangeRequest::TYPE_SIGNATURE);

            return ResidentIdentityChangeRequest::create([
                'resident_id' => $resident->id,
                'type' => ResidentIdentityChangeRequest::TYPE_SIGNATURE,
                'requested_signature' => $signature,
                'request_reason' => $reason,
            ]);
        });
    }

    private function ensureNoPendingRequest(Resident $resident, string $type): void
    {
        if (ResidentIdentityChangeRequest::where('resident_id', $resident->id)->where('type', $type)->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages([
                $type => 'You already have a pending '.($type === 'photo' ? 'profile photo' : 'signature').' replacement request.',
            ]);
        }
    }
}
