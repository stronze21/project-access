<?php

namespace App\Livewire;

use App\Models\Barangay;
use App\Models\BarangayHealthWorkerAssignment;
use App\Models\BarangayZone;
use App\Models\Resident;
use App\Services\Legacy\LegacyCsvImporter;
use Database\Seeders\LocationsSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class LegacyBhwManager extends Component
{
    use WithPagination;

    public array $form = [];

    public ?int $editingId = null;

    public ?int $deleteId = null;

    public ?string $notice = null;

    public function mount(): void
    {
        $this->authorizeManager();
        $this->resetForm();
    }

    public function edit(int $zoneId): void
    {
        $this->authorizeManager();
        $zone = BarangayZone::with('healthWorkerAssignments')->findOrFail($zoneId);
        $assignments = $zone->healthWorkerAssignments->keyBy('assignment_slot');
        $this->editingId = $zone->id;
        $this->form = [
            'legacy_barangay_code' => $zone->legacy_barangay_code,
            'legacy_zone_id' => $zone->legacy_zone_id,
            'name' => $zone->name,
            'brgy_code' => $zone->brgy_code ?? '',
            'primary_pin' => $assignments->get('primary')?->legacy_pin ?? '',
            'secondary_pin' => $assignments->get('secondary')?->legacy_pin ?? '',
        ];
        $this->notice = null;
        $this->resetValidation();
    }

    public function save(): void
    {
        $this->authorizeManager();
        $data = $this->validate($this->rules())['form'];
        $zone = $this->editingId ? BarangayZone::findOrFail($this->editingId) : null;

        $duplicate = BarangayZone::query()
            ->where('source_system', LegacyCsvImporter::SOURCE_SYSTEM)
            ->where('legacy_barangay_code', $data['legacy_barangay_code'])
            ->where('legacy_zone_id', $data['legacy_zone_id'])
            ->where('name', $data['name'])
            ->when($zone, fn ($query) => $query->where('id', '!=', $zone->id))
            ->exists();
        if ($duplicate) {
            throw ValidationException::withMessages([
                'form.legacy_zone_id' => 'This barangay, zone ID, and zone name already exist.',
            ]);
        }

        DB::transaction(function () use ($data, &$zone) {
            $values = [
                'legacy_barangay_code' => $data['legacy_barangay_code'],
                'legacy_zone_id' => $data['legacy_zone_id'],
                'name' => $data['name'],
                'brgy_code' => ($data['brgy_code'] ?? null) ?: null,
                'is_active' => true,
            ];
            if ($zone) {
                $zone->update($values);
            } else {
                $zone = BarangayZone::create([
                    ...$values,
                    'source_system' => LegacyCsvImporter::SOURCE_SYSTEM,
                ]);
            }
            $this->syncAssignments($zone, $data);
        });

        $this->notice = $this->editingId ? 'BHW zone and assignments updated.' : 'BHW zone created.';
        $this->resetForm();
        $this->resetPage();
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
        $this->notice = null;
    }

    public function requestDelete(int $zoneId): void
    {
        $this->authorizeManager();
        BarangayZone::findOrFail($zoneId);
        $this->deleteId = $zoneId;
    }

    public function cancelDelete(): void
    {
        $this->deleteId = null;
    }

    public function deleteZone(): void
    {
        $this->authorizeManager();
        $zone = BarangayZone::findOrFail($this->deleteId);
        DB::transaction(function () use ($zone) {
            $residentIds = $zone->healthWorkerAssignments()->whereNotNull('resident_id')->pluck('resident_id')->all();
            $zone->delete();
            $this->syncResidentFlags($residentIds);
        });
        if ($this->editingId === $this->deleteId) {
            $this->resetForm();
        }
        $this->deleteId = null;
        $this->notice = 'BHW zone removed.';
        $this->resetPage();
    }

    public function render()
    {
        $barangays = Schema::hasTable('refbrgy')
            ? Barangay::query()
                ->where('citymunCode', LocationsSeeder::CITY_CODE)
                ->orderBy('brgyDesc')
                ->get(['brgyCode', 'brgyDesc'])
            : collect();

        return view('livewire.legacy-bhw-manager', [
            'zones' => BarangayZone::query()
                ->with(['healthWorkerAssignments' => fn ($query) => $query->with('resident')->orderBy('assignment_slot')])
                ->orderBy('legacy_barangay_code')
                ->orderBy('legacy_zone_id')
                ->paginate(20),
            'barangays' => $barangays,
        ])->layout('layouts.app');
    }

    private function rules(): array
    {
        return [
            'form.legacy_barangay_code' => ['required', 'string', 'max:50'],
            'form.legacy_zone_id' => ['required', 'string', 'max:50'],
            'form.name' => ['required', 'string', 'max:255'],
            'form.brgy_code' => array_values(array_filter([
                'nullable',
                'string',
                Schema::hasTable('refbrgy') ? 'exists:refbrgy,brgyCode' : null,
            ])),
            'form.primary_pin' => ['nullable', 'string', 'exists:residents,resident_id', 'different:form.secondary_pin'],
            'form.secondary_pin' => ['nullable', 'string', 'exists:residents,resident_id', 'different:form.primary_pin'],
        ];
    }

    private function syncAssignments(BarangayZone $zone, array $data): void
    {
        $residentIds = $zone->healthWorkerAssignments()->whereNotNull('resident_id')->pluck('resident_id')->all();
        $zone->healthWorkerAssignments()->delete();

        foreach (['primary_pin' => 'primary', 'secondary_pin' => 'secondary'] as $field => $slot) {
            $pin = trim((string) ($data[$field] ?? ''));
            if ($pin === '') {
                continue;
            }
            $resident = Resident::where('resident_id', $pin)->firstOrFail();
            BarangayHealthWorkerAssignment::create([
                'barangay_zone_id' => $zone->id,
                'resident_id' => $resident->id,
                'legacy_pin' => $pin,
                'assignment_slot' => $slot,
            ]);
            $residentIds[] = $resident->id;
        }

        $this->syncResidentFlags(array_values(array_unique($residentIds)));
    }

    private function syncResidentFlags(array $residentIds): void
    {
        if ($residentIds === []) {
            return;
        }
        DB::table('residents')->whereIn('id', $residentIds)->update(['is_bhw' => false]);
        $assigned = BarangayHealthWorkerAssignment::whereIn('resident_id', $residentIds)
            ->whereNotNull('resident_id')
            ->pluck('resident_id')
            ->unique();
        if ($assigned->isNotEmpty()) {
            DB::table('residents')->whereIn('id', $assigned)->update(['is_bhw' => true]);
        }
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->form = [
            'legacy_barangay_code' => '',
            'legacy_zone_id' => '',
            'name' => '',
            'brgy_code' => '',
            'primary_pin' => '',
            'secondary_pin' => '',
        ];
        $this->resetValidation();
    }

    private function authorizeManager(): void
    {
        abort_unless(auth()->user()?->can('manage-legacy-reference-data'), 403);
    }
}
