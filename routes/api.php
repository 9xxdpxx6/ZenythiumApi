<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CycleController;
use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\MetricController;
use App\Http\Controllers\MuscleGroupController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\WorkoutController;
use App\Http\Controllers\WorkoutSetController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// API v1 routes
Route::prefix('v1')->group(function () {
    // Public authentication routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    
    // Test endpoint to verify CORS is working
    Route::get('/test', function () {
        return response()->json([
            'message' => 'CORS is working!',
            'timestamp' => now(),
        ]);
    });
});

// Protected API v1 routes
Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::get('/user/statistics', [StatisticsController::class, 'statistics']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
    
    // Muscle Groups CRUD routes
    Route::apiResource('muscle-groups', MuscleGroupController::class);
    
    // Cycles CRUD routes
    Route::apiResource('cycles', CycleController::class);
    
    // Exercises CRUD routes
    Route::apiResource('exercises', ExerciseController::class);
    
    // Plans CRUD routes
    Route::apiResource('plans', PlanController::class);
    
    // Workouts CRUD routes
    Route::get('/workouts', [WorkoutController::class, 'index']);
    Route::post('/workouts', [WorkoutController::class, 'store']);
    Route::get('/workouts/{workout}', [WorkoutController::class, 'show']);
    Route::put('/workouts/{workout}', [WorkoutController::class, 'update']);
    Route::delete('/workouts/{workout}', [WorkoutController::class, 'destroy']);
    Route::post('/workouts/start', [WorkoutController::class, 'start']);
    Route::post('/workouts/{workout}/finish', [WorkoutController::class, 'finish']);
    
    // Workout Sets CRUD routes
    Route::apiResource('workout-sets', WorkoutSetController::class);
    
    // Metrics CRUD routes
    Route::apiResource('metrics', MetricController::class);
    
    // Additional WorkoutSet routes
    Route::get('/workouts/{workoutId}/workout-sets', [WorkoutSetController::class, 'getByWorkout']);
    Route::get('/plan-exercises/{planExerciseId}/workout-sets', [WorkoutSetController::class, 'getByPlanExercise']);
});
