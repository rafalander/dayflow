<?php

namespace App\Http\Controllers;

use App\Support\AbsenceTypes;
use Illuminate\Http\JsonResponse;

class AbsenceTypeController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => AbsenceTypes::forApi(),
            'status' => 'success',
        ]);
    }
}
