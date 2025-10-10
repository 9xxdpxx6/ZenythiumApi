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
     * @OA\Get(
     *     path="/api/v1/plans",
     *     summary="Получение списка планов тренировок",
     *     description="Возвращает пагинированный список планов тренировок текущего пользователя с возможностью фильтрации",
     *     tags={"Plans"},
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
     *         name="cycle_id",
     *         in="query",
     *         description="Фильтр по ID цикла",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="Фильтр по названию плана",
     *         required=false,
     *         @OA\Schema(type="string", example="силовая")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Планы успешно получены",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="message", type="string", example="Планы успешно получены"),
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
     * @OA\Post(
     *     path="/api/v1/plans",
     *     summary="Создание нового плана тренировок",
     *     description="Создает новый план тренировок для текущего пользователя",
     *     tags={"Plans"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","cycle_id"},
     *             @OA\Property(property="name", type="string", example="Силовая тренировка", description="Название плана"),
     *             @OA\Property(property="description", type="string", example="План для развития силы", description="Описание плана"),
     *             @OA\Property(property="cycle_id", type="integer", example=1, description="ID цикла тренировок")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="План успешно создан",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="План успешно создан")
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
     * @OA\Get(
     *     path="/api/v1/plans/{plan}",
     *     summary="Получение конкретного плана тренировок",
     *     description="Возвращает детальную информацию о плане тренировок по ID",
     *     tags={"Plans"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="plan",
     *         in="path",
     *         description="ID плана",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="План успешно получен",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="План успешно получен")
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
     *         description="План не найден",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="План не найден")
     *         )
     *     )
     * )
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $plan = $this->planService->getById($id, $request->user()?->id);
        
        if (!$plan) {
            return response()->json([
                'message' => 'План не найден'
            ], 404);
        }
        
        return response()->json([
            'data' => new PlanResource($plan),
            'message' => 'План успешно получен'
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/plans/{plan}",
     *     summary="Обновление плана тренировок",
     *     description="Обновляет информацию о существующем плане тренировок",
     *     tags={"Plans"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="plan",
     *         in="path",
     *         description="ID плана",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Силовая тренировка", description="Название плана"),
     *             @OA\Property(property="description", type="string", example="План для развития силы", description="Описание плана"),
     *             @OA\Property(property="cycle_id", type="integer", example=1, description="ID цикла тренировок")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="План успешно обновлен",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="План успешно обновлен")
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
     *         description="План не найден",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="План не найден")
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
    public function update(PlanRequest $request, int $id): JsonResponse
    {
        $plan = $this->planService->update($id, $request->validated(), $request->user()?->id);
        
        if (!$plan) {
            return response()->json([
                'message' => 'План не найден'
            ], 404);
        }
        
        return response()->json([
            'data' => new PlanResource($plan),
            'message' => 'План успешно обновлен'
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/plans/{plan}",
     *     summary="Удаление плана тренировок",
     *     description="Удаляет план тренировок и все связанные с ним упражнения и тренировки",
     *     tags={"Plans"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="plan",
     *         in="path",
     *         description="ID плана",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="План успешно удален",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="null", example=null),
     *             @OA\Property(property="message", type="string", example="План успешно удален")
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
     *         description="План не найден",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="План не найден")
     *         )
     *     )
     * )
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        $deleted = $this->planService->delete($id, $request->user()?->id);
        
        if (!$deleted) {
            return response()->json([
                'message' => 'План не найден'
            ], 404);
        }
        
        return response()->json([
            'data' => null,
            'message' => 'План успешно удален'
        ]);
    }
}
