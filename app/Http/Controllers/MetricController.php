<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\MetricRequest;
use App\Http\Resources\MetricResource;
use App\Models\Metric;
use App\Services\MetricService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MetricController extends Controller
{
    public function __construct(
        private readonly MetricService $metricService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->query();
        $filters['user_id'] = $request->user()?->id;
        
        $metrics = $this->metricService->getAll($filters);
        
        return response()->json([
            'data' => MetricResource::collection($metrics->items()),
            'message' => 'Метрики успешно получены',
            'meta' => [
                'current_page' => $metrics->currentPage(),
                'last_page' => $metrics->lastPage(),
                'per_page' => $metrics->perPage(),
                'total' => $metrics->total(),
                'from' => $metrics->firstItem(),
                'to' => $metrics->lastItem(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MetricRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()?->id;
        
        $metric = $this->metricService->create($data);
        
        return response()->json([
            'data' => new MetricResource($metric->load(['user'])),
            'message' => 'Метрика успешно создана'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Metric $metric, Request $request): JsonResponse
    {
        $metric = $this->metricService->getById($metric->id, $request->user()?->id);
        
        return response()->json([
            'data' => new MetricResource($metric),
            'message' => 'Метрика успешно получена'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MetricRequest $request, Metric $metric): JsonResponse
    {
        $metric = $this->metricService->update($metric->id, $request->validated(), $request->user()?->id);
        
        return response()->json([
            'data' => new MetricResource($metric),
            'message' => 'Метрика успешно обновлена'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Metric $metric, Request $request): JsonResponse
    {
        $this->metricService->delete($metric->id, $request->user()?->id);
        
        return response()->json([
            'data' => null,
            'message' => 'Метрика успешно удалена'
        ]);
    }
}
