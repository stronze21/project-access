<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ComplaintBarangay;
use App\Models\ComplaintCategory;
use App\Models\PublicOfficial;
use Illuminate\Http\JsonResponse;

class MobileLookupController extends Controller
{
    public function categories(): JsonResponse
    {
        return response()->json([
            'data' => ComplaintCategory::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (ComplaintCategory $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                ]),
        ]);
    }

    public function barangays(): JsonResponse
    {
        return response()->json([
            'data' => ComplaintBarangay::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (ComplaintBarangay $barangay): array => [
                    'id' => $barangay->id,
                    'name' => $barangay->name,
                ]),
        ]);
    }

    public function officials(): JsonResponse
    {
        return response()->json([
            'data' => PublicOfficial::query()
                ->where('is_active', true)
                ->orderBy('position')
                ->orderBy('name')
                ->get(['id', 'name', 'position'])
                ->map(fn (PublicOfficial $official): array => [
                    'id' => $official->id,
                    'name' => $official->name,
                    'position' => $official->position,
                ]),
        ]);
    }
}

