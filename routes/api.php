<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CycleController;
use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\MetricController;
use App\Http\Controllers\MuscleGroupController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\PlanExerciseController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\WorkoutController;
use App\Http\Controllers\WorkoutSetController;
use App\Http\Controllers\TrainingProgramController;
use App\Http\Controllers\GoalController;
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
Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:200,1'])->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::put('/user', [AuthController::class, 'updateProfile']);
    Route::get('/user/statistics', [StatisticsController::class, 'statistics']);
    Route::get('/user/exercise-statistics', [StatisticsController::class, 'exerciseStatistics']);
    Route::get('/user/time-analytics', [StatisticsController::class, 'timeAnalytics']);
    Route::get('/user/muscle-group-statistics', [StatisticsController::class, 'muscleGroupStatistics']);
    Route::get('/user/records', [StatisticsController::class, 'records']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
    
    // Muscle Groups CRUD routes
    Route::get('/muscle-groups', [MuscleGroupController::class, 'index']);
    Route::post('/muscle-groups', [MuscleGroupController::class, 'store']);
    Route::get('/muscle-groups/{id}', [MuscleGroupController::class, 'show']);
    Route::put('/muscle-groups/{id}', [MuscleGroupController::class, 'update']);
    Route::delete('/muscle-groups/{id}', [MuscleGroupController::class, 'destroy']);
    
    // Cycles CRUD routes
    Route::get('/cycles', [CycleController::class, 'index']);
    Route::post('/cycles', [CycleController::class, 'store']);
    Route::get('/cycles/{id}', [CycleController::class, 'show']);
    Route::put('/cycles/{id}', [CycleController::class, 'update']);
    Route::delete('/cycles/{id}', [CycleController::class, 'destroy']);
    
    // Exercises CRUD routes
    Route::get('/exercises', [ExerciseController::class, 'index']);
    Route::post('/exercises', [ExerciseController::class, 'store']);
    Route::get('/exercises/{id}', [ExerciseController::class, 'show']);
    Route::put('/exercises/{id}', [ExerciseController::class, 'update']);
    Route::delete('/exercises/{id}', [ExerciseController::class, 'destroy']);
    
    // Plans CRUD routes
    Route::get('/plans', [PlanController::class, 'index']);
    Route::post('/plans', [PlanController::class, 'store']);
    Route::get('/plans/{id}', [PlanController::class, 'show']);
    Route::put('/plans/{id}', [PlanController::class, 'update']);
    Route::delete('/plans/{id}', [PlanController::class, 'destroy']);
    Route::post('/plans/{id}/duplicate', [PlanController::class, 'duplicate'])->name('plans.duplicate');
    
    // Plan Exercises management routes
    Route::get('/plan-exercises', [PlanExerciseController::class, 'getAllForUser']);
    Route::get('/plans/{plan}/exercises', [PlanExerciseController::class, 'index']);
    Route::post('/plans/{plan}/exercises', [PlanExerciseController::class, 'store']);
    Route::put('/plans/{plan}/exercises/{planExercise}', [PlanExerciseController::class, 'update']);
    Route::delete('/plans/{plan}/exercises/{planExercise}', [PlanExerciseController::class, 'destroy']);
    
    // Workouts CRUD routes
    Route::get('/workouts', [WorkoutController::class, 'index']);
    Route::post('/workouts', [WorkoutController::class, 'store']);
    Route::get('/workouts/{id}', [WorkoutController::class, 'show']);
    Route::put('/workouts/{id}', [WorkoutController::class, 'update']);
    Route::delete('/workouts/{id}', [WorkoutController::class, 'destroy']);
    Route::post('/workouts/start', [WorkoutController::class, 'start']);
    Route::post('/workouts/{id}/finish', [WorkoutController::class, 'finish']);
    
    // Workout Sets CRUD routes
    Route::get('/workout-sets', [WorkoutSetController::class, 'index']);
    Route::post('/workout-sets', [WorkoutSetController::class, 'store']);
    Route::get('/workout-sets/{id}', [WorkoutSetController::class, 'show']);
    Route::put('/workout-sets/{id}', [WorkoutSetController::class, 'update']);
    Route::delete('/workout-sets/{id}', [WorkoutSetController::class, 'destroy']);
    
    // Metrics CRUD routes
    Route::get('/metrics', [MetricController::class, 'index']);
    Route::post('/metrics', [MetricController::class, 'store']);
    Route::get('/metrics/{id}', [MetricController::class, 'show']);
    Route::put('/metrics/{id}', [MetricController::class, 'update']);
    Route::delete('/metrics/{id}', [MetricController::class, 'destroy']);
    
    // Additional WorkoutSet routes
    Route::get('/workouts/{workoutId}/workout-sets', [WorkoutSetController::class, 'getByWorkout']);
    Route::get('/plan-exercises/{planExerciseId}/workout-sets', [WorkoutSetController::class, 'getByPlanExercise']);
    
    // Training Programs routes
    Route::get('/training-programs', [TrainingProgramController::class, 'index']);
    Route::get('/training-programs/{id}', [TrainingProgramController::class, 'show']);
    Route::post('/training-programs/{id}/install', [TrainingProgramController::class, 'install']);
    Route::delete('/training-programs/{id}/uninstall', [TrainingProgramController::class, 'uninstall']);
    
    // Goals routes
    Route::get('/goals', [GoalController::class, 'index']);
    Route::post('/goals', [GoalController::class, 'store']);
    Route::get('/goals/statistics', [GoalController::class, 'statistics']);
    Route::get('/goals/completed', [GoalController::class, 'completed']);
    Route::get('/goals/failed', [GoalController::class, 'failed']);
    Route::get('/goals/{id}', [GoalController::class, 'show']);
    Route::put('/goals/{id}', [GoalController::class, 'update']);
    Route::delete('/goals/{id}', [GoalController::class, 'destroy']);
    
    // Device tokens for push notifications
    Route::post('/user/device-tokens', [AuthController::class, 'registerDeviceToken']);
    Route::delete('/user/device-tokens/{id}', [AuthController::class, 'removeDeviceToken']);
});
