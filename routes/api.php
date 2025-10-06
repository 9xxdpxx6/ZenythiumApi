<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Test endpoint to verify CORS is working
Route::get('/test', function () {
    return response()->json([
        'message' => 'CORS is working!',
        'timestamp' => now(),
    ]);
});
