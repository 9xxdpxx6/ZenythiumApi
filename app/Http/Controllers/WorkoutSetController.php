<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\WorkoutSetRequest;
use App\Http\Resources\WorkoutSetResource;
use App\Models\WorkoutSet;
use App\Services\WorkoutSetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="WorkoutSetResource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="workout_id", type="integer", example=1),
 *     @OA\Property(property="plan_exercise_id", type="integer", example=1),
 *     @OA\Property(property="weight", type="number", format="float", example=80.0),
 *     @OA\Property(property="reps", type="integer", example=10),
 *     @OA\Property(property="workout", type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="started_at", type="string", format="date-time", example="2024-01-01T10:00:00.000000Z"),
 *         @OA\Property(property="finished_at", type="string", format="date-time", example="2024-01-01T11:30:00.000000Z"),
 *         @OA\Property(property="duration_minutes", type="integer", example=90),
 *         @OA\Property(property="exercise_count", type="integer", example=5),
 *         @OA\Property(property="total_volume", type="number", format="float", example=2500.5),
 *         @OA\Property(property="plan", type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="name", type="string", example="Программа на массу")
 *         ),
 *         @OA\Property(property="user", type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="name", type="string", example="Иван Петров")
 *         )
 *     ),
 *     @OA\Property(property="plan_exercise", type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="order", type="integer", example=1),
 *         @OA\Property(property="exercise", type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="name", type="string", example="Жим лежа"),
 *             @OA\Property(property="description", type="string", example="Базовое упражнение")
 *         )
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z")
 * )
 */
