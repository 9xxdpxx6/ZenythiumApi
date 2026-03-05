<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ExerciseRequest;
use App\Http\Resources\ExerciseResource;
use App\Services\ExerciseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="ExerciseResource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Жим лежа"),
 *     @OA\Property(property="description", type="string", example="Базовое упражнение для развития грудных мышц"),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="muscle_group", type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="Грудь")
 *     ),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z")
 * )
 */
final class ExerciseController extends Controller
{
    public function __construct(
        private readonly ExerciseService $exerciseService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/exercises",
     *     summary="Получение списка упражнений",
     *     description="Возвращает пагинированный список упражнений текущего пользователя с возможностью фильтрации",
     *     tags={"Exercises"},
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
     *         description="Количество элементов на странице (от 1 до 100, по умолчанию 100)",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100, default=100, example=100)
     *     ),
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="Фильтр по ID пользователя",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="is_active",
     *         in="query",
     *         description="Фильтр по статусу активности упражнений. Поддерживает значения: '1' или 'true' для активных, '0' или 'false' для неактивных. Пустое значение возвращает все упражнения.",
     *         required=false,
     *         @OA\Schema(type="string", enum={"true", "false", "1", "0"}, example="1")
     *     ),
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         description="Фильтр по дате создания (от). Форматы: YYYY-MM-DD, YYYY-MM-DD HH:MM:SS, ISO 8601",
     *         required=false,
     *         @OA\Schema(type="string", format="date-time", example="2024-01-01T00:00:00Z")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         description="Фильтр по дате создания (до). Форматы: YYYY-MM-DD, YYYY-MM-DD HH:MM:SS, ISO 8601",
     *         required=false,
     *         @OA\Schema(type="string", format="date-time", example="2024-01-31T23:59:59Z")
     *     ),
     *     @OA\Parameter(
     *         name="muscle_group_id",
     *         in="query",
     *         description="Фильтр по ID группы мышц",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Умный поиск по словам в названии и описании упражнения. Поисковая строка разбивается на слова, и находятся записи, содержащие все слова (в любом порядке). Например: 'жим лежа' найдет 'жим штанги лежа', 'лежа жим' и т.д. Игнорируются слова короче 2 символов.",
     *         required=false,
     *         @OA\Schema(type="string", example="жим лежа")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Поле для сортировки",
     *         required=false,
     *         @OA\Schema(type="string", enum={"id", "name", "muscle_group_id", "is_active", "created_at"}, example="name")
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         description="Порядок сортировки",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, example="asc")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Упражнения успешно получены",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ExerciseResource")),
     *             @OA\Property(property="message", type="string", example="Упражнения успешно получены"),
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
        
        $exercises = $this->exerciseService->getAll($filters);
        
        return response()->json([
            'data' => ExerciseResource::collection($exercises->items()),
            'message' => 'Упражнения успешно получены',
            'meta' => [
                'current_page' => $exercises->currentPage(),
                'last_page' => $exercises->lastPage(),
                'per_page' => $exercises->perPage(),
                'total' => $exercises->total(),
                'from' => $exercises->firstItem(),
                'to' => $exercises->lastItem(),
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/exercises",
     *     summary="Создание нового упражнения",
     *     description="Создает новое упражнение для текущего пользователя",
     *     tags={"Exercises"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","muscle_group_id"},
     *             @OA\Property(property="name", type="string", example="Жим лежа", description="Название упражнения"),
     *             @OA\Property(property="description", type="string", example="Базовое упражнение для груди", description="Описание упражнения"),
     *             @OA\Property(property="muscle_group_id", type="integer", example=1, description="ID группы мышц")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Упражнение успешно создано",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Жим лежа"),
     *                 @OA\Property(property="description", type="string", example="Базовое упражнение для груди"),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="muscle_group", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Грудь")
     *                 ),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z")
     *             ),
     *             @OA\Property(property="message", type="string", example="Упражнение успешно создано")
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
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="name", type="array", @OA\Items(type="string"), example={"Поле name обязательно"})
     *             )
     *         )
     *     )
     * )
     */
    public function store(ExerciseRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()?->id;
        
        $exercise = $this->exerciseService->create($data);
        
        return response()->json([
            'data' => new ExerciseResource($exercise->load('muscleGroup')),
            'message' => 'Упражнение успешно создано'
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/exercises/{exercise}",
     *     summary="Получение конкретного упражнения",
     *     description="Возвращает детальную информацию об упражнении по ID",
     *     tags={"Exercises"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="exercise",
     *         in="path",
     *         description="ID упражнения",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Упражнение успешно получено",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/ExerciseResource"),
     *             @OA\Property(property="message", type="string", example="Упражнение успешно получено")
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
     *         description="Упражнение не найдено",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Упражнение не найдено")
     *         )
     *     )
     * )
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $exercise = $this->exerciseService->getById($id, $request->user()?->id);
        
        if (!$exercise) {
            return response()->json([
                'message' => 'Упражнение не найдено'
            ], 404);
        }
        
        return response()->json([
            'data' => new ExerciseResource($exercise),
            'message' => 'Упражнение успешно получено'
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/exercises/{exercise}",
     *     summary="Обновление упражнения",
     *     description="Обновляет информацию о существующем упражнении",
     *     tags={"Exercises"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="exercise",
     *         in="path",
     *         description="ID упражнения",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Жим лежа", description="Название упражнения"),
     *             @OA\Property(property="description", type="string", example="Базовое упражнение для груди", description="Описание упражнения"),
     *             @OA\Property(property="muscle_group_id", type="integer", example=1, description="ID группы мышц")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Упражнение успешно обновлено",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Упражнение успешно обновлено")
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
     *         description="Упражнение не найдено",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Упражнение не найдено")
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
    public function update(ExerciseRequest $request, int $id): JsonResponse
    {
        $exercise = $this->exerciseService->update($id, $request->validated(), $request->user()?->id);
        
        if (!$exercise) {
            return response()->json([
                'message' => 'Упражнение не найдено'
            ], 404);
        }
        
        return response()->json([
            'data' => new ExerciseResource($exercise),
            'message' => 'Упражнение успешно обновлено'
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/exercises/{exercise}",
     *     summary="Удаление упражнения",
     *     description="Удаляет упражнение и все связанные с ним записи в планах",
     *     tags={"Exercises"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="exercise",
     *         in="path",
     *         description="ID упражнения",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Упражнение успешно удалено",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="null", example=null),
     *             @OA\Property(property="message", type="string", example="Упражнение успешно удалено")
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
     *         description="Упражнение не найдено",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Упражнение не найдено")
     *         )
     *     )
     * )
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        $deleted = $this->exerciseService->delete($id, $request->user()?->id);
        
        if (!$deleted) {
            return response()->json([
                'message' => 'Упражнение не найдено'
            ], 404);
        }
        
        return response()->json([
            'data' => null,
            'message' => 'Упражнение успешно удалено'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/exercises/install-base-pack",
     *     summary="Установка базового набора упражнений",
     *     description="Устанавливает стартовый набор из ~37 универсальных упражнений, покрывающих все основные группы мышц. Упражнения, которые уже существуют у пользователя (по совпадению названия и группы мышц), пропускаются. Можно вызывать повторно — дубликаты не создаются.",
     *     tags={"Exercises"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=201,
     *         description="Базовый набор упражнений установлен",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Базовый набор упражнений установлен"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="created", type="integer", example=37, description="Количество созданных упражнений"),
     *                 @OA\Property(property="skipped", type="integer", example=0, description="Количество пропущенных (уже существуют)"),
     *                 @OA\Property(property="total", type="integer", example=37, description="Общее количество упражнений в пакете")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Базовый набор уже был установлен ранее",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Базовый набор упражнений уже установлен"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="created", type="integer", example=0),
     *                 @OA\Property(property="skipped", type="integer", example=37),
     *                 @OA\Property(property="total", type="integer", example=37)
     *             )
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
     *         response=500,
     *         description="Ошибка сервера (группы мышц отсутствуют)",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Ошибка при установке базового набора упражнений")
     *         )
     *     )
     * )
     */
    public function installBasePack(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;

        if (!$userId) {
            return response()->json([
                'message' => 'Не авторизован'
            ], 401);
        }

        try {
            $result = $this->exerciseService->installBasePack($userId);

            if ($result['created'] === 0) {
                return response()->json([
                    'message' => 'Базовый набор упражнений уже установлен',
                    'data' => $result,
                ]);
            }

            return response()->json([
                'message' => 'Базовый набор упражнений установлен',
                'data' => $result,
            ], 201);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => 'Ошибка при установке базового набора упражнений',
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/exercises/base-pack-status",
     *     summary="Проверка статуса базового набора упражнений",
     *     description="Проверяет, установлен ли базовый набор упражнений у текущего пользователя. Пакет считается установленным, если есть хотя бы одно упражнение с source='base_pack'.",
     *     tags={"Exercises"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Статус базового набора",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="installed", type="boolean", example=false, description="Установлен ли базовый набор"),
     *                 @OA\Property(property="installed_count", type="integer", example=0, description="Количество установленных упражнений из пакета"),
     *                 @OA\Property(property="pack_size", type="integer", example=37, description="Общее количество упражнений в пакете")
     *             ),
     *             @OA\Property(property="message", type="string", example="Статус базового набора")
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
    public function basePackStatus(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;

        if (!$userId) {
            return response()->json([
                'message' => 'Не авторизован'
            ], 401);
        }

        $status = $this->exerciseService->getBasePackStatus($userId);

        return response()->json([
            'data' => $status,
            'message' => 'Статус базового набора',
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/exercises/uninstall-base-pack",
     *     summary="Откат установки базового набора упражнений",
     *     description="Удаляет все упражнения, установленные из базового пакета (source='base_pack'). Упражнения, которые уже использовались в тренировках (есть записи в workout_sets), не удаляются, а деактивируются (is_active=false) и открепляются от пакета. Работает корректно даже если упражнения были переименованы.",
     *     tags={"Exercises"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Базовый набор упражнений удалён",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Базовый набор упражнений удалён"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="deleted", type="integer", example=35, description="Количество полностью удалённых упражнений"),
     *                 @OA\Property(property="deactivated", type="integer", example=2, description="Количество деактивированных (задействованы в тренировках)"),
     *                 @OA\Property(property="total_found", type="integer", example=37, description="Общее количество найденных упражнений из пакета")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Базовый набор не установлен",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Базовый набор упражнений не установлен")
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
     *         response=500,
     *         description="Ошибка сервера",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Ошибка при удалении базового набора упражнений")
     *         )
     *     )
     * )
     */
    public function uninstallBasePack(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;

        if (!$userId) {
            return response()->json([
                'message' => 'Не авторизован'
            ], 401);
        }

        try {
            $result = $this->exerciseService->uninstallBasePack($userId);

            if ($result['total_found'] === 0) {
                return response()->json([
                    'message' => 'Базовый набор упражнений не установлен',
                ], 404);
            }

            return response()->json([
                'message' => 'Базовый набор упражнений удалён',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ошибка при удалении базового набора упражнений',
            ], 500);
        }
    }
}
