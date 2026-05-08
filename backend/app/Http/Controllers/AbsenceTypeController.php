<?php

namespace App\Http\Controllers;

use App\Support\AbsenceTypes;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class AbsenceTypeController extends Controller
{
    public function index(): JsonResponse
    {
        $ttl = (int) config('dayflow.api_read_cache.absence_types', 86400);

        $data = Cache::remember(
            'api.absence_types',
            $ttl,
            static fn () => AbsenceTypes::forApi()
        );

        return response()->json([
            'data' => $data,
            'status' => 'success',
        ]);
    }
}
