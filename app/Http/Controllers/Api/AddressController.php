<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
    public function regions(): JsonResponse
    {
        $regions = Region::orderBy('regDesc')->get();

        return response()->json($regions);
    }

    /**
     * Get provinces by region.
     */
    public function provinces(Request $request): JsonResponse
    {
        $query = Province::query();

        if ($request->has('region') && $request->region) {
            $query->where('regCode', $request->region);
        }

        $provinces = $query->orderBy('provDesc')->get();

        return response()->json($provinces);
    }

    /**
     * Get cities/municipalities by province.
     */
    public function cities(Request $request): JsonResponse
    {
        $query = CityMunicipality::query();

        if ($request->has('province') && $request->province) {
            $query->where('provCode', $request->province);
        }

        $cities = $query->orderBy('citymunDesc')->get();

        return response()->json($cities);
    }

    /**
     * Get barangays by city/municipality.
     */
    public function barangays(Request $request): JsonResponse
    {
        $query = Barangay::query();

        if ($request->has('city') && $request->city) {
            $query->where('citymunCode', $request->city);
        }

        $barangays = $query->orderBy('brgyDesc')->get();

        return response()->json($barangays);
    }

    /**
     * Get a specific region by code.
     */
    public function region(string $code): JsonResponse
    {
        $region = Region::where('regCode', $code)->firstOrFail();

        return response()->json($region);
    }

    /**
     * Get a specific province by code.
     */
    public function province(string $code): JsonResponse
    {
        $province = Province::where('provCode', $code)->firstOrFail();

        return response()->json($province);
    }

    /**
     * Get a specific city/municipality by code.
     */
    public function city(string $code): JsonResponse
    {
        $city = CityMunicipality::where('citymunCode', $code)->firstOrFail();

        return response()->json($city);
    }

    /**
     * Get a specific barangay by code.
     */
    public function barangay(string $code): JsonResponse
    {
        $barangay = Barangay::where('brgyCode', $code)->firstOrFail();

        return response()->json($barangay);
    }
}
