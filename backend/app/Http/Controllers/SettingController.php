<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    /**
     * Get all settings
     */
    public function index(): JsonResponse
    {
        $settings = Setting::all()->keyBy('key');

        return response()->json([
            'data' => $settings,
            'status' => 'success',
        ]);
    }

    /**
     * Get single setting
     */
    public function show(string $key): JsonResponse
    {
        $setting = Setting::where('key', $key)->firstOrFail();

        return response()->json([
            'data' => $setting,
            'status' => 'success',
        ]);
    }

    /**
     * Update setting
     */
    public function update(Request $request, string $key): JsonResponse
    {
        $this->authorize('admin');

        $setting = Setting::where('key', $key)->firstOrFail();

        $validated = $request->validate([
            'value' => 'required|string',
            'description' => 'sometimes|string',
        ]);

        $setting->update($validated);

        return response()->json([
            'data' => $setting,
            'message' => 'Setting updated successfully',
            'status' => 'success',
        ]);
    }
}
