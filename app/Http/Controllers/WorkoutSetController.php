<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\WorkoutSetRequest;
use App\Http\Resources\WorkoutSetResource;
use App\Models\WorkoutSet;
use App\Services\WorkoutSetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WorkoutSetController extends Controller
{
    public function __construct(
        private readonly WorkoutSetService $workoutSetService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->query();
        $filters['user_id'] = $request->user()?->id;
        
        $workoutSets = $this->workoutSetService->getAll($filters);
        
        return response()->json([
            'data' => WorkoutSetResource::collection($workoutSets->items()),
            'message' => 'Подходы успешно получены',
            'meta' => [
                'current_page' => $workoutSets->currentPage(),
                'last_page' => $workoutSets->lastPage(),
                'per_page' => $workoutSets->perPage(),
                'total' => $workoutSets->total(),
                'from' => $workoutSets->firstItem(),
                'to' => $workoutSets->lastItem(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(WorkoutSetRequest $request): JsonResponse
    {
        $data = $request->validated();
        
        $workoutSet = $this->workoutSetService->create($data);
        
        return response()->json([
            'data' => new WorkoutSetResource($workoutSet->load(['workout.plan.cycle', 'workout.user', 'planExercise.exercise'])),
            'message' => 'Подход успешно создан'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(WorkoutSet $workoutSet, Request $request): JsonResponse
    {
        $workoutSet = $this->workoutSetService->getById($workoutSet->id, $request->user()?->id);
        
        return response()->json([
            'data' => new WorkoutSetResource($workoutSet),
            'message' => 'Подход успешно получен'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(WorkoutSetRequest $request, WorkoutSet $workoutSet): JsonResponse
    {
        $workoutSet = $this->workoutSetService->update($workoutSet->id, $request->validated(), $request->user()?->id);
        
        return response()->json([
            'data' => new WorkoutSetResource($workoutSet),
            'message' => 'Подход успешно обновлен'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WorkoutSet $workoutSet, Request $request): JsonResponse
    {
        $this->workoutSetService->delete($workoutSet->id, $request->user()?->id);
        
        return response()->json([
            'data' => null,
            'message' => 'Подход успешно удален'
        ]);
    }

    /**
     * Get workout sets by workout ID.
     */
    public function getByWorkout(Request $request, int $workoutId): JsonResponse
    {
        $workoutSets = $this->workoutSetService->getByWorkoutId($workoutId, $request->user()?->id);
        
        return response()->json([
            'data' => WorkoutSetResource::collection($workoutSets),
            'message' => 'Подходы тренировки успешно получены'
        ]);
    }

    /**
     * Get workout sets by plan exercise ID.
     */
    public function getByPlanExercise(Request $request, int $planExerciseId): JsonResponse
    {
        $workoutSets = $this->workoutSetService->getByPlanExerciseId($planExerciseId, $request->user()?->id);
        
        return response()->json([
            'data' => WorkoutSetResource::collection($workoutSets),
            'message' => 'Подходы упражнения успешно получены'
        ]);
    }
}