final class WorkoutSetController extends Controller
{
    public function __construct(
        private readonly WorkoutSetService $workoutSetService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/workout-sets",
     *     summary="Получение списка подходов",
     *     description="Возвращает пагинированный список подходов текущего пользователя с возможностью фильтрации",
     *     tags={"Workout Sets"},
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
     *         name="workout_id",
     *         in="query",
     *         description="Фильтр по ID тренировки",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="plan_exercise_id",
     *         in="query",
     *         description="Фильтр по ID упражнения в плане",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Умный поиск по словам в названии плана, упражнения или имени пользователя. Поисковая строка разбивается на слова, и находятся записи, содержащие все слова (в любом порядке). Например: 'жим лежа' найдет 'жим штанги лежа', 'лежа жим' и т.д. Игнорируются слова короче 2 символов.",
     *         required=false,
     *         @OA\Schema(type="string", example="жим лежа")
     *     ),
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="Фильтр по ID пользователя",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="weight_from",
     *         in="query",
     *         description="Фильтр по минимальному весу",
     *         required=false,
     *         @OA\Schema(type="number", format="float", example=50.0)
     *     ),
     *     @OA\Parameter(
     *         name="weight_to",
     *         in="query",
     *         description="Фильтр по максимальному весу",
     *         required=false,
     *         @OA\Schema(type="number", format="float", example=100.0)
     *     ),
     *     @OA\Parameter(
     *         name="reps_from",
     *         in="query",
     *         description="Фильтр по минимальному количеству повторений",
     *         required=false,
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Parameter(
     *         name="reps_to",
     *         in="query",
     *         description="Фильтр по максимальному количеству повторений",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Parameter(
     *         name="reps_min",
     *         in="query",
     *         description="Фильтр по минимальному количеству повторений (альтернативный параметр)",
     *         required=false,
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Parameter(
     *         name="reps_max",
     *         in="query",
     *         description="Фильтр по максимальному количеству повторений (альтернативный параметр)",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Поле для сортировки",
     *         required=false,
     *         @OA\Schema(type="string", enum={"id", "weight", "reps", "created_at"}, example="created_at")
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
     *         description="Подходы успешно получены",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/WorkoutSetResource")),
     *             @OA\Property(property="message", type="string", example="Подходы успешно получены"),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=2),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=25),
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
        
        $workoutSets = $this->workoutSetService->getAll($filters);
        
        return response()->json([
            'data' => WorkoutSetResource::collection($workoutSets->items()),
            'message' => 'Подходы успешно получены',
            'meta' => [
                'current_page' => $workoutSets->currentPage(),
                'last_page' => $workoutSets->lastPage(),
                'per_page' => $workoutSets->perPage(),
                'total' => $workoutSets->total(),
                'from' => $workoutSets->firstItem(),
                'to' => $workoutSets->lastItem(),
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/workout-sets",
     *     summary="Создание нового подхода",
     *     description="Создает новый подход для упражнения в тренировке",
     *     tags={"Workout Sets"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"workout_id","plan_exercise_id"},
     *             @OA\Property(property="workout_id", type="integer", example=1, description="ID тренировки"),
     *             @OA\Property(property="plan_exercise_id", type="integer", example=1, description="ID упражнения в плане"),
     *             @OA\Property(property="weight", type="number", format="float", example=80.0, description="Вес в килограммах"),
     *             @OA\Property(property="reps", type="integer", example=10, description="Количество повторений")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Подход успешно создан",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/WorkoutSetResource"),
     *             @OA\Property(property="message", type="string", example="Подход успешно создан")
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
    public function store(WorkoutSetRequest $request): JsonResponse
    {
        $data = $request->validated();
        
        $workoutSet = $this->workoutSetService->create($data);
        
        return response()->json([
            'data' => new WorkoutSetResource($workoutSet->load(['workout.plan.cycle', 'workout.user', 'planExercise.exercise'])),
            'message' => 'Подход успешно создан'
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/workout-sets/{workout_set}",
     *     summary="Получение конкретного подхода",
     *     description="Возвращает детальную информацию о подходе по ID",
     *     tags={"Workout Sets"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="workout_set",
     *         in="path",
     *         description="ID подхода",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Подход успешно получен",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/WorkoutSetResource"),
     *             @OA\Property(property="message", type="string", example="Подход успешно получен")
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
     *         description="Подход не найден",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Подход не найден")
     *         )
     *     )
     * )
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $workoutSet = $this->workoutSetService->getById($id, $request->user()?->id);
        
        if (!$workoutSet) {
            return response()->json([
                'message' => 'Подход не найден'
            ], 404);
        }
        
        return response()->json([
            'data' => new WorkoutSetResource($workoutSet->load(['workout.plan', 'workout.user', 'planExercise.exercise'])),
            'message' => 'Подход успешно получен'
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/workout-sets/{workout_set}",
     *     summary="Обновление подхода",
     *     description="Обновляет информацию о существующем подходе",
     *     tags={"Workout Sets"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="workout_set",
     *         in="path",
     *         description="ID подхода",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="weight", type="number", format="float", example=80.0, description="Вес в килограммах"),
     *             @OA\Property(property="reps", type="integer", example=10, description="Количество повторений")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Подход успешно обновлен",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/WorkoutSetResource"),
     *             @OA\Property(property="message", type="string", example="Подход успешно обновлен")
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
     *         description="Подход не найден",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Подход не найден")
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
    public function update(WorkoutSetRequest $request, int $id): JsonResponse
    {
        $workoutSet = $this->workoutSetService->update($id, $request->validated(), $request->user()?->id);
        
        if (!$workoutSet) {
            return response()->json([
                'message' => 'Подход не найден'
            ], 404);
        }
        
        return response()->json([
            'data' => new WorkoutSetResource($workoutSet->load(['workout.plan', 'workout.user', 'planExercise.exercise'])),
            'message' => 'Подход успешно обновлен'
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/workout-sets/{workout_set}",
     *     summary="Удаление подхода",
     *     description="Удаляет подход из тренировки",
     *     tags={"Workout Sets"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="workout_set",
     *         in="path",
     *         description="ID подхода",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Подход успешно удален",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="null", example=null),
     *             @OA\Property(property="message", type="string", example="Подход успешно удален")
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
     *         description="Подход не найден",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Подход не найден")
     *         )
     *     )
     * )
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        $deleted = $this->workoutSetService->delete($id, $request->user()?->id);
        
        if (!$deleted) {
            return response()->json([
                'message' => 'Подход не найден'
            ], 404);
        }
        
        return response()->json([
            'data' => null,
            'message' => 'Подход успешно удален'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/workout-sets/workout/{workoutId}",
     *     summary="Получение подходов по тренировке",
     *     description="Возвращает все подходы для конкретной тренировки",
     *     tags={"Workout Sets"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="workoutId",
     *         in="path",
     *         description="ID тренировки",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Подходы тренировки успешно получены",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/WorkoutSetResource")),
     *             @OA\Property(property="message", type="string", example="Подходы тренировки успешно получены")
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
    public function getByWorkout(Request $request, int $workoutId): JsonResponse
    {
        $workoutSets = $this->workoutSetService->getByWorkoutId($workoutId, $request->user()?->id);
        
        return response()->json([
            'data' => WorkoutSetResource::collection($workoutSets),
            'message' => 'Подходы тренировки успешно получены'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/workout-sets/plan-exercise/{planExerciseId}",
     *     summary="Получение подходов по упражнению в плане",
     *     description="Возвращает все подходы для конкретного упражнения в плане",
     *     tags={"Workout Sets"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="planExerciseId",
     *         in="path",
     *         description="ID упражнения в плане",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Подходы упражнения успешно получены",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/WorkoutSetResource")),
     *             @OA\Property(property="message", type="string", example="Подходы упражнения успешно получены")
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
     *         description="Упражнение в плане не найдено",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Упражнение в плане не найдено")
     *         )
     *     )
     * )
     */
    public function getByPlanExercise(Request $request, int $planExerciseId): JsonResponse
    {
        $workoutSets = $this->workoutSetService->getByPlanExerciseId($planExerciseId, $request->user()?->id);
        
        return response()->json([
            'data' => WorkoutSetResource::collection($workoutSets),
            'message' => 'Подходы упражнения успешно получены'
        ]);
    }
}
