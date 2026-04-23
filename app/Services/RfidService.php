<?php

namespace App\Services;

use App\Models\Resident;
use Illuminate\Support\Facades\Log;

class RfidService
{
    /**
     * Assign an RFID number to a resident.
     *
     * @param Resident $resident
     * @param string $rfidNumber
     * @return bool Whether the RFID assignment was successful
     */
    public function assignRfidToResident(Resident $resident, string $rfidNumber): bool
    {
        // Check if RFID is already assigned to another resident
        $existingResident = Resident::where('rfid_number', $rfidNumber)
            ->where('id', '!=', $resident->id)
            ->first();

        if ($existingResident) {
            Log::warning("RFID number {$rfidNumber} is already assigned to resident #{$existingResident->id}");
            return false;
        }

        $resident->rfid_number = $rfidNumber;
        $resident->save();

        Log::info("RFID number {$rfidNumber} assigned to resident #{$resident->id}");
        return true;
    }

    /**
     * Find a resident by RFID number.
     *
     * @param string $rfidNumber
     * @return Resident|null
     */
    public function findResidentByRfid(string $rfidNumber): ?Resident
    {
        return Resident::where('rfid_number', $rfidNumber)->first();
    }

    /**
     * Process a scanned RFID.
     *
     * @param string $rfidNumber
     * @return array Information about the scanned RFID
     */
    public function processRfid(string $rfidNumber): array
    {
        $result = [
            'type' => 'resident',
            'id' => null,
            'object' => null,
            'found' => false,
            'message' => 'No resident found with this RFID'
        ];

        $resident = $this->findResidentByRfid($rfidNumber);

        if ($resident) {
            $result['found'] = true;
            $result['object'] = $resident;
            $result['id'] = $resident->id;
            $result['message'] = 'Resident found: ' . $resident->full_name;
        }

        return $result;
    }

    /**
     * Validate an RFID number format.
     *
     * @param string $rfidNumber
     * @return bool Whether the RFID number has a valid format
     */
    public function isValidRfidFormat(string $rfidNumber): bool
    {
        // This implementation depends on the specific RFID format you're using
        // For example, if you're using 10-digit hexadecimal numbers
        return (bool) preg_match('/^[0-9A-F]{10}$/i', $rfidNumber);

        // For numeric RFID numbers of specific length
        // return (bool) preg_match('/^\d{10}$/', $rfidNumber);
    }

    /**
     * Revoke an RFID assignment from a resident.
     *
     * @param Resident $resident
     * @return bool
     */
    public function revokeRfid(Resident $resident): bool
    {
        if (!$resident->rfid_number) {
            return false;
        }

        $oldRfid = $resident->rfid_number;
        $resident->rfid_number = null;
        $resident->save();

        Log::info("RFID number {$oldRfid} revoked from resident #{$resident->id}");
        return true;
    }
}