<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ResidentIdCardController extends Controller
{
    public const MAX_BATCH_SIZE = 100;

    /**
     * Display the ID card for the specified resident in landscape orientation.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showLandscape($id)
    {
        $resident = Resident::with('household')->findOrFail($id);

        return view('residents.id-card-landscape', [
            'resident' => $resident,
            'orientation' => 'landscape',
        ]);
    }

    /**
     * Generate a batch of ID cards for multiple residents.
     *
     * @return \Illuminate\Http\Response
     */
    public function generateBatch(Request $request)
    {
        $validated = $request->validate([
            'residents' => ['nullable', 'array', 'max:'.self::MAX_BATCH_SIZE, 'required_without:barangay'],
            'residents.*' => ['required', 'integer', 'distinct', 'exists:residents,id'],
            'barangay' => ['nullable', 'string', 'max:255', 'required_without:residents', Rule::exists('households', 'barangay')],
            'status' => ['nullable', Rule::in(['all', 'active', 'inactive'])],
            'batch_number' => ['nullable', 'integer', 'min:1'],
        ], [
            'residents.max' => 'Choose no more than '.self::MAX_BATCH_SIZE.' residents in one print batch.',
            'residents.*.distinct' => 'Each resident may appear only once in a print batch.',
            'residents.*.exists' => 'One or more selected residents no longer exist.',
        ]);

        $barangay = $validated['barangay'] ?? null;
        $status = $validated['status'] ?? 'all';
        $batchNumber = (int) ($validated['batch_number'] ?? 1);
        $totalResidents = null;

        if ($barangay) {
            $query = Resident::with('household')
                ->whereHas('household', fn ($household) => $household->where('barangay', $barangay))
                ->when($status === 'active', fn ($residents) => $residents->where('is_active', true))
                ->when($status === 'inactive', fn ($residents) => $residents->where('is_active', false))
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->orderBy('id');

            $totalResidents = (clone $query)->count();
            $residents = $query
                ->offset(($batchNumber - 1) * self::MAX_BATCH_SIZE)
                ->limit(self::MAX_BATCH_SIZE)
                ->get();

            if ($residents->isEmpty()) {
                throw ValidationException::withMessages([
                    'batch_number' => "No residents were found in batch {$batchNumber} for {$barangay}.",
                ]);
            }
        } else {
            $residentIds = $validated['residents'];
            $residents = Resident::with('household')->whereIn('id', $residentIds)->get();
        }

        return view('residents.id-card-batch-landscape', [
            'residents' => $residents,
            'orientation' => 'landscape',
            'barangay' => $barangay,
            'status' => $status,
            'batchNumber' => $batchNumber,
            'totalResidents' => $totalResidents,
            'hasNextBatch' => $totalResidents !== null && $batchNumber * self::MAX_BATCH_SIZE < $totalResidents,
        ]);
    }

    /**
     * Show the form for generating multiple ID cards.
     *
     * @return \Illuminate\Http\Response
     */
    public function batchForm()
    {
        $barangayList = \App\Models\Household::select('barangay')
            ->distinct()
            ->orderBy('barangay')
            ->pluck('barangay')
            ->toArray();

        return view('residents.id-card-batch-form', [
            'barangayList' => $barangayList,
            'selectedBarangay' => request('barangay'),
            'selectedStatus' => request('status', 'active'),
            'selectedBatchNumber' => max(1, request()->integer('batch_number', 1)),
        ]);
    }

    /**
     * Redirect to the default ID card view (landscape).
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return $this->showLandscape($id);
    }
}
