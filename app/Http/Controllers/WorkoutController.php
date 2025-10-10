<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\WorkoutRequest;
use App\Http\Resources\WorkoutResource;
use App\Models\Workout;
use App\Services\WorkoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WorkoutController extends Controller
{
    public function __construct(
        private readonly WorkoutService $workoutService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/workouts",
     *     summary="Получение списка тренировок",
     *     description="Возвращает пагинированный список тренировок текущего пользователя с возможностью фильтрации",
     *     tags={"Workouts"},
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
     *         name="plan_id",
     *         in="query",
     *         description="Фильтр по ID плана",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="started_at",
     *         in="query",
     *         description="Фильтр по дате начала (от)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="finished_at",
     *         in="query",
     *         description="Фильтр по дате окончания (до)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-31")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Тренировки успешно получены",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="message", type="string", example="Тренировки успешно получены"),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=3),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=45)
     *             )
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
        
        $workouts = $this->workoutService->getAll($filters);
        
        return response()->json([
            'data' => WorkoutResource::collection($workouts->items()),
            'message' => 'Тренировки успешно получены',
            'meta' => [
                'current_page' => $workouts->currentPage(),
                'last_page' => $workouts->lastPage(),
                'per_page' => $workouts->perPage(),
                'total' => $workouts->total(),
                'from' => $workouts->firstItem(),
                'to' => $workouts->lastItem(),
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/workouts",
     *     summary="Создание новой тренировки",
     *     description="Создает новую тренировку для указанного плана",
     *     tags={"Workouts"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"plan_id","started_at"},
     *             @OA\Property(property="plan_id", type="integer", example=1, description="ID плана тренировки"),
     *             @OA\Property(property="started_at", type="string", format="date-time", example="2024-01-01 10:00:00", description="Время начала тренировки"),
     *             @OA\Property(property="finished_at", type="string", format="date-time", example="2024-01-01 11:00:00", description="Время окончания тренировки (необязательно)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Тренировка успешно создана",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Тренировка успешно создана")
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
    public function store(WorkoutRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()?->id;
        
        $workout = $this->workoutService->create($data);
        
        return response()->json([
            'data' => new WorkoutResource($workout),
            'message' => 'Тренировка успешно создана'
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/workouts/{workout}",
     *     summary="Получение конкретной тренировки",
     *     description="Возвращает детальную информацию о тренировке по ID",
     *     tags={"Workouts"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="workout",
     *         in="path",
     *         description="ID тренировки",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Тренировка успешно получена",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Тренировка успешно получена")
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
     *         description="Тренировка не найдена",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Тренировка не найдена")
     *         )
     *     )
     * )
     */
    public function show(Workout $workout, Request $request): JsonResponse
    {
        $workout = $this->workoutService->getById($workout->id, $request->user()?->id);
        
        if (!$workout) {
            return response()->json([
                'message' => 'Тренировка не найдена'
            ], 404);
        }
        
        return response()->json([
            'data' => new WorkoutResource($workout),
            'message' => 'Тренировка успешно получена'
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/workouts/{workout}",
     *     summary="Обновление тренировки",
     *     description="Обновляет информацию о существующей тренировке",
     *     tags={"Workouts"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="workout",
     *         in="path",
     *         description="ID тренировки",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="plan_id", type="integer", example=1, description="ID плана тренировки"),
     *             @OA\Property(property="started_at", type="string", format="date-time", example="2024-01-15T10:00:00Z", description="Время начала тренировки (ISO 8601)"),
     *             @OA\Property(property="finished_at", type="string", format="date-time", example="2024-01-15T11:30:00Z", description="Время окончания тренировки (ISO 8601)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Тренировка успешно обновлена",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Тренировка успешно обновлена")
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
     *         description="Тренировка не найдена",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Тренировка не найдена")
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
    public function update(WorkoutRequest $request, Workout $workout): JsonResponse
    {
        $workout = $this->workoutService->update($workout->id, $request->validated(), $request->user()?->id);
        
        if (!$workout) {
            return response()->json([
                'message' => 'Тренировка не найдена'
            ], 404);
        }
        
        return response()->json([
            'data' => new WorkoutResource($workout),
            'message' => 'Тренировка успешно обновлена'
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/workouts/{workout}",
     *     summary="Удаление тренировки",
     *     description="Удаляет тренировку и все связанные с ней подходы",
     *     tags={"Workouts"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="workout",
     *         in="path",
     *         description="ID тренировки",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Тренировка успешно удалена",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="null", example=null),
     *             @OA\Property(property="message", type="string", example="Тренировка успешно удалена")
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
     *         description="Тренировка не найдена",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Тренировка не найдена")
     *         )
     *     )
     * )
     */
    public function destroy(Workout $workout, Request $request): JsonResponse
    {
        $deleted = $this->workoutService->delete($workout->id, $request->user()?->id);
        
        if (!$deleted) {
            return response()->json([
                'message' => 'Тренировка не найдена'
            ], 404);
        }
        
        return response()->json([
            'data' => null,
            'message' => 'Тренировка успешно удалена'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/workouts/start",
     *     summary="Запуск тренировки",
     *     description="Создает новую тренировку на основе плана и устанавливает время начала",
     *     tags={"Workouts"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"plan_id"},
     *             @OA\Property(property="plan_id", type="integer", example=1, description="ID плана для тренировки")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Тренировка успешно запущена",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Тренировка успешно запущена")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизован",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Пользователь не аутентифицирован")
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
    public function start(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => 'required|integer|exists:plans,id',
        ]);

        $userId = $request->user()?->id;
        if (!$userId) {
            return response()->json(['message' => 'Пользователь не аутентифицирован'], 401);
        }

        $workout = $this->workoutService->start($request->plan_id, $userId);
        
        return response()->json([
            'data' => new WorkoutResource($workout->load(['plan.cycle', 'user'])),
            'message' => 'Тренировка успешно запущена'
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/workouts/{workout}/finish",
     *     summary="Завершение тренировки",
     *     description="Завершает активную тренировку, устанавливая время окончания и рассчитывая продолжительность",
     *     tags={"Workouts"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="workout",
     *         in="path",
     *         description="ID тренировки",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Тренировка успешно завершена",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Тренировка успешно завершена"),
     *             @OA\Property(property="duration_minutes", type="integer", example=90)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизован",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Пользователь не аутентифицирован")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Тренировка не найдена",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Тренировка не найдена")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Тренировка уже завершена",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Тренировка уже завершена")
     *         )
     *     )
     * )
     */
    public function finish(Request $request, Workout $workout): JsonResponse
    {
        $userId = $request->user()?->id;
        if (!$userId) {
            return response()->json(['message' => 'Пользователь не аутентифицирован'], 401);
        }

        try {
            $workout = $this->workoutService->finish($workout->id, $userId);
            
            if (!$workout) {
                return response()->json([
                    'message' => 'Тренировка не найдена'
                ], 404);
            }
            
            return response()->json([
                'data' => new WorkoutResource($workout),
                'message' => 'Тренировка успешно завершена',
                'duration_minutes' => $workout->duration_minutes
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
