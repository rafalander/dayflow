<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = Setting::all()->keyBy('key');

        return response()->json([
            'data' => $settings,
            'status' => 'success',
        ]);
    }

    public function show(string $key): JsonResponse
    {
        $setting = Setting::where('key', $key)->firstOrFail();

        return response()->json([
            'data' => $setting,
            'status' => 'success',
        ]);
    }

    public function update(Request $request, string $key): JsonResponse
    {
        $this->authorize('admin', User::class);

        $validated = $request->validate([
            'value' => 'required|string',
            'description' => 'sometimes|string',
        ]);

        $payload = ['value' => $validated['value']];
        if (array_key_exists('description', $validated)) {
            $payload['description'] = $validated['description'];
        }

        $setting = Setting::updateOrCreate(['key' => $key], $payload);

        return response()->json([
            'data' => $setting->fresh(),
            'message' => 'Setting updated successfully',
            'status' => 'success',
        ]);
    }
}
