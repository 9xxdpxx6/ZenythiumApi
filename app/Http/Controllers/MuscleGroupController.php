<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\MuscleGroupRequest;
use App\Http\Resources\MuscleGroupResource;
use App\Models\MuscleGroup;
use App\Services\MuscleGroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MuscleGroupController extends Controller
{
    public function __construct(
        private readonly MuscleGroupService $muscleGroupService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->query();
        $filters['user_id'] = $request->user()->id;
        
        $muscleGroups = $this->muscleGroupService->getAll($filters);
        
        return response()->json([
            'data' => MuscleGroupResource::collection($muscleGroups),
            'message' => 'Muscle groups retrieved successfully'
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MuscleGroupRequest $request): JsonResponse
    {
        $muscleGroup = $this->muscleGroupService->create($request->validated());
        
        return response()->json([
            'data' => new MuscleGroupResource($muscleGroup),
            'message' => 'Muscle group created successfully'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(MuscleGroup $muscleGroup, Request $request): JsonResponse
    {
        $muscleGroup = $this->muscleGroupService->getById($muscleGroup->id, $request->user()->id);
        
        return response()->json([
            'data' => new MuscleGroupResource($muscleGroup),
            'message' => 'Muscle group retrieved successfully'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MuscleGroupRequest $request, MuscleGroup $muscleGroup): JsonResponse
    {
        $muscleGroup = $this->muscleGroupService->update($muscleGroup->id, $request->validated());
        
        return response()->json([
            'data' => new MuscleGroupResource($muscleGroup),
            'message' => 'Muscle group updated successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MuscleGroup $muscleGroup): JsonResponse
    {
        $this->muscleGroupService->delete($muscleGroup->id);
        
        return response()->json([
            'message' => 'Muscle group deleted successfully'
        ]);
    }
}
