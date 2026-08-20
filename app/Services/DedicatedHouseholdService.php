<?php

namespace App\Services;

use App\Models\Household;
use App\Models\Resident;

class DedicatedHouseholdService
{
    public function resolve(?Resident $resident, array $attributes, ?string $householdKey = null): Household
    {
        $household = $resident?->household;

        if ($household && ! $household->residents()->withTrashed()->whereKeyNot($resident->id)->exists()) {
            $household->update($attributes);

            return $household;
        }

        if ($householdKey !== null) {
            $household = Household::withTrashed()->where('household_id', $householdKey)->first();
            if ($household && ! $household->residents()
                ->withTrashed()
                ->when($resident, fn ($query) => $query->whereKeyNot($resident->id))
                ->exists()) {
                if ($household->trashed()) {
                    $household->restore();
                }
                $household->update($attributes);

                return $household;
            }
        }

        return Household::create([
            'household_id' => $householdKey ?? Household::generateHouseholdId(),
            ...$attributes,
        ]);
    }
}
