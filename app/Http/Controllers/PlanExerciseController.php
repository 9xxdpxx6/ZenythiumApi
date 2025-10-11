<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\PlanExerciseRequest;
use App\Http\Resources\PlanExerciseResource;
use App\Models\Plan;
use App\Models\PlanExercise;
use App\Services\PlanExerciseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PlanExerciseController extends Controller
{
    public function __construct(
        private readonly PlanExerciseService $planExerciseService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/plans/{plan}/exercises",
     *     summary="Получение упражнений плана",
     *     description="Возвращает список упражнений в конкретном плане тренировок",
     *     tags={"Plan Exercises"},
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
     *         description="Упражнения плана успешно получены",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="message", type="string", example="Упражнения плана успешно получены")
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
    public function index(Request $request, int $planId): JsonResponse
    {
        $planExercises = $this->planExerciseService->getByPlanId($planId, $request->user()?->id);
        
        if ($planExercises === null) {
            return response()->json([
                'message' => 'План не найден'
            ], 404);
        }
        
        return response()->json([
            'data' => PlanExerciseResource::collection($planExercises),
            'message' => 'Упражнения плана успешно получены'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/plans/{plan}/exercises",
     *     summary="Добавление упражнения в план",
     *     description="Добавляет упражнение в план тренировок",
     *     tags={"Plan Exercises"},
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
     *             required={"exercise_id"},
     *             @OA\Property(property="exercise_id", type="integer", example=1, description="ID упражнения"),
     *             @OA\Property(property="order", type="integer", example=1, description="Порядок упражнения в плане")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Упражнение успешно добавлено в план",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Упражнение успешно добавлено в план")
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
     *         description="План или упражнение не найдены",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="План или упражнение не найдены")
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
    public function store(PlanExerciseRequest $request, int $planId): JsonResponse
    {
        $data = $request->validated();
        $data['plan_id'] = $planId;
        
        $planExercise = $this->planExerciseService->create($data, $request->user()?->id);
        
        if (!$planExercise) {
            return response()->json([
                'message' => 'План или упражнение не найдены'
            ], 404);
        }
        
        return response()->json([
            'data' => new PlanExerciseResource($planExercise),
            'message' => 'Упражнение успешно добавлено в план'
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/plans/{plan}/exercises/{planExercise}",
     *     summary="Обновление упражнения в плане",
     *     description="Обновляет порядок упражнения в плане тренировок",
     *     tags={"Plan Exercises"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="plan",
     *         in="path",
     *         description="ID плана",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="planExercise",
     *         in="path",
     *         description="ID упражнения в плане",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="order", type="integer", example=2, description="Новый порядок упражнения в плане")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Упражнение в плане успешно обновлено",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Упражнение в плане успешно обновлено")
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
    public function update(PlanExerciseRequest $request, int $planId, int $planExerciseId): JsonResponse
    {
        $planExercise = $this->planExerciseService->update($planExerciseId, $request->validated(), $request->user()?->id, $planId);
        
        if (!$planExercise) {
            return response()->json([
                'message' => 'Упражнение в плане не найдено'
            ], 404);
        }
        
        return response()->json([
            'data' => new PlanExerciseResource($planExercise),
            'message' => 'Упражнение в плане успешно обновлено'
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/plans/{plan}/exercises/{planExercise}",
     *     summary="Удаление упражнения из плана",
     *     description="Удаляет упражнение из плана тренировок",
     *     tags={"Plan Exercises"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="plan",
     *         in="path",
     *         description="ID плана",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="planExercise",
     *         in="path",
     *         description="ID упражнения в плане",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Упражнение успешно удалено из плана",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="null", example=null),
     *             @OA\Property(property="message", type="string", example="Упражнение успешно удалено из плана")
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
    public function destroy(Request $request, int $planId, int $planExerciseId): JsonResponse
    {
        $deleted = $this->planExerciseService->delete($planExerciseId, $request->user()?->id, $planId);
        
        if (!$deleted) {
            return response()->json([
                'message' => 'Упражнение в плане не найдено'
            ], 404);
        }
        
        return response()->json([
            'data' => null,
            'message' => 'Упражнение успешно удалено из плана'
        ]);
    }
}
