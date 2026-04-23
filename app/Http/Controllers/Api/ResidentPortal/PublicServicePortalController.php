<?php

namespace App\Http\Controllers\Api\ResidentPortal;

use App\Http\Controllers\Controller;
use App\Models\PublicServiceLink;
use Illuminate\Http\JsonResponse;

class PublicServicePortalController extends Controller
{
    public function index(): JsonResponse
    {
        $links = PublicServiceLink::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        return response()->json([
            'data' => $links,
            'grouped' => $links->groupBy('service_type'),
        ]);
    }
}
