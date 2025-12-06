<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\GoalStatus;
use App\Http\Requests\StoreGoalRequest;
use App\Http\Requests\UpdateGoalRequest;
use App\Http\Resources\GoalResource;
use App\Models\Goal;
use App\Services\GoalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Goals",
 *     description="Управление целями пользователя"
 * )
 */
final class GoalController extends Controller
{
    public function __construct(
        private readonly GoalService $goalService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/goals",
     *     summary="Получение списка целей",
     *     description="Возвращает список целей текущего пользователя с возможностью фильтрации по статусу",
     *     tags={"Goals"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Фильтр по статусу цели",
     *         required=false,
     *         @OA\Schema(type="string", enum={"active", "completed", "failed", "cancelled"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Список целей успешно получен",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/GoalResource")),
     *             @OA\Property(property="message", type="string", example="Список целей успешно получен")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;
        if (!$userId) {
            return response()->json(['message' => 'Пользователь не аутентифицирован'], 401);
        }

        $query = Goal::where('user_id', $userId)
            ->with(['exercise']);

        if ($request->has('status')) {
            $status = GoalStatus::tryFrom($request->input('status'));
            if ($status) {
                $query->where('status', $status);
            }
        }

        $goals = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => GoalResource::collection($goals),
            'message' => 'Список целей успешно получен',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/goals",
     *     summary="Создание цели",
     *     description="Создает новую цель для текущего пользователя",
     *     tags={"Goals"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", example="total_workouts"),
     *             @OA\Property(property="title", type="string", example="50 тренировок за 3 месяца"),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="target_value", type="number", example=50),
     *             @OA\Property(property="start_date", type="string", format="date", example="2024-01-01"),
     *             @OA\Property(property="end_date", type="string", format="date", nullable=true, example="2024-03-31"),
     *             @OA\Property(property="exercise_id", type="integer", nullable=true, example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Цель успешно создана",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/GoalResource"),
     *             @OA\Property(property="message", type="string", example="Цель успешно создана")
     *         )
     *     )
     * )
     */
    public function store(StoreGoalRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()?->id;
        $data['status'] = GoalStatus::ACTIVE;

        $goal = Goal::create($data);

        // Обновляем прогресс сразу после создания
        $this->goalService->updateProgress($goal);

        return response()->json([
            'data' => new GoalResource($goal->load('exercise')),
            'message' => 'Цель успешно создана',
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/goals/{id}",
     *     summary="Получение цели",
     *     description="Возвращает детали цели по ID",
     *     tags={"Goals"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Цель успешно получена",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/GoalResource"),
     *             @OA\Property(property="message", type="string", example="Цель успешно получена")
     *         )
     *     )
     * )
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $userId = $request->user()?->id;
        if (!$userId) {
            return response()->json(['message' => 'Пользователь не аутентифицирован'], 401);
        }

        $goal = Goal::where('user_id', $userId)
            ->with(['exercise'])
            ->find($id);

        if (!$goal) {
            return response()->json(['message' => 'Цель не найдена'], 404);
        }

        return response()->json([
            'data' => new GoalResource($goal),
            'message' => 'Цель успешно получена',
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/goals/{id}",
     *     summary="Обновление цели",
     *     description="Обновляет цель по ID",
     *     tags={"Goals"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Цель успешно обновлена",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/GoalResource"),
     *             @OA\Property(property="message", type="string", example="Цель успешно обновлена")
     *         )
     *     )
     * )
     */
    public function update(int $id, UpdateGoalRequest $request): JsonResponse
    {
        $userId = $request->user()?->id;
        if (!$userId) {
            return response()->json(['message' => 'Пользователь не аутентифицирован'], 401);
        }

        $goal = Goal::where('user_id', $userId)->find($id);

        if (!$goal) {
            return response()->json(['message' => 'Цель не найдена'], 404);
        }

        $data = $request->validated();

        // Если меняется статус на cancelled, устанавливаем cancelled_at
        if (isset($data['status']) && $data['status'] === GoalStatus::CANCELLED) {
            $data['cancelled_at'] = now();
        }

        $goal->update($data);

        // Если цель активна, обновляем прогресс
        if ($goal->isActive()) {
            $this->goalService->updateProgress($goal);
        }

        return response()->json([
            'data' => new GoalResource($goal->load('exercise')),
            'message' => 'Цель успешно обновлена',
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/goals/{id}",
     *     summary="Удаление цели",
     *     description="Удаляет или отменяет цель по ID",
     *     tags={"Goals"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Цель успешно удалена",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Цель успешно удалена")
     *         )
     *     )
     * )
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        $userId = $request->user()?->id;
        if (!$userId) {
            return response()->json(['message' => 'Пользователь не аутентифицирован'], 401);
        }

        $goal = Goal::where('user_id', $userId)->find($id);

        if (!$goal) {
            return response()->json(['message' => 'Цель не найдена'], 404);
        }

        // Если цель активна, помечаем как отмененную, иначе удаляем
        if ($goal->isActive()) {
            $goal->status = GoalStatus::CANCELLED;
            $goal->cancelled_at = now();
            $goal->save();
        } else {
            $goal->delete();
        }

        return response()->json([
            'message' => 'Цель успешно удалена',
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/goals/statistics",
     *     summary="Статистика достижений",
     *     description="Возвращает статистику достижений пользователя",
     *     tags={"Goals"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Статистика успешно получена",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Статистика успешно получена")
     *         )
     *     )
     * )
     */
    public function statistics(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;
        if (!$userId) {
            return response()->json(['message' => 'Пользователь не аутентифицирован'], 401);
        }

        $stats = $this->goalService->getStatistics($userId);

        return response()->json([
            'data' => $stats,
            'message' => 'Статистика успешно получена',
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/goals/completed",
     *     summary="Достигнутые цели",
     *     description="Возвращает список достигнутых целей",
     *     tags={"Goals"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Список достигнутых целей успешно получен",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/GoalResource")),
     *             @OA\Property(property="message", type="string", example="Список достигнутых целей успешно получен")
     *         )
     *     )
     * )
     */
    public function completed(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;
        if (!$userId) {
            return response()->json(['message' => 'Пользователь не аутентифицирован'], 401);
        }

        $goals = Goal::where('user_id', $userId)
            ->where('status', GoalStatus::COMPLETED)
            ->with(['exercise'])
            ->orderBy('completed_at', 'desc')
            ->get();

        return response()->json([
            'data' => GoalResource::collection($goals),
            'message' => 'Список достигнутых целей успешно получен',
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/goals/failed",
     *     summary="Проваленные цели",
     *     description="Возвращает список проваленных целей",
     *     tags={"Goals"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Список проваленных целей успешно получен",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/GoalResource")),
     *             @OA\Property(property="message", type="string", example="Список проваленных целей успешно получен")
     *         )
     *     )
     * )
     */
    public function failed(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;
        if (!$userId) {
            return response()->json(['message' => 'Пользователь не аутентифицирован'], 401);
        }

        $goals = Goal::where('user_id', $userId)
            ->where('status', GoalStatus::FAILED)
            ->with(['exercise'])
            ->orderBy('end_date', 'desc')
            ->get();

        return response()->json([
            'data' => GoalResource::collection($goals),
            'message' => 'Список проваленных целей успешно получен',
        ]);
    }
}
