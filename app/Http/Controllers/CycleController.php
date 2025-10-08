<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CycleRequest;
use App\Http\Resources\CycleResource;
use App\Models\Cycle;
use App\Services\CycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CycleController extends Controller
{
    public function __construct(
        private readonly CycleService $cycleService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->query();
        $filters['user_id'] = $request->user()?->id;
        
        $cycles = $this->cycleService->getAll($filters);
        
        return response()->json([
            'data' => CycleResource::collection($cycles->items()),
            'message' => 'Циклы успешно получены',
            'meta' => [
                'current_page' => $cycles->currentPage(),
                'last_page' => $cycles->lastPage(),
                'per_page' => $cycles->perPage(),
                'total' => $cycles->total(),
                'from' => $cycles->firstItem(),
                'to' => $cycles->lastItem(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CycleRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()?->id;
        
        $cycle = $this->cycleService->create($data);
        
        return response()->json([
            'data' => new CycleResource($cycle),
            'message' => 'Цикл успешно создан'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Cycle $cycle, Request $request): JsonResponse
    {
        $cycle = $this->cycleService->getById($cycle->id, $request->user()?->id);
        
        return response()->json([
            'data' => new CycleResource($cycle),
            'message' => 'Цикл успешно получен'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CycleRequest $request, Cycle $cycle): JsonResponse
    {
        $cycle = $this->cycleService->update($cycle->id, $request->validated(), $request->user()?->id);
        
        return response()->json([
            'data' => new CycleResource($cycle),
            'message' => 'Цикл успешно обновлен'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cycle $cycle, Request $request): JsonResponse
    {
        $this->cycleService->delete($cycle->id, $request->user()?->id);
        
        return response()->json([
            'data' => null,
            'message' => 'Цикл успешно удален'
        ]);
    }
}
