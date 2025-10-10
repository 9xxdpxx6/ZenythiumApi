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
     * @OA\Get(
     *     path="/api/v1/metrics",
     *     summary="Получение списка метрик",
     *     description="Возвращает пагинированный список метрик (вес, объемы тела) текущего пользователя с возможностью фильтрации",
     *     tags={"Metrics"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Номер страницы",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Количество элементов на странице",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         description="Фильтр по дате (от)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         description="Фильтр по дате (до)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-31")
     *     ),
     *     @OA\Parameter(
     *         name="weight_min",
     *         in="query",
     *         description="Фильтр по минимальному весу",
     *         required=false,
     *         @OA\Schema(type="number", format="float", example=70.0)
     *     ),
     *     @OA\Parameter(
     *         name="weight_max",
     *         in="query",
     *         description="Фильтр по максимальному весу",
     *         required=false,
     *         @OA\Schema(type="number", format="float", example=80.0)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Метрики успешно получены",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="message", type="string", example="Метрики успешно получены"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизован",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
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
     * @OA\Post(
     *     path="/api/v1/metrics",
     *     summary="Создание новой метрики",
     *     description="Создает новую запись метрики (вес, объемы тела) для текущего пользователя",
     *     tags={"Metrics"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"weight","date"},
     *             @OA\Property(property="weight", type="number", format="float", example=75.5, description="Вес в килограммах"),
     *             @OA\Property(property="body_fat_percentage", type="number", format="float", example=15.2, description="Процент жира в теле"),
     *             @OA\Property(property="muscle_mass", type="number", format="float", example=60.0, description="Мышечная масса в килограммах"),
     *             @OA\Property(property="date", type="string", format="date", example="2024-01-15", description="Дата измерения")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Метрика успешно создана",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Метрика успешно создана")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизован",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Ошибка валидации"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/v1/metrics/{metric}",
     *     summary="Получение конкретной метрики",
     *     description="Возвращает детальную информацию о метрике по ID",
     *     tags={"Metrics"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="metric",
     *         in="path",
     *         description="ID метрики",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Метрика успешно получена",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Метрика успешно получена")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизован",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Метрика не найдена",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Метрика не найдена")
     *         )
     *     )
     * )
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
     * @OA\Put(
     *     path="/api/v1/metrics/{metric}",
     *     summary="Обновление метрики",
     *     description="Обновляет информацию о существующей метрике",
     *     tags={"Metrics"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="metric",
     *         in="path",
     *         description="ID метрики",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="weight", type="number", format="float", example=75.5, description="Вес в килограммах"),
     *             @OA\Property(property="body_fat_percentage", type="number", format="float", example=15.2, description="Процент жира в теле"),
     *             @OA\Property(property="muscle_mass", type="number", format="float", example=60.0, description="Мышечная масса в килограммах"),
     *             @OA\Property(property="date", type="string", format="date", example="2024-01-15", description="Дата измерения")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Метрика успешно обновлена",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Метрика успешно обновлена")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизован",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Метрика не найдена",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Метрика не найдена")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Ошибка валидации"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
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
     * @OA\Delete(
     *     path="/api/v1/metrics/{metric}",
     *     summary="Удаление метрики",
     *     description="Удаляет запись метрики",
     *     tags={"Metrics"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="metric",
     *         in="path",
     *         description="ID метрики",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Метрика успешно удалена",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="null", example=null),
     *             @OA\Property(property="message", type="string", example="Метрика успешно удалена")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизован",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Метрика не найдена",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Метрика не найдена")
     *         )
     *     )
     * )
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
