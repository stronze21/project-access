<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use Illuminate\Http\Request;

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
        $request->validate([
            'residents' => ['required', 'array', 'max:'.self::MAX_BATCH_SIZE],
            'residents.*' => ['required', 'integer', 'distinct', 'exists:residents,id'],
        ], [
            'residents.max' => 'Choose no more than '.self::MAX_BATCH_SIZE.' residents in one print batch.',
            'residents.*.distinct' => 'Each resident may appear only once in a print batch.',
            'residents.*.exists' => 'One or more selected residents no longer exist.',
        ]);

        $residentIds = $request->input('residents');
        $residents = Resident::with('household')->whereIn('id', $residentIds)->get();

        return view('residents.id-card-batch-landscape', [
            'residents' => $residents,
            'orientation' => 'landscape',
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
