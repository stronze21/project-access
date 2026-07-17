<?php

namespace App\Services\Bhwis;

use App\Exceptions\BhwisUnavailableException;
use App\Repositories\BhwisRepository;
use Throwable;

class BhwisGateway
{
    public function __construct(private readonly BhwisRepository $repository) {}

    /** @return array<string, mixed>|null */
    public function findResident(string $pin, string $lastName, string $birthDate): ?array
    {
        try {
            return $this->repository->findPersonalInfo($pin, $lastName, $birthDate);
        } catch (BhwisUnavailableException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new BhwisUnavailableException('BHWIS resident lookup failed.', previous: $exception);
        }
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    public function linkedRecords(string $pin, array $personal): array
    {
        try {
            $memberships = $this->repository->getFamilyMembersByPin($pin);
            $family = $memberships;
            foreach (collect($memberships)->pluck('FamilyNumber')->map(fn ($value) => trim((string) $value))->filter()->unique() as $number) {
                $family = array_merge($family, $this->repository->getFamilyMembersByFamilyNumber($number));
            }

            $bhw = $this->repository->getBhwAssignments($pin);
            $barangays = [];
            foreach (collect($bhw)->pluck('Barangay_Code')->map(fn ($value) => trim((string) $value))->filter()->unique() as $code) {
                $barangays = array_merge($barangays, $this->repository->getBarangay($code));
            }

            return [
                'personal' => [$personal],
                'family_members' => collect($family)->unique(fn ($row) => ($row['FamilyNumber'] ?? '').'|'.($row['PIN'] ?? ''))->values()->all(),
                'bhw_master' => $bhw,
                'barangay' => $barangays,
                'civil_status' => $this->repository->getCivilStatus($personal['CivilStatus'] ?? null),
                'source_income_type' => $this->repository->getSourceIncomeType($personal['SourceIncome_id'] ?? null),
                'educational_attainment' => $this->repository->getEducationalAttainment($personal['Educational_id'] ?? null),
            ];
        } catch (BhwisUnavailableException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new BhwisUnavailableException('BHWIS linked-record lookup failed.', previous: $exception);
        }
    }

    /** @return array<int, string> */
    public function checkSchema(): array
    {
        try {
            return $this->repository->missingRequiredSchema();
        } catch (BhwisUnavailableException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new BhwisUnavailableException('BHWIS schema check failed.', previous: $exception);
        }
    }

    public function testConnection(): array
    {
        return $this->repository->testConnection();
    }
}
