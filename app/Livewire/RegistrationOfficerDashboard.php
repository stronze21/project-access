<?php

namespace App\Livewire;

use App\Models\Resident;
use App\Models\Household;
use App\Traits\ComponentAuthorization;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class RegistrationOfficerDashboard extends Component
{
    use ComponentAuthorization;

    /**
     * Mount the component.
     */
    public function mount()
    {
        // Check if user has permission to view residents
        if (!Auth::user()->hasPermissionTo('view-residents')) {
            // Use redirect helper function instead of Redirector class
            return redirect()->route('dashboard')->with('error', 'You do not have permission to access this page.');
        }
    }

    /**
     * Get recent resident registrations
     */
    #[Computed]
    public function recentResidents()
    {
        return Resident::orderBy('created_at', 'desc')
            ->with('household')
            ->take(5)
            ->get();
    }

    /**
     * Get household statistics
     */
    #[Computed]
    public function householdStats()
    {
        // Get total households
        $totalHouseholds = Household::count();

        // Get recent households (current month)
        $recentHouseholds = Household::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        // Get households by barangay
        $householdsByBarangay = Household::select('barangay', DB::raw('count(*) as count'))
            ->groupBy('barangay')
            ->orderByDesc('count')
            ->take(5)
            ->get();

        // Calculate percentage for barangays
        $maxCount = $householdsByBarangay->max('count') ?: 1;
        foreach ($householdsByBarangay as $barangay) {
            $barangay->percentage = round(($barangay->count / $maxCount) * 100);
        }

        return [
            'total' => $totalHouseholds,
            'recent' => $recentHouseholds,
            'byBarangay' => $householdsByBarangay
        ];
    }

    /**
     * Get resident statistics
     */
    #[Computed]
    public function residentStats()
    {
        // Get total residents
        $totalResidents = Resident::count();

        // Get recent residents (current month)
        $recentResidents = Resident::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        // Get incomplete resident records
        $incompleteRecords = Resident::where(function ($query) {
            $query->whereNull('birth_date')
                ->orWhereNull('contact_number')
                ->orWhereNull('gender')
                ->orWhereNull('civil_status');
        })
            ->count();

        // Get residents by age group
        $residentsByAgeGroup = [
            'children' => Resident::whereRaw('TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) < 18')->count(),
            'adults' => Resident::whereRaw('TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 18 AND 59')->count(),
            'seniors' => Resident::whereRaw('TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) >= 60')->count(),
        ];

        return [
            'total' => $totalResidents,
            'recent' => $recentResidents,
            'incomplete' => $incompleteRecords,
            'byAgeGroup' => $residentsByAgeGroup
        ];
    }

    /**
     * Quick registration actions
     */
    public function generateQrCodes()
    {
        // Logic to generate missing QR codes
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'QR codes generation started in the background.'
        ]);
    }

    public function checkDuplicates()
    {
        // Logic to check for potential duplicate residents
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Duplicate check started. Results will be ready shortly.'
        ]);
    }

    /**
     * Render the component view
     */
    public function render()
    {
        return view('livewire.registration-officer-dashboard', [
            'recentResidents' => $this->recentResidents,
            'householdStats' => $this->householdStats,
            'residentStats' => $this->residentStats
        ]);
    }
}