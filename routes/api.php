<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\MuscleGroupController;
use App\Http\Controllers\PlanController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public authentication routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
    
    // Muscle Groups CRUD routes
    Route::apiResource('muscle-groups', MuscleGroupController::class);
    
    // Exercises CRUD routes
    Route::apiResource('exercises', ExerciseController::class);
    
    // Plans CRUD routes
    Route::apiResource('plans', PlanController::class);
});

// Test endpoint to verify CORS is working
Route::get('/test', function () {
    return response()->json([
        'message' => 'CORS is working!',
        'timestamp' => now(),
    ]);
});
