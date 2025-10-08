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
            'data' => ExerciseResource::collection($exercises->items()),
            'message' => 'Упражнения успешно получены',
            'meta' => [
                'current_page' => $exercises->currentPage(),
                'last_page' => $exercises->lastPage(),
                'per_page' => $exercises->perPage(),
                'total' => $exercises->total(),
                'from' => $exercises->firstItem(),
                'to' => $exercises->lastItem(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ExerciseRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        
        $exercise = $this->exerciseService->create($data);
        
        return response()->json([
            'data' => new ExerciseResource($exercise->load('muscleGroup')),
            'message' => 'Упражнение успешно создано'
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
            'message' => 'Упражнение успешно получено'
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
            'message' => 'Упражнение успешно обновлено'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Exercise $exercise, Request $request): JsonResponse
    {
        $this->exerciseService->delete($exercise->id, $request->user()->id);
        
        return response()->json([
            'data' => null,
            'message' => 'Упражнение успешно удалено'
        ]);
    }
}
