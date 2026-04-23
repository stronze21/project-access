<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PublicServiceLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PublicServiceLinkController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => PublicServiceLink::orderBy('sort_order')->orderBy('title')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'service_type' => 'required|string|max:100',
            'description' => 'nullable|string',
            'url' => 'required|url|max:255',
            'icon' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $link = PublicServiceLink::create([
            'title' => $request->title,
            'slug' => Str::slug($request->title . '-' . Str::random(4)),
            'service_type' => $request->service_type,
            'description' => $request->description,
            'url' => $request->url,
            'icon' => $request->icon,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $request->integer('sort_order', 0),
        ]);

        return response()->json(['data' => $link], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $link = PublicServiceLink::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'service_type' => 'sometimes|required|string|max:100',
            'description' => 'nullable|string',
            'url' => 'sometimes|required|url|max:255',
            'icon' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $link->update($request->only([
            'title',
            'service_type',
            'description',
            'url',
            'icon',
            'is_active',
            'sort_order',
        ]));

        return response()->json(['data' => $link->fresh()]);
    }

    public function destroy(int $id): JsonResponse
    {
        PublicServiceLink::findOrFail($id)->delete();

        return response()->json(['message' => 'Service link deleted successfully.']);
    }
}
