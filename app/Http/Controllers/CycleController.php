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
     * @OA\Get(
     *     path="/api/v1/cycles",
     *     summary="Получение списка циклов тренировок",
     *     description="Возвращает пагинированный список циклов тренировок текущего пользователя с возможностью фильтрации",
     *     tags={"Cycles"},
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
     *         name="name",
     *         in="query",
     *         description="Фильтр по названию цикла",
     *         required=false,
     *         @OA\Schema(type="string", example="набор")
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Фильтр по дате начала (от)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="Фильтр по дате окончания (до)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-31")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Циклы успешно получены",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="message", type="string", example="Циклы успешно получены"),
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
     * @OA\Post(
     *     path="/api/v1/cycles",
     *     summary="Создание нового цикла тренировок",
     *     description="Создает новый цикл тренировок для текущего пользователя",
     *     tags={"Cycles"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","weeks"},
     *             @OA\Property(property="name", type="string", example="Набор массы", description="Название цикла"),
     *             @OA\Property(property="start_date", type="string", format="date", example="2024-01-01", description="Дата начала цикла (необязательно)"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2024-03-31", description="Дата окончания цикла (необязательно)"),
     *             @OA\Property(property="weeks", type="integer", example=6, description="Количество недель в цикле")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Цикл успешно создан",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Цикл успешно создан")
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
     * @OA\Get(
     *     path="/api/v1/cycles/{cycle}",
     *     summary="Получение конкретного цикла тренировок",
     *     description="Возвращает детальную информацию о цикле тренировок по ID",
     *     tags={"Cycles"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="cycle",
     *         in="path",
     *         description="ID цикла",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Цикл успешно получен",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Цикл успешно получен")
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
     *         description="Цикл не найден",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Цикл не найден")
     *         )
     *     )
     * )
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $cycle = $this->cycleService->getById($id, $request->user()?->id);
        
        if (!$cycle) {
            return response()->json([
                'message' => 'Цикл не найден'
            ], 404);
        }
        
        return response()->json([
            'data' => new CycleResource($cycle),
            'message' => 'Цикл успешно получен'
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/cycles/{cycle}",
     *     summary="Обновление цикла тренировок",
     *     description="Обновляет информацию о существующем цикле тренировок",
     *     tags={"Cycles"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="cycle",
     *         in="path",
     *         description="ID цикла",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Набор массы", description="Название цикла"),
     *             @OA\Property(property="start_date", type="string", format="date", example="2024-01-01", description="Дата начала цикла (необязательно)"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2024-03-31", description="Дата окончания цикла (необязательно)"),
     *             @OA\Property(property="weeks", type="integer", example=6, description="Количество недель в цикле")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Цикл успешно обновлен",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Цикл успешно обновлен")
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
     *         description="Цикл не найден",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Цикл не найден")
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
    public function update(CycleRequest $request, int $id): JsonResponse
    {
        $cycle = $this->cycleService->update($id, $request->validated(), $request->user()?->id);
        
        if (!$cycle) {
            return response()->json([
                'message' => 'Цикл не найден'
            ], 404);
        }
        
        return response()->json([
            'data' => new CycleResource($cycle),
            'message' => 'Цикл успешно обновлен'
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/cycles/{cycle}",
     *     summary="Удаление цикла тренировок",
     *     description="Удаляет цикл тренировок и все связанные с ним планы и тренировки",
     *     tags={"Cycles"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="cycle",
     *         in="path",
     *         description="ID цикла",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Цикл успешно удален",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="null", example=null),
     *             @OA\Property(property="message", type="string", example="Цикл успешно удален")
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
     *         description="Цикл не найден",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Цикл не найден")
     *         )
     *     )
     * )
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        $deleted = $this->cycleService->delete($id, $request->user()?->id);
        
        if (!$deleted) {
            return response()->json([
                'message' => 'Цикл не найден'
            ], 404);
        }
        
        return response()->json([
            'data' => null,
            'message' => 'Цикл успешно удален'
        ]);
    }
}
