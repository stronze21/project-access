<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ResidentIdCardController extends Controller
{
    /**
     * Display the ID card for the specified resident in landscape orientation.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showLandscape($id)
    {
        $current_logo_url = null;
        $current_favicon_url = null;
        $resident = Resident::with('household')->findOrFail($id);

        $logo = SystemSetting::where('key', 'app_logo')->first();
        $favicon = SystemSetting::where('key', 'app_favicon')->first();

        if ($logo && $logo->value) {
            $current_logo_url = Storage::url($logo->value);
        }

        if ($favicon && $favicon->value) {
            $current_favicon_url = Storage::url($favicon->value);
        }

        return view('residents.id-card-landscape', [
            'resident' => $resident,
            'orientation' => 'landscape',
            'current_logo_url' => $current_logo_url,
            'current_favicon_url' => $current_favicon_url,
        ]);
    }

    /**
     * Display the ID card for the specified resident in portrait orientation.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showPortrait($id)
    {
        $resident = Resident::with('household')->findOrFail($id);

        return view('residents.id-card-portrait', [
            'resident' => $resident,
            'orientation' => 'portrait'
        ]);
    }

    /**
     * Generate a batch of ID cards for multiple residents.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function generateBatch(Request $request)
    {
        $request->validate([
            'residents' => 'required|array',
            'residents.*' => 'exists:residents,id',
            'orientation' => 'required|in:landscape,portrait'
        ]);

        $residentIds = $request->input('residents');
        $orientation = $request->input('orientation', 'landscape');
        $residents = Resident::with('household')->whereIn('id', $residentIds)->get();

        if ($orientation === 'portrait') {
            return view('residents.id-card-batch-portrait', [
                'residents' => $residents,
                'orientation' => 'portrait'
            ]);
        } else {
            return view('residents.id-card-batch-landscape', [
                'residents' => $residents,
                'orientation' => 'landscape'
            ]);
        }
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
            'barangayList' => $barangayList
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