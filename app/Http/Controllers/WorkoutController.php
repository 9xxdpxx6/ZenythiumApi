<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\WorkoutRequest;
use App\Http\Resources\WorkoutResource;
use App\Models\Workout;
use App\Services\WorkoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WorkoutController extends Controller
{
    public function __construct(
        private readonly WorkoutService $workoutService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->query();
        $filters['user_id'] = $request->user()?->id;
        
        $workouts = $this->workoutService->getAll($filters);
        
        return response()->json([
            'data' => WorkoutResource::collection($workouts->items()),
            'message' => 'Тренировки успешно получены',
            'meta' => [
                'current_page' => $workouts->currentPage(),
                'last_page' => $workouts->lastPage(),
                'per_page' => $workouts->perPage(),
                'total' => $workouts->total(),
                'from' => $workouts->firstItem(),
                'to' => $workouts->lastItem(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(WorkoutRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()?->id;
        
        $workout = $this->workoutService->create($data);
        
        return response()->json([
            'data' => new WorkoutResource($workout->load(['plan.cycle', 'user'])),
            'message' => 'Тренировка успешно создана'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Workout $workout, Request $request): JsonResponse
    {
        $workout = $this->workoutService->getById($workout->id, $request->user()?->id);
        
        return response()->json([
            'data' => new WorkoutResource($workout),
            'message' => 'Тренировка успешно получена'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(WorkoutRequest $request, Workout $workout): JsonResponse
    {
        $workout = $this->workoutService->update($workout->id, $request->validated(), $request->user()?->id);
        
        return response()->json([
            'data' => new WorkoutResource($workout),
            'message' => 'Тренировка успешно обновлена'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Workout $workout, Request $request): JsonResponse
    {
        $this->workoutService->delete($workout->id, $request->user()?->id);
        
        return response()->json([
            'data' => null,
            'message' => 'Тренировка успешно удалена'
        ]);
    }

    /**
     * Start a new workout for a plan.
     */
    public function start(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => 'required|integer|exists:plans,id',
        ]);

        $userId = $request->user()?->id;
        if (!$userId) {
            return response()->json(['message' => 'Пользователь не аутентифицирован'], 401);
        }

        $workout = $this->workoutService->start($request->plan_id, $userId);
        
        return response()->json([
            'data' => new WorkoutResource($workout->load(['plan.cycle', 'user'])),
            'message' => 'Тренировка успешно запущена'
        ], 201);
    }

    /**
     * Finish a workout.
     */
    public function finish(Request $request, Workout $workout): JsonResponse
    {
        $userId = $request->user()?->id;
        if (!$userId) {
            return response()->json(['message' => 'Пользователь не аутентифицирован'], 401);
        }

        try {
            $workout = $this->workoutService->finish($workout->id, $userId);
            
            return response()->json([
                'data' => new WorkoutResource($workout),
                'message' => 'Тренировка успешно завершена',
                'duration_minutes' => $workout->duration_minutes
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
