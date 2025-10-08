<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ExerciseRequest;
use App\Http\Resources\ExerciseResource;
use App\Models\Exercise;
use App\Services\ExerciseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ExerciseController extends Controller
{
    public function __construct(
        private readonly ExerciseService $exerciseService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->query();
        $filters['user_id'] = $request->user()->id;
        
        $exercises = $this->exerciseService->getAll($filters);
        
        return response()->json([
            'data' => ExerciseResource::collection($exercises),
            'message' => 'Exercises retrieved successfully'
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ExerciseRequest $request): JsonResponse
    {
        $exercise = $this->exerciseService->create($request->validated());
        
        return response()->json([
            'data' => new ExerciseResource($exercise->load('muscleGroup')),
            'message' => 'Exercise created successfully'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Exercise $exercise, Request $request): JsonResponse
    {
        $exercise = $this->exerciseService->getById($exercise->id, $request->user()->id);
        
        return response()->json([
            'data' => new ExerciseResource($exercise),
            'message' => 'Exercise retrieved successfully'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ExerciseRequest $request, Exercise $exercise): JsonResponse
    {
        $exercise = $this->exerciseService->update($exercise->id, $request->validated(), $request->user()->id);
        
        return response()->json([
            'data' => new ExerciseResource($exercise),
            'message' => 'Exercise updated successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Exercise $exercise, Request $request): JsonResponse
    {
        $this->exerciseService->delete($exercise->id, $request->user()->id);
        
        return response()->json([
            'message' => 'Exercise deleted successfully'
        ]);
    }
}
