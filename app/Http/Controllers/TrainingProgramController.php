<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\TrainingProgramResource;
use App\Http\Resources\TrainingProgramDetailResource;
use App\Models\TrainingProgramInstallation;
use App\Services\TrainingProgramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Training Programs",
 *     description="Управление программами тренировок"
 * )
 */
final class TrainingProgramController extends Controller
{
    public function __construct(
        private readonly TrainingProgramService $trainingProgramService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/training-programs",
     *     summary="Получение списка программ тренировок",
     *     description="Возвращает пагинированный список программ тренировок из каталога",
     *     tags={"Training Programs"},
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
     *         description="Поиск по названию и описанию",
     *         required=false,
     *         @OA\Schema(type="string", example="новичок")
     *     ),
     *     @OA\Parameter(
     *         name="is_active",
     *         in="query",
     *         description="Фильтр по активности программы. Принимает значения: '1'/'true' для активных, '0'/'false' для неактивных. Если не указан, возвращаются все программы.",
     *         required=false,
     *         @OA\Schema(type="string", enum={"0", "1", "true", "false"}, example="1")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Программы успешно получены",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/TrainingProgramResource")),
     *             @OA\Property(property="message", type="string", example="Программы успешно получены"),
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
        
        $programs = $this->trainingProgramService->getAll($filters);
        
        // Предзагружаем установки пользователя одним запросом для оптимизации
        $userInstallations = [];
        if ($request->user()) {
            $userInstallations = TrainingProgramInstallation::where('user_id', $request->user()->id)
                ->pluck('training_program_id')
                ->toArray();
        }
        
        // Передаем установки в request для использования в Resource
        $request->merge(['_user_installations' => $userInstallations]);
        
        return response()->json([
            'data' => TrainingProgramResource::collection($programs->items()),
            'message' => 'Программы успешно получены',
            'meta' => [
                'current_page' => $programs->currentPage(),
                'last_page' => $programs->lastPage(),
                'per_page' => $programs->perPage(),
                'total' => $programs->total(),
                'from' => $programs->firstItem(),
                'to' => $programs->lastItem(),
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/training-programs/{id}",
     *     summary="Получение конкретной программы тренировок",
     *     description="Возвращает детальную информацию о программе тренировок по ID",
     *     tags={"Training Programs"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID программы",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Программа успешно получена",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/TrainingProgramDetailResource"),
     *             @OA\Property(property="message", type="string", example="Программа успешно получена")
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
     *         description="Программа не найдена",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Программа не найдена")
     *         )
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        $program = $this->trainingProgramService->getById($id);
        
        if (!$program) {
            return response()->json([
                'message' => 'Программа не найдена'
            ], 404);
        }
        
        return response()->json([
            'data' => new TrainingProgramDetailResource($program),
            'message' => 'Программа успешно получена'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/training-programs/{id}/install",
     *     summary="Установка программы тренировок",
     *     description="Устанавливает программу тренировок для текущего пользователя. Создает упражнения, планы и цикл. Проверяет конфликты названий и разрешает их автоматически.",
     *     tags={"Training Programs"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID программы",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Программа успешно установлена",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Программа успешно установлена"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="install_id", type="integer", example=1),
     *                 @OA\Property(property="cycle_id", type="integer", example=5),
     *                 @OA\Property(property="plans_count", type="integer", example=3),
     *                 @OA\Property(property="exercises_count", type="integer", example=12)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка установки",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Программа уже установлена")
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
     *         description="Программа не найдена",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Программа не найдена")
     *         )
     *     )
     * )
     */
    public function install(int $id, Request $request): JsonResponse
    {
        try {
            $userId = $request->user()?->id;
            
            if (!$userId) {
                return response()->json([
                    'message' => 'Не авторизован'
                ], 401);
            }

            $result = $this->trainingProgramService->install($id, $userId);

            return response()->json([
                'message' => 'Программа успешно установлена',
                'data' => [
                    'install_id' => $result['install']->id,
                    'cycle_id' => $result['cycle']?->id,
                    'plans_count' => $result['plans']->count(),
                    'exercises_count' => $result['exercises']->count(),
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/training-programs/{id}/uninstall",
     *     summary="Отмена установки программы тренировок",
     *     description="Отменяет установку программы тренировок. Удаляет только элементы, созданные при установке программы, не трогая пользовательские элементы.",
     *     tags={"Training Programs"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID установки программы (training_program_installation_id), который можно получить из ответа после установки программы",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Установка программы успешно отменена",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Установка программы успешно отменена")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка отмены",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Установка программы не найдена")
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
    public function uninstall(int $id, Request $request): JsonResponse
    {
        try {
            $userId = $request->user()?->id;
            
            if (!$userId) {
                return response()->json([
                    'message' => 'Не авторизован'
                ], 401);
            }

            $this->trainingProgramService->uninstall($id, $userId);

            return response()->json([
                'message' => 'Установка программы успешно отменена'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
