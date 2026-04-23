<?php

namespace App\Http\Controllers\Api\ResidentPortal;

use App\Http\Controllers\Controller;
use App\Models\GrievanceReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class GrievanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $resident = $request->user();

        $query = GrievanceReport::where('resident_id', $resident->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $reports = $query->latest()->paginate($request->integer('per_page', 10));

        return response()->json([
            'data' => $reports->items(),
            'meta' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'category' => 'required|string|max:100',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'location_label' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $path = null;
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('grievances', 'public');
        }

        $report = GrievanceReport::create([
            'resident_id' => $request->user()->id,
            'category' => $request->category,
            'subject' => $request->subject,
            'description' => $request->description,
            'status' => 'submitted',
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'location_label' => $request->location_label,
            'photo_path' => $path,
        ]);

        return response()->json([
            'message' => 'Report submitted successfully.',
            'data' => array_merge($report->toArray(), [
                'photo_url' => $path ? Storage::disk('public')->url($path) : null,
            ]),
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $report = GrievanceReport::where('resident_id', $request->user()->id)->findOrFail($id);

        return response()->json([
            'data' => array_merge($report->toArray(), [
                'photo_url' => $report->photo_path ? Storage::disk('public')->url($report->photo_path) : null,
            ]),
        ]);
    }
}
