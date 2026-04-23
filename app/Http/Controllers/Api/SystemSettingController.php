<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class SystemSettingController extends Controller
{
    /**
     * Get all system settings.
     */
    public function index(Request $request): JsonResponse
    {
        $query = SystemSetting::query();

        // Filter by group if provided
        if ($request->has('group') && $request->group) {
            $query->where('group', $request->group);
        }

        // Only return public settings for non-admin users
        if (!$request->user()->hasRole(['admin', 'system-administrator'])) {
            $query->where('is_public', true);
        }

        $settings = $query->get();

        // Format as key-value pairs if requested
        if ($request->has('format') && $request->format === 'key_value') {
            $formattedSettings = [];
            foreach ($settings as $setting) {
                $formattedSettings[$setting->key] = $setting->value;
            }
            return response()->json($formattedSettings);
        }

        return response()->json($settings);
    }

    /**
     * Get a specific system setting.
     */
    public function show(string $key): JsonResponse
    {
        $setting = SystemSetting::where('key', $key)->firstOrFail();

        // Check if setting is private and user is not admin
        if (!$setting->is_public && !request()->user()->hasRole(['admin', 'system-administrator'])) {
            return response()->json(['message' => 'You do not have permission to access this setting.'], 403);
        }

        return response()->json([
            'data' => $setting
        ]);
    }

    /**
     * Update or create a system setting.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string|max:100',
            'value' => 'required|string|max:1000',
            'group' => 'nullable|string|max:50',
            'type' => 'nullable|string|max:20',
            'is_public' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Only admins can update settings
        if (!$request->user()->hasRole(['admin', 'system-administrator'])) {
            return response()->json(['message' => 'You do not have permission to update system settings.'], 403);
        }

        $data = $validator->validated();

        // Default values
        if (!isset($data['group'])) {
            $data['group'] = 'general';
        }

        if (!isset($data['type'])) {
            $data['type'] = 'string';
        }

        if (!isset($data['is_public'])) {
            $data['is_public'] = true;
        }

        $setting = SystemSetting::updateOrCreate(
            ['key' => $data['key']],
            $data
        );

        return response()->json([
            'message' => 'System setting saved successfully',
            'data' => $setting
        ], 201);
    }

    /**
     * Update a system setting.
     */
    public function update(Request $request, string $key): JsonResponse
    {
        $setting = SystemSetting::where('key', $key)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'value' => 'sometimes|required|string|max:1000',
            'group' => 'nullable|string|max:50',
            'type' => 'nullable|string|max:20',
            'is_public' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Only admins can update settings
        if (!$request->user()->hasRole(['admin', 'system-administrator'])) {
            return response()->json(['message' => 'You do not have permission to update system settings.'], 403);
        }

        $setting->update($validator->validated());

        return response()->json([
            'message' => 'System setting updated successfully',
            'data' => $setting
        ]);
    }

    /**
     * Delete a system setting.
     */
    public function destroy(string $key): JsonResponse
    {
        $setting = SystemSetting::where('key', $key)->firstOrFail();

        // Only admins can delete settings
        if (!request()->user()->hasRole(['admin', 'system-administrator'])) {
            return response()->json(['message' => 'You do not have permission to delete system settings.'], 403);
        }

        $setting->delete();

        return response()->json([
            'message' => 'System setting deleted successfully'
        ]);
    }

    /**
     * Get settings by group.
     */
    public function byGroup(string $group): JsonResponse
    {
        $query = SystemSetting::where('group', $group);

        // Only return public settings for non-admin users
        if (!request()->user()->hasRole(['admin', 'system-administrator'])) {
            $query->where('is_public', true);
        }

        $settings = $query->get();

        // Format as key-value pairs
        $formattedSettings = [];
        foreach ($settings as $setting) {
            $formattedSettings[$setting->key] = $setting->value;
        }

        return response()->json($formattedSettings);
    }

    /**
     * Clear all system settings cache.
     */
    public function clearCache(): JsonResponse
    {
        // Only admins can clear cache
        if (!request()->user()->hasRole(['admin', 'system-administrator'])) {
            return response()->json(['message' => 'You do not have permission to clear system settings cache.'], 403);
        }

        SystemSetting::clearCache();

        return response()->json([
            'message' => 'System settings cache cleared successfully'
        ]);
    }
}
