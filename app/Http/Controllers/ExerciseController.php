<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ExerciseRequest;
use App\Http\Resources\ExerciseResource;
use App\Models\Exercise;
use App\Services\ExerciseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
     *         description="Количество элементов на странице",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Parameter(
     *         name="muscle_group_id",
     *         in="query",
     *         description="Фильтр по ID группы мышц",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="Фильтр по названию упражнения",
     *         required=false,
     *         @OA\Schema(type="string", example="жим")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Упражнения успешно получены",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="message", type="string", example="Упражнения успешно получены"),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=2),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=25)
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
     *                 @OA\Property(property="muscle_group", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Грудь")
     *                 ),
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Иван Петров")
     *                 )
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
     *             @OA\Property(property="data", type="object"),
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
}
