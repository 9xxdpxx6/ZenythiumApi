<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ImportSharedCycleRequest;
use App\Http\Resources\SharedCycleResource;
use App\Models\SharedCycle;
use App\Services\CycleImportService;
use App\Services\CycleShareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Shared Cycles",
 *     description="Управление расшаренными циклами тренировок"
 * )
 */
final class SharedCycleController extends Controller
{
    public function __construct(
        private readonly CycleShareService $shareService,
        private readonly CycleImportService $importService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/shared-cycles/{shareId}",
     *     summary="Получение данных расшаренного цикла",
     *     description="Возвращает данные расшаренного цикла для предпросмотра перед импортом. Только для авторизованных пользователей.",
     *     tags={"Shared Cycles"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="shareId",
     *         in="path",
     *         description="UUID ссылки для расшаривания",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Данные цикла успешно получены",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/SharedCycleResource"),
     *             @OA\Property(property="message", type="string", example="Данные цикла успешно получены")
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
     *         description="Расшаренный цикл не найден",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Расшаренный цикл не найден")
     *         )
     *     ),
     *     @OA\Response(
     *         response=410,
     *         description="Ссылка истекла или деактивирована",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Ссылка истекла или деактивирована")
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
    public function show(string $shareId, Request $request): JsonResponse
    {
        $userId = $request->user()?->id;

        if (!$userId) {
            return response()->json([
                'message' => 'Не авторизован'
            ], 401);
        }

        // Валидация UUID формата
        if (!\Illuminate\Support\Str::isUuid($shareId)) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => [
                    'shareId' => ['Идентификатор ссылки должен быть корректным UUID.']
                ]
            ], 422);
        }

        try {
            $sharedCycle = $this->shareService->getSharedCycle($shareId);

            if (!$sharedCycle) {
                // Проверяем, существует ли вообще shared_cycle (может быть истекла или неактивна)
                try {
                    $sharedCycleExists = SharedCycle::where('share_id', $shareId)->exists();
                    
                    if ($sharedCycleExists) {
                        return response()->json([
                            'message' => 'Ссылка истекла или деактивирована'
                        ], 410);
                    }
                } catch (\Illuminate\Database\QueryException $e) {
                    Log::error('Database error checking shared cycle', [
                        'share_id' => $shareId,
                        'error' => $e->getMessage(),
                    ]);
                    return response()->json([
                        'message' => 'Ошибка базы данных. Пожалуйста, обратитесь к администратору.'
                    ], 500);
                }

                return response()->json([
                    'message' => 'Расшаренный цикл не найден'
                ], 404);
            }

            // Инкрементируем счетчик просмотров
            $this->shareService->incrementViewCount($shareId);

            // Логируем просмотр
            Log::info('Shared cycle viewed', [
                'share_id' => $shareId,
                'user_id' => $userId,
            ]);

            return response()->json([
                'data' => new SharedCycleResource($sharedCycle),
                'message' => 'Данные цикла успешно получены'
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Ошибки базы данных - не показываем детали пользователю
            Log::error('Database error viewing shared cycle', [
                'share_id' => $shareId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'sql' => $e->getSql() ?? null,
                'bindings' => $e->getBindings() ?? null,
            ]);

            return response()->json([
                'message' => 'Ошибка базы данных. Пожалуйста, обратитесь к администратору.'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Error viewing shared cycle', [
                'share_id' => $shareId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Ошибка при загрузке данных цикла. Пожалуйста, попробуйте позже.'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/shared-cycles/{shareId}/import",
     *     summary="Импорт расшаренного цикла",
     *     description="Импортирует расшаренный цикл тренировок для текущего пользователя. Создает новый цикл с копией всех планов и упражнений.",
     *     tags={"Shared Cycles"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="shareId",
     *         in="path",
     *         description="UUID ссылки для расшаривания",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Цикл успешно импортирован",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Цикл успешно импортирован"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="cycle_id", type="integer", example=5),
     *                 @OA\Property(property="plans_count", type="integer", example=3),
     *                 @OA\Property(property="exercises_count", type="integer", example=12)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка импорта (попытка импорта своей программы)",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Нельзя импортировать свою собственную программу")
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
     *         description="Расшаренный цикл не найден",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Расшаренный цикл не найден или недоступен")
     *         )
     *     ),
     *     @OA\Response(
     *         response=410,
     *         description="Ссылка истекла или деактивирована",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Ссылка истекла или деактивирована")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации (невалидный UUID или программа слишком большая)",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Ошибка валидации"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function import(string $shareId, ImportSharedCycleRequest $request): JsonResponse
    {
        $userId = $request->user()?->id;

        if (!$userId) {
            return response()->json([
                'message' => 'Не авторизован'
            ], 401);
        }

        try {
            $result = $this->importService->importFromShare($shareId, $userId);

            return response()->json([
                'message' => 'Цикл успешно импортирован',
                'data' => [
                    'cycle_id' => $result['cycle']->id,
                    'plans_count' => $result['plans']->count(),
                    'exercises_count' => $result['exercises']->count(),
                ]
            ], 201);
        } catch (\Illuminate\Database\QueryException $e) {
            // Ошибки базы данных - не показываем детали пользователю
            Log::error('Database error importing shared cycle', [
                'share_id' => $shareId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'sql' => $e->getSql() ?? null,
                'bindings' => $e->getBindings() ?? null,
            ]);

            return response()->json([
                'message' => 'Ошибка базы данных. Пожалуйста, обратитесь к администратору.'
            ], 500);
        } catch (\Exception $e) {
            $message = $e->getMessage();

            Log::error('Error importing shared cycle', [
                'share_id' => $shareId,
                'user_id' => $userId,
                'error' => $message,
                'trace' => $e->getTraceAsString(),
            ]);

            // Определяем код ошибки на основе сообщения
            if (str_contains($message, 'не найден') || str_contains($message, 'недоступен')) {
                // Проверяем, существует ли вообще shared_cycle
                try {
                    $sharedCycleExists = SharedCycle::where('share_id', $shareId)->exists();
                    
                    if ($sharedCycleExists) {
                        return response()->json([
                            'message' => 'Ссылка истекла или деактивирована'
                        ], 410);
                    }
                } catch (\Exception $dbError) {
                    // Если не можем проверить - просто возвращаем 404
                    Log::error('Error checking shared cycle existence', [
                        'share_id' => $shareId,
                        'error' => $dbError->getMessage(),
                    ]);
                }

                return response()->json([
                    'message' => 'Расшаренный цикл не найден или недоступен'
                ], 404);
            }

            if (str_contains($message, 'собственную программу')) {
                return response()->json([
                    'message' => $message
                ], 400);
            }

            if (str_contains($message, 'слишком большая')) {
                return response()->json([
                    'message' => $message
                ], 422);
            }

            // Для других ошибок возвращаем общее сообщение
            return response()->json([
                'message' => 'Ошибка при импорте цикла. Пожалуйста, попробуйте позже.'
            ], 500);
        }
    }
}
