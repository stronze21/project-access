<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Models\Province;
use App\Models\CityMunicipality;
use App\Models\Barangay;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AddressController extends Controller
{
    /**
     * Get all regions.
     */
    public function getRegions(): JsonResponse
    {
        $regions = Region::orderBy('regDesc')->get();
        return response()->json($regions);
    }

    /**
     * Get provinces within a region.
     */
    public function getProvinces(Request $request): JsonResponse
    {
        $request->validate([
            'region_code' => 'required|string',
        ]);

        $provinces = Province::where('regCode', $request->region_code)
            ->orderBy('provDesc')
            ->get();

        return response()->json($provinces);
    }

    /**
     * Get cities/municipalities within a province.
     */
    public function getCities(Request $request): JsonResponse
    {
        $request->validate([
            'province_code' => 'required|string',
        ]);

        $cities = CityMunicipality::where('provCode', $request->province_code)
            ->orderBy('citymunDesc')
            ->get();

        return response()->json($cities);
    }

    /**
     * Get barangays within a city/municipality.
     */
    public function getBarangays(Request $request): JsonResponse
    {
        $request->validate([
            'city_code' => 'required|string',
        ]);

        $barangays = Barangay::where('citymunCode', $request->city_code)
            ->orderBy('brgyDesc')
            ->get();

        return response()->json($barangays);
    }

    /**
     * Get full address information.
     */
    public function getAddressInfo(Request $request): JsonResponse
    {
        $request->validate([
            'barangay_code' => 'required|string',
        ]);

        $barangay = Barangay::where('brgyCode', $request->barangay_code)->first();

        if (!$barangay) {
            return response()->json(['message' => 'Barangay not found'], 404);
        }

        $city = CityMunicipality::where('citymunCode', $barangay->citymunCode)->first();
        $province = Province::where('provCode', $barangay->provCode)->first();
        $region = Region::where('regCode', $barangay->regCode)->first();

        return response()->json([
            'barangay' => $barangay,
            'city' => $city,
            'province' => $province,
            'region' => $region,
        ]);
    }
}