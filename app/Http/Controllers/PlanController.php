<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\PlanRequest;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use App\Services\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PlanController extends Controller
{
    public function __construct(
        private readonly PlanService $planService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->query();
        $filters['user_id'] = $request->user()?->id;
        
        $plans = $this->planService->getAll($filters);
        
        return response()->json([
            'data' => PlanResource::collection($plans->items()),
            'message' => 'Планы успешно получены',
            'meta' => [
                'current_page' => $plans->currentPage(),
                'last_page' => $plans->lastPage(),
                'per_page' => $plans->perPage(),
                'total' => $plans->total(),
                'from' => $plans->firstItem(),
                'to' => $plans->lastItem(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PlanRequest $request): JsonResponse
    {
        $data = $request->validated();
        
        $plan = $this->planService->create($data);
        
        return response()->json([
            'data' => new PlanResource($plan->load('cycle')),
            'message' => 'План успешно создан'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Plan $plan, Request $request): JsonResponse
    {
        $plan = $this->planService->getById($plan->id, $request->user()?->id);
        
        return response()->json([
            'data' => new PlanResource($plan),
            'message' => 'План успешно получен'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PlanRequest $request, Plan $plan): JsonResponse
    {
        $plan = $this->planService->update($plan->id, $request->validated(), $request->user()?->id);
        
        return response()->json([
            'data' => new PlanResource($plan),
            'message' => 'План успешно обновлен'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Plan $plan, Request $request): JsonResponse
    {
        $this->planService->delete($plan->id, $request->user()?->id);
        
        return response()->json([
            'data' => null,
            'message' => 'План успешно удален'
        ]);
    }
}
