<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\WorkoutRequest;
use App\Http\Resources\WorkoutResource;
use App\Models\Workout;
use App\Services\WorkoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="WorkoutResource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="started_at", type="string", format="date-time", example="2024-01-01T10:00:00.000000Z"),
 *     @OA\Property(property="finished_at", type="string", format="date-time", example="2024-01-01T11:30:00.000000Z"),
 *     @OA\Property(property="duration_minutes", type="integer", example=90),
 *     @OA\Property(property="exercise_count", type="integer", example=5),
 *     @OA\Property(property="total_volume", type="number", format="float", example=2500.5),
 *     @OA\Property(property="plan", type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="Программа на массу")
 *     ),
 *     @OA\Property(property="user", type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="Иван Петров")
 *     ),
 *     @OA\Property(property="exercises", type="array", @OA\Items(
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="order", type="integer", example=1),
 *         @OA\Property(property="exercise", type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="name", type="string", example="Жим лежа"),
 *             @OA\Property(property="description", type="string", example="Базовое упражнение для груди"),
 *             @OA\Property(property="muscle_group", type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="name", type="string", example="Грудь")
 *             )
 *         ),
 *         @OA\Property(property="history", type="array", @OA\Items(
 *             type="object",
 *             @OA\Property(property="workout_id", type="integer", example=1),
 *             @OA\Property(property="workout_date", type="string", format="date-time", nullable=true, example="2024-01-01T11:30:00.000000Z", description="Дата завершения тренировки (finished_at). null = незавершенная тренировка, дата = завершенная тренировка"),
 *             @OA\Property(property="sets", type="array", @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="weight", type="number", format="float", example=80.5),
 *                 @OA\Property(property="reps", type="integer", example=10)
 *             ))
 *         ))
 *     )),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z")
 * )
 */
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
     *         name="search",
     *         in="query",
     *         description="Поиск по названию плана или имени пользователя",
     *         required=false,
     *         @OA\Schema(type="string", example="Программа на массу")
     *     ),
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="Фильтр по ID пользователя",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="plan_id",
     *         in="query",
     *         description="Фильтр по ID плана",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="started_at_from",
     *         in="query",
     *         description="Фильтр по дате начала тренировки (от). Форматы: YYYY-MM-DD, YYYY-MM-DD HH:MM:SS, ISO 8601",
     *         required=false,
     *         @OA\Schema(type="string", format="date-time", example="2024-01-01T00:00:00Z")
     *     ),
     *     @OA\Parameter(
     *         name="started_at_to",
     *         in="query",
     *         description="Фильтр по дате начала тренировки (до). Форматы: YYYY-MM-DD, YYYY-MM-DD HH:MM:SS, ISO 8601",
     *         required=false,
     *         @OA\Schema(type="string", format="date-time", example="2024-01-31T23:59:59Z")
     *     ),
     *     @OA\Parameter(
     *         name="finished_at_from",
     *         in="query",
     *         description="Фильтр по дате окончания тренировки (от). Форматы: YYYY-MM-DD, YYYY-MM-DD HH:MM:SS, ISO 8601",
     *         required=false,
     *         @OA\Schema(type="string", format="date-time", example="2024-01-01T00:00:00Z")
     *     ),
     *     @OA\Parameter(
     *         name="finished_at_to",
     *         in="query",
     *         description="Фильтр по дате окончания тренировки (до). Форматы: YYYY-MM-DD, YYYY-MM-DD HH:MM:SS, ISO 8601",
     *         required=false,
     *         @OA\Schema(type="string", format="date-time", example="2024-01-31T23:59:59Z")
     *     ),
     *     @OA\Parameter(
     *         name="completed",
     *         in="query",
     *         description="Фильтр по статусу завершения тренировки",
     *         required=false,
     *         @OA\Schema(type="string", enum={"true", "false", "1", "0"}, example="true")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Поле для сортировки",
     *         required=false,
     *         @OA\Schema(type="string", enum={"id", "started_at", "finished_at", "created_at", "updated_at"}, example="started_at")
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         description="Порядок сортировки",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, example="desc")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Тренировки успешно получены",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/WorkoutResource")),
     *             @OA\Property(property="message", type="string", example="Тренировки успешно получены"),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=3),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=45),
     *                 @OA\Property(property="from", type="integer", example=1),
     *                 @OA\Property(property="to", type="integer", example=15)
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
     *     description="Возвращает детальную информацию о тренировке по ID, включая список упражнений из плана с историей их выполнения. История содержит: текущую тренировку (workout_date=null если незавершена) + последние 3 завершенные тренировки (workout_date=finished_at)",
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
     *             @OA\Property(property="data", ref="#/components/schemas/WorkoutResource"),
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
    public function show(int $id, Request $request): JsonResponse
    {
        $workout = $this->workoutService->getById($id, $request->user()?->id);
        
        if (!$workout) {
            return response()->json([
                'message' => 'Тренировка не найдена'
            ], 404);
        }
        
        // Загружаем дополнительные связи для отображения упражнений с историей
        $workout->load([
            'plan.planExercises.exercise.muscleGroup',
            'plan.planExercises.workoutSets' => function ($query) use ($request, $workout) {
                $query->whereHas('workout', function ($q) use ($request, $workout) {
                    $q->where('user_id', $request->user()?->id)
                      ->where(function ($subQ) use ($workout) {
                          $subQ->whereNotNull('finished_at')
                               ->orWhere('id', $workout->id); // Включаем текущую тренировку
                      });
                })->with('workout:id,finished_at')->orderBy('created_at', 'desc');
            }
        ]);
        
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
    public function update(WorkoutRequest $request, int $id): JsonResponse
    {
        $workout = $this->workoutService->update($id, $request->validated(), $request->user()?->id);
        
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
    public function destroy(int $id, Request $request): JsonResponse
    {
        $deleted = $this->workoutService->delete($id, $request->user()?->id);
        
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
     *     description="Создает новую тренировку на основе плана и устанавливает время начала. Если plan_id не передан, автоматически определяет план на основе активного цикла и прогресса тренировок. Логика выбора: выбирается план с наименьшим количеством завершенных тренировок, при равенстве - первый по порядку.",
     *     tags={"Workouts"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="plan_id", type="integer", example=1, description="ID плана для тренировки (необязательно, если не указан - определяется автоматически)")
     *         ),
     *         description="Тело запроса может быть пустым для автоматического определения плана или содержать plan_id для указания конкретного плана"
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Тренировка успешно запущена",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/WorkoutResource"),
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
     *         response=404,
     *         description="Активный цикл не найден",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Не найден активный цикл с планами")
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
            'plan_id' => 'nullable|integer|exists:plans,id',
        ]);

        $userId = $request->user()?->id;
        if (!$userId) {
            return response()->json(['message' => 'Пользователь не аутентифицирован'], 401);
        }

        $planId = $request->plan_id;
        
        // Если plan_id не передан, автоматически определяем план
        if (!$planId) {
            $planId = $this->workoutService->determineNextPlan($userId);
            
            if (!$planId) {
                return response()->json([
                    'message' => 'Не найден активный цикл с планами'
                ], 404);
            }
        }

        $workout = $this->workoutService->start($planId, $userId);
        
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
    public function finish(Request $request, int $id): JsonResponse
    {
        $userId = $request->user()?->id;
        if (!$userId) {
            return response()->json(['message' => 'Пользователь не аутентифицирован'], 401);
        }

        try {
            $workout = $this->workoutService->finish($id, $userId);
            
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
