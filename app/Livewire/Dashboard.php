<?php

namespace App\Livewire;

use App\Models\AyudaProgram;
use App\Models\SosAlert;
use App\Models\Distribution;
use App\Models\Resident;
use App\Models\Household;
use App\Models\EmergencyAlert;
use App\Models\GrievanceReport;
use App\Models\DistributionBatch;
use App\Models\PublicServiceLink;
use App\Models\CitizenServiceRequest;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Traits\ComponentAuthorization;

class Dashboard extends Component
{
    use ComponentAuthorization;

    // Filters
    public $dateRange = 'month';
    public $programId = null;
    public $byBarangay = true;

    public $dateRanges = [
        ['id' => 'today', 'name' => 'Today'],
        ['id' => 'week', 'name' => 'This Week'],
        ['id' => 'month', 'name' => 'This Month'],
        ['id' => 'quarter', 'name' => 'This Quarter'],
        ['id' => 'year', 'name' => 'This Year'],
        ['id' => 'custom', 'name' => 'Custom Range'],
        ['id' => 'all', 'name' => 'All Time'],
    ];

    // Custom date range
    public $startDate;
    public $endDate;

    // Available programs for filter
    public $programs = [];

    // User roles and permissions
    public $userRole;
    public $userPermissions = [];
    public $canViewResidents = false;
    public $canViewPrograms = false;
    public $canViewDistributions = false;
    public $canViewReports = false;

    /**
     * Mount the component.
     */
    public function mount()
    {
        $user = auth()->user();

        // Redirect registration officers to their specialized dashboard
        if ($user->hasRole('registration-officer')) {
            return redirect(route('registration.dashboard'));
        }

        // Set default date range
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->endOfMonth()->format('Y-m-d');

        // Check user roles and permissions
        $user = Auth::user();
        $this->userRole = $user->roles->pluck('name')->first() ?? 'User';
        $this->userPermissions = $user->getAllPermissions()->pluck('name')->toArray();

        // Set permission flags
        $this->canViewResidents = $user->can('view-residents');
        $this->canViewPrograms = $user->can('view-programs');
        $this->canViewDistributions = $user->can('view-distributions');
        $this->canViewReports = $user->can('view-reports');

        // Adjust default filter based on role
        if ($user->hasRole('program-manager')) {
            $this->dateRange = 'year';
            $this->updatedDateRange();
        } elseif ($user->hasRole('distribution-officer')) {
            $this->dateRange = 'week';
            $this->updatedDateRange();
        }

        // Load programs (only if user has permission)
        if ($this->canViewPrograms) {
            $this->programs = AyudaProgram::orderBy('name')->get();

            // If user is a program manager and manages specific programs, filter the list
            if ($user->hasRole('program-manager')) {
                // Assume we have a relationship for program managers to see only their programs
                // This would need to be implemented in your user model
                if (method_exists($user, 'managedPrograms')) {
                    $managedProgramIds = $user->managedPrograms()->pluck('id')->toArray();
                    if (!empty($managedProgramIds)) {
                        $this->programs = $this->programs->whereIn('id', $managedProgramIds);
                    }
                }
            }
        }
    }

    /**
     * Update date range based on selection.
     */
    public function updatedDateRange()
    {
        switch ($this->dateRange) {
            case 'today':
                $this->startDate = now()->format('Y-m-d');
                $this->endDate = now()->format('Y-m-d');
                break;
            case 'week':
                $this->startDate = now()->startOfWeek()->format('Y-m-d');
                $this->endDate = now()->endOfWeek()->format('Y-m-d');
                break;
            case 'month':
                $this->startDate = now()->startOfMonth()->format('Y-m-d');
                $this->endDate = now()->endOfMonth()->format('Y-m-d');
                break;
            case 'quarter':
                $this->startDate = now()->startOfQuarter()->format('Y-m-d');
                $this->endDate = now()->endOfQuarter()->format('Y-m-d');
                break;
            case 'year':
                $this->startDate = now()->startOfYear()->format('Y-m-d');
                $this->endDate = now()->endOfYear()->format('Y-m-d');
                break;
            case 'all':
                $this->startDate = null;
                $this->endDate = null;
                break;
        }
    }

    /**
     * Get summary statistics.
     */
    #[Computed]
    public function summaryStats()
    {
        // Only proceed if user has necessary permissions
        if (!$this->canViewDistributions && !$this->canViewReports) {
            return $this->getEmptyStatsArray();
        }

        $query = Distribution::where('status', 'distributed');

        // Apply date range filter
        if ($this->startDate) {
            $query->whereDate('distribution_date', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('distribution_date', '<=', $this->endDate);
        }

        // Apply program filter
        if ($this->programId) {
            $query->where('ayuda_program_id', $this->programId);
        }

        // Role-based filtering
        $user = Auth::user();

        // Distribution officers might only see their own distributions
        if ($user->hasRole('distribution-officer')) {
            // Assuming distributions have a created_by field
            if (DB::getSchemaBuilder()->hasColumn('distributions', 'created_by')) {
                $query->where('created_by', $user->id);
            }
        }

        // Get total distributions and amount
        $totalDistributions = $query->count();
        $totalAmount = $query->sum('amount');

        // Get unique beneficiaries
        $uniqueResidents = $query->distinct('resident_id')->count('resident_id');
        $uniqueHouseholds = $query->whereNotNull('household_id')->distinct('household_id')->count('household_id');

        return [
            'total_distributions' => $totalDistributions,
            'total_amount' => $totalAmount,
            'unique_residents' => $uniqueResidents,
            'unique_households' => $uniqueHouseholds,
        ];
    }

    /**
     * Get distribution by program data.
     */
    #[Computed]
    public function distributionByProgram()
    {
        // Only proceed if user has necessary permissions
        if (!$this->canViewDistributions && !$this->canViewReports) {
            return collect();
        }

        $query = Distribution::select(
            'ayuda_programs.name as program_name',
            DB::raw('COUNT(*) as distribution_count'),
            DB::raw('SUM(distributions.amount) as total_amount')
        )
            ->join('ayuda_programs', 'distributions.ayuda_program_id', '=', 'ayuda_programs.id')
            ->where('distributions.status', 'distributed')
            ->groupBy('ayuda_programs.name');

        // Apply date range filter
        if ($this->startDate) {
            $query->whereDate('distribution_date', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('distribution_date', '<=', $this->endDate);
        }

        // Apply program filter
        if ($this->programId) {
            $query->where('distributions.ayuda_program_id', $this->programId);
        }

        // Role-based filtering
        $user = Auth::user();

        // Distribution officers might only see their distributions
        if ($user->hasRole('distribution-officer')) {
            // Assuming distributions have a created_by field
            if (DB::getSchemaBuilder()->hasColumn('distributions', 'created_by')) {
                $query->where('distributions.created_by', $user->id);
            }
        }

        return $query->get();
    }

    /**
     * Get distribution by location data.
     */
    #[Computed]
    public function distributionByLocation()
    {
        // Only proceed if user has necessary permissions
        if (!$this->canViewDistributions && !$this->canViewReports) {
            return collect();
        }

        $groupBy = $this->byBarangay ? 'households.barangay' : 'households.city_municipality';
        $locationField = $this->byBarangay ? 'barangay' : 'city_municipality';

        $query = Distribution::select(
            "households.{$locationField} as location_name",
            DB::raw('COUNT(*) as distribution_count'),
            DB::raw('SUM(distributions.amount) as total_amount'),
            DB::raw('COUNT(DISTINCT distributions.household_id) as household_count')
        )
            ->join('households', 'distributions.household_id', '=', 'households.id')
            ->where('distributions.status', 'distributed')
            ->whereNotNull('distributions.household_id')
            ->groupBy("households.{$locationField}");

        // Apply date range filter
        if ($this->startDate) {
            $query->whereDate('distribution_date', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('distribution_date', '<=', $this->endDate);
        }

        // Apply program filter
        if ($this->programId) {
            $query->where('distributions.ayuda_program_id', $this->programId);
        }

        // Role-based filtering
        $user = Auth::user();

        // Registration officers might only see their assigned barangays
        if ($user->hasRole('registration-officer')) {
            // Assuming there's an assignment table for officers
            if (method_exists($user, 'assignedBarangays')) {
                $assignedBarangays = $user->assignedBarangays()->pluck('barangay')->toArray();
                if (!empty($assignedBarangays)) {
                    $query->whereIn('households.barangay', $assignedBarangays);
                }
            }
        }

        return $query->get();
    }

    /**
     * Get daily distribution trend data.
     */
    #[Computed]
    public function distributionTrend()
    {
        // Only proceed if user has necessary permissions
        if (!$this->canViewDistributions && !$this->canViewReports) {
            return collect();
        }

        $query = Distribution::select(
            DB::raw('DATE(distribution_date) as distribution_day'),
            DB::raw('COUNT(*) as distribution_count'),
            DB::raw('SUM(amount) as daily_amount')
        )
            ->where('status', 'distributed')
            ->groupBy('distribution_day')
            ->orderBy('distribution_day');

        // Apply date range filter
        if ($this->startDate) {
            $query->whereDate('distribution_date', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('distribution_date', '<=', $this->endDate);
        }

        // Apply program filter
        if ($this->programId) {
            $query->where('ayuda_program_id', $this->programId);
        }

        // Role-based filtering
        $user = Auth::user();

        // Distribution officers might only see their distributions
        if ($user->hasRole('distribution-officer')) {
            // Assuming distributions have a created_by field
            if (DB::getSchemaBuilder()->hasColumn('distributions', 'created_by')) {
                $query->where('created_by', $user->id);
            }
        }

        return $query->get();
    }

    /**
     * Get top distribution batches data.
     */
    #[Computed]
    public function topBatches()
    {
        // Only proceed if user has necessary permissions
        if (!$this->canViewDistributions && !$this->canViewReports) {
            return collect();
        }

        $query = DistributionBatch::select(
            'distribution_batches.batch_number',
            'distribution_batches.location',
            'distribution_batches.batch_date',
            'ayuda_programs.name as program_name',
            'distribution_batches.actual_beneficiaries',
            'distribution_batches.total_amount'
        )
            ->join('ayuda_programs', 'distribution_batches.ayuda_program_id', '=', 'ayuda_programs.id')
            ->orderByDesc('distribution_batches.actual_beneficiaries');

        // Apply date range filter
        if ($this->startDate) {
            $query->whereDate('batch_date', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('batch_date', '<=', $this->endDate);
        }

        // Apply program filter
        if ($this->programId) {
            $query->where('distribution_batches.ayuda_program_id', $this->programId);
        }

        // Role-based filtering
        $user = Auth::user();

        // Distribution officers might only see their batches
        if ($user->hasRole('distribution-officer')) {
            // Assuming batches have a created_by field
            if (DB::getSchemaBuilder()->hasColumn('distribution_batches', 'created_by')) {
                $query->where('distribution_batches.created_by', $user->id);
            }
        }

        return $query->limit(5)->get();
    }

    /**
     * Get recent distribution data.
     */
    #[Computed]
    public function recentDistributions()
    {
        // Only proceed if user has necessary permissions
        if (!$this->canViewDistributions && !$this->canViewReports) {
            return collect();
        }

        $query = Distribution::select(
            'distributions.id',
            'distributions.reference_number',
            'distributions.distribution_date',
            'distributions.amount',
            'residents.first_name',
            'residents.last_name',
            'ayuda_programs.name as program_name'
        )
            ->join('residents', 'distributions.resident_id', '=', 'residents.id')
            ->join('ayuda_programs', 'distributions.ayuda_program_id', '=', 'ayuda_programs.id')
            ->where('distributions.status', 'distributed')
            ->orderByDesc('distributions.distribution_date');

        // Apply program filter
        if ($this->programId) {
            $query->where('distributions.ayuda_program_id', $this->programId);
        }

        // Role-based filtering
        $user = Auth::user();

        // Distribution officers might only see their distributions
        if ($user->hasRole('distribution-officer')) {
            // Assuming distributions have a created_by field
            if (DB::getSchemaBuilder()->hasColumn('distributions', 'created_by')) {
                $query->where('distributions.created_by', $user->id);
            }
        }

        return $query->limit(10)->get();
    }

    /**
     * Get program progress data.
     */
    #[Computed]
    public function programProgress()
    {
        // Only proceed if user has necessary permissions
        if (!$this->canViewPrograms && !$this->canViewReports) {
            return collect();
        }

        $query = AyudaProgram::select(
            'id',
            'name',
            'total_budget',
            'budget_used',
            'max_beneficiaries',
            'current_beneficiaries'
        )
            ->where('is_active', true);

        // Apply program filter
        if ($this->programId) {
            $query->where('id', $this->programId);
        }

        // Role-based filtering
        $user = Auth::user();

        // Program managers might only see their managed programs
        if ($user->hasRole('program-manager')) {
            // Assuming we have a relationship for program managers
            if (method_exists($user, 'managedPrograms')) {
                $managedProgramIds = $user->managedPrograms()->pluck('id')->toArray();
                if (!empty($managedProgramIds)) {
                    $query->whereIn('id', $managedProgramIds);
                }
            }
        }

        $programs = $query->get();

        // Calculate progress percentages
        foreach ($programs as $program) {
            if ($program->total_budget > 0) {
                $program->budget_progress = min(100, round(($program->budget_used / $program->total_budget) * 100, 1));
            } else {
                $program->budget_progress = 0;
            }

            if ($program->max_beneficiaries > 0) {
                $program->beneficiary_progress = min(100, round(($program->current_beneficiaries / $program->max_beneficiaries) * 100, 1));
            } else {
                $program->beneficiary_progress = 0;
            }
        }

        return $programs;
    }

    /**
     * Get system administrators stats
     */
    #[Computed]
    public function adminStats()
    {
        // Only accessible to system administrators
        $user = Auth::user();
        if (!$user->hasRole('system-administrator')) {
            return null;
        }

        return [
            'users_count' => \App\Models\User::count(),
            'roles_count' => \Spatie\Permission\Models\Role::count(),
            'permissions_count' => \Spatie\Permission\Models\Permission::count(),
            'active_programs' => AyudaProgram::where('is_active', true)->count(),
            'recent_users' => \App\Models\User::orderBy('created_at', 'desc')->take(5)->get(),
            'role_distribution' => DB::table('model_has_roles')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->select('roles.name', DB::raw('count(*) as count'))
                ->groupBy('roles.name')
                ->get()
        ];
    }

    /**
     * Get citizen services overview cards for the dashboard.
     */
    #[Computed]
    public function citizenServicesStats()
    {
        return [
            'service_requests' => CitizenServiceRequest::count(),
            'active_requests' => CitizenServiceRequest::whereNotIn('status', ['completed', 'released', 'cancelled', 'rejected'])->count(),
            'open_grievances' => GrievanceReport::whereNotIn('status', ['resolved', 'closed'])->count(),
            'active_alerts' => EmergencyAlert::active()->count(),
            'open_sos' => SosAlert::where('status', 'open')->count(),
            'portal_links' => PublicServiceLink::where('is_active', true)->count(),
        ];
    }

    /**
     * Recent SOS alerts for quick monitoring.
     */
    #[Computed]
    public function recentSosAlerts()
    {
        return SosAlert::with('resident:id,first_name,last_name,resident_id')
            ->latest()
            ->limit(5)
            ->get();
    }

    /**
     * Return empty stats array
     */
    private function getEmptyStatsArray()
    {
        return [
            'total_distributions' => 0,
            'total_amount' => 0,
            'unique_residents' => 0,
            'unique_households' => 0,
        ];
    }

    /**
     * Render the component.
     */
    public function render()
    {

        return view('livewire.dashboard', [
            'userRole' => $this->userRole,
            'userPermissions' => $this->userPermissions,
            'canViewResidents' => $this->canViewResidents,
            'canViewPrograms' => $this->canViewPrograms,
            'canViewDistributions' => $this->canViewDistributions,
            'canViewReports' => $this->canViewReports,
            'adminStats' => $this->adminStats
        ]);
    }
}
