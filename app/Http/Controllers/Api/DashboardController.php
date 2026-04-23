<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resident;
use App\Models\Household;
use App\Models\Distribution;
use App\Models\AyudaProgram;
use App\Models\DistributionBatch;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics.
     */
    public function stats(): JsonResponse
    {
        // Count total residents, households, and active residents
        $totalResidents = Resident::count();
        $activeResidents = Resident::where('is_active', true)->count();
        $totalHouseholds = Household::count();

        // Count recent registrations (last 30 days)
        $recentRegistrations = Resident::where('created_at', '>=', now()->subDays(30))->count();

        // Get distributions statistics
        $totalDistributions = Distribution::count();
        $pendingDistributions = Distribution::where('status', 'pending')->count();
        $distributedCount = Distribution::where('status', 'distributed')->count();
        $totalDistributedAmount = Distribution::where('status', 'distributed')->sum('amount');

        // Get today's distributions
        $todayDistributions = Distribution::whereDate('distribution_date', now()->toDateString())->count();

        // Get active programs
        $activePrograms = AyudaProgram::active()->count();

        // Get today's batches
        $todayBatches = DistributionBatch::whereDate('batch_date', now()->toDateString())->count();

        return response()->json([
            'total_residents' => $totalResidents,
            'active_residents' => $activeResidents,
            'total_households' => $totalHouseholds,
            'recent_registrations' => $recentRegistrations,
            'total_distributions' => $totalDistributions,
            'pending_distributions' => $pendingDistributions,
            'distributed_count' => $distributedCount,
            'total_distributed_amount' => $totalDistributedAmount,
            'today_distributions' => $todayDistributions,
            'active_programs' => $activePrograms,
            'today_batches' => $todayBatches,
        ]);
    }

    /**
     * Get distribution data by month for the current year.
     */
    public function monthlyDistributions(): JsonResponse
    {
        $currentYear = now()->year;

        $monthlyData = Distribution::select(
                DB::raw('MONTH(distribution_date) as month'),
                DB::raw('COUNT(*) as total_count'),
                DB::raw('SUM(CASE WHEN status = "distributed" THEN 1 ELSE 0 END) as distributed_count'),
                DB::raw('SUM(CASE WHEN status = "distributed" THEN amount ELSE 0 END) as total_amount')
            )
            ->whereYear('distribution_date', $currentYear)
            ->groupBy(DB::raw('MONTH(distribution_date)'))
            ->orderBy(DB::raw('MONTH(distribution_date)'))
            ->get();

        // Format data for all months (1-12)
        $formattedData = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthName = Carbon::create($currentYear, $i, 1)->format('F');
            $monthData = $monthlyData->firstWhere('month', $i);

            $formattedData[] = [
                'month' => $i,
                'month_name' => $monthName,
                'total_count' => $monthData ? $monthData->total_count : 0,
                'distributed_count' => $monthData ? $monthData->distributed_count : 0,
                'total_amount' => $monthData ? $monthData->total_amount : 0,
            ];
        }

        return response()->json($formattedData);
    }

    /**
     * Get distribution data by program.
     */
    public function programDistributions(): JsonResponse
    {
        $programData = Distribution::select(
                'ayuda_program_id',
                DB::raw('COUNT(*) as total_count'),
                DB::raw('SUM(CASE WHEN status = "distributed" THEN 1 ELSE 0 END) as distributed_count'),
                DB::raw('SUM(CASE WHEN status = "distributed" THEN amount ELSE 0 END) as total_amount')
            )
            ->with('ayudaProgram:id,name,code')
            ->groupBy('ayuda_program_id')
            ->get();

        return response()->json($programData);
    }

    /**
     * Get distribution data by barangay.
     */
    public function barangayDistributions(): JsonResponse
    {
        $barangayData = Distribution::select(
                'households.barangay',
                DB::raw('COUNT(distributions.id) as total_count'),
                DB::raw('SUM(CASE WHEN distributions.status = "distributed" THEN 1 ELSE 0 END) as distributed_count'),
                DB::raw('SUM(CASE WHEN distributions.status = "distributed" THEN distributions.amount ELSE 0 END) as total_amount')
            )
            ->join('households', 'distributions.household_id', '=', 'households.id')
            ->groupBy('households.barangay')
            ->orderBy('total_count', 'desc')
            ->get();

        return response()->json($barangayData);
    }

    /**
     * Get resident statistics by category.
     */
    public function residentStatistics(): JsonResponse
    {
        $statistics = [
            'by_gender' => Resident::select('gender', DB::raw('COUNT(*) as count'))
                ->groupBy('gender')
                ->get(),

            'by_civil_status' => Resident::select('civil_status', DB::raw('COUNT(*) as count'))
                ->groupBy('civil_status')
                ->get(),

            'by_age_group' => [
                ['group' => 'Children (0-12)', 'count' => Resident::whereRaw('TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) <= 12')->count()],
                ['group' => 'Teenagers (13-17)', 'count' => Resident::whereRaw('TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 13 AND 17')->count()],
                ['group' => 'Young Adults (18-30)', 'count' => Resident::whereRaw('TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 18 AND 30')->count()],
                ['group' => 'Adults (31-59)', 'count' => Resident::whereRaw('TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 31 AND 59')->count()],
                ['group' => 'Seniors (60+)', 'count' => Resident::whereRaw('TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) >= 60')->count()],
            ],

            'by_special_status' => [
                ['status' => 'PWD', 'count' => Resident::where('is_pwd', true)->count()],
                ['status' => 'Senior Citizen', 'count' => Resident::where('is_senior_citizen', true)->count()],
                ['status' => 'Solo Parent', 'count' => Resident::where('is_solo_parent', true)->count()],
                ['status' => 'Pregnant', 'count' => Resident::where('is_pregnant', true)->count()],
                ['status' => 'Lactating', 'count' => Resident::where('is_lactating', true)->count()],
                ['status' => 'Indigenous', 'count' => Resident::where('is_indigenous', true)->count()],
                ['status' => '4Ps Beneficiary', 'count' => Resident::where('is_4ps', true)->count()],
                ['status' => 'Registered Voter', 'count' => Resident::where('is_registered_voter', true)->count()],
            ],
        ];

        return response()->json($statistics);
    }

    /**
     * Get recent registrations.
     */
    public function recentRegistrations(): JsonResponse
    {
        $recentResidents = Resident::select('id', 'resident_id', 'first_name', 'last_name', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json($recentResidents);
    }

    /**
     * Get recent distributions.
     */
    public function recentDistributions(): JsonResponse
    {
        $recentDistributions = Distribution::with([
                'resident:id,first_name,last_name',
                'ayudaProgram:id,name'
            ])
            ->select('id', 'reference_number', 'resident_id', 'ayuda_program_id', 'distribution_date', 'amount', 'status')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json($recentDistributions);
    }

    /**
     * Get upcoming distribution batches.
     */
    public function upcomingBatches(): JsonResponse
    {
        $upcomingBatches = DistributionBatch::with('ayudaProgram:id,name')
            ->select('id', 'batch_number', 'ayuda_program_id', 'location', 'batch_date', 'start_time', 'status')
            ->whereIn('status', ['scheduled', 'ongoing'])
            ->where('batch_date', '>=', now()->toDateString())
            ->orderBy('batch_date')
            ->orderBy('start_time')
            ->limit(10)
            ->get();

        return response()->json($upcomingBatches);
    }

    /**
     * Get all dashboard data in a single request.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'stats' => $this->stats()->original,
            'monthly_distributions' => $this->monthlyDistributions()->original,
            'program_distributions' => $this->programDistributions()->original,
            'barangay_distributions' => $this->barangayDistributions()->original,
            'resident_statistics' => $this->residentStatistics()->original,
            'recent_registrations' => $this->recentRegistrations()->original,
            'recent_distributions' => $this->recentDistributions()->original,
            'upcoming_batches' => $this->upcomingBatches()->original,
        ]);
    }
}