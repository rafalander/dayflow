<?php

use Illuminate\Support\Facades\Route;

// API routes are in routes/api.php
Route::get('/', function () {
    return response()->json(['message' => 'Dayflow API', 'version' => '1.0.0']);
});

// Catch-all for SPA routing (if needed)
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api|docs).*$');
