<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resident;
use Illuminate\Http\Request;

class ResidentApiController extends Controller
{
    /**
     * Get a list of residents with optional filtering.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Resident::with('household');

        // Apply barangay filter
        if ($request->has('barangay') && !empty($request->barangay)) {
            $query->whereHas('household', function ($q) use ($request) {
                $q->where('barangay', $request->barangay);
            });
        }

        // Apply status filter
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('is_active', $request->status === 'active');
        }

        // Apply search filter
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', '%' . $search . '%')
                    ->orWhere('last_name', 'like', '%' . $search . '%')
                    ->orWhere('middle_name', 'like', '%' . $search . '%')
                    ->orWhere('resident_id', 'like', '%' . $search . '%');
            });
        }

        // Apply limit
        $limit = $request->has('limit') ? (int)$request->limit : 100;

        // Order by
        $query->orderBy('last_name')->orderBy('first_name');

        // Get results
        $residents = $query->limit($limit)->get();

        return response()->json($residents);
    }
}
