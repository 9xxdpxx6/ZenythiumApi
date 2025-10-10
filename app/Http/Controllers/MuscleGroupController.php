<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\MuscleGroupRequest;
use App\Http\Resources\MuscleGroupResource;
use App\Models\MuscleGroup;
use App\Services\MuscleGroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MuscleGroupController extends Controller
{
    public function __construct(
        private readonly MuscleGroupService $muscleGroupService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/muscle-groups",
     *     summary="Получение списка групп мышц",
     *     description="Возвращает пагинированный список групп мышц с возможностью фильтрации. Доступно как аутентифицированным, так и неаутентифицированным пользователям",
     *     tags={"Muscle Groups"},
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
     *         description="Фильтр по названию группы мышц",
     *         required=false,
     *         @OA\Schema(type="string", example="грудь")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Группы мышц успешно получены",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="message", type="string", example="Группы мышц успешно получены"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->query();
        
        if ($request->user()) {
            $filters['user_id'] = $request->user()->id;
        }
        
        $muscleGroups = $this->muscleGroupService->getAll($filters);
        
        return response()->json([
            'data' => MuscleGroupResource::collection($muscleGroups->items()),
            'message' => 'Группы мышц успешно получены',
            'meta' => [
                'current_page' => $muscleGroups->currentPage(),
                'last_page' => $muscleGroups->lastPage(),
                'per_page' => $muscleGroups->perPage(),
                'total' => $muscleGroups->total(),
                'from' => $muscleGroups->firstItem(),
                'to' => $muscleGroups->lastItem(),
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/muscle-groups",
     *     summary="Создание новой группы мышц",
     *     description="Создает новую группу мышц. Доступно всем пользователям",
     *     tags={"Muscle Groups"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Грудь", description="Название группы мышц"),
     *             @OA\Property(property="description", type="string", example="Мышцы грудной клетки", description="Описание группы мышц")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Группа мышц успешно создана",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Группа мышц успешно создана")
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
    public function store(MuscleGroupRequest $request): JsonResponse
    {
        $muscleGroup = $this->muscleGroupService->create($request->validated());
        
        return response()->json([
            'data' => new MuscleGroupResource($muscleGroup),
            'message' => 'Группа мышц успешно создана'
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/muscle-groups/{muscleGroup}",
     *     summary="Получение конкретной группы мышц",
     *     description="Возвращает детальную информацию о группе мышц по ID. Доступно как аутентифицированным, так и неаутентифицированным пользователям",
     *     tags={"Muscle Groups"},
     *     @OA\Parameter(
     *         name="muscleGroup",
     *         in="path",
     *         description="ID группы мышц",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Группа мышц успешно получена",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Группа мышц успешно получена")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Группа мышц не найдена",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Группа мышц не найдена")
     *         )
     *     )
     * )
     */
    public function show(MuscleGroup $muscleGroup, Request $request): JsonResponse
    {
        $userId = $request->user() ? $request->user()->id : null;
        
        $muscleGroup = $this->muscleGroupService->getById($muscleGroup->id, $userId);
        
        return response()->json([
            'data' => new MuscleGroupResource($muscleGroup),
            'message' => 'Группа мышц успешно получена'
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/muscle-groups/{muscleGroup}",
     *     summary="Обновление группы мышц",
     *     description="Обновляет информацию о существующей группе мышц",
     *     tags={"Muscle Groups"},
     *     @OA\Parameter(
     *         name="muscleGroup",
     *         in="path",
     *         description="ID группы мышц",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Грудь", description="Название группы мышц"),
     *             @OA\Property(property="description", type="string", example="Мышцы грудной клетки", description="Описание группы мышц")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Группа мышц успешно обновлена",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Группа мышц успешно обновлена")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Группа мышц не найдена",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Группа мышц не найдена")
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
    public function update(MuscleGroupRequest $request, MuscleGroup $muscleGroup): JsonResponse
    {
        $muscleGroup = $this->muscleGroupService->update($muscleGroup->id, $request->validated());
        
        return response()->json([
            'data' => new MuscleGroupResource($muscleGroup),
            'message' => 'Группа мышц успешно обновлена'
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/muscle-groups/{muscleGroup}",
     *     summary="Удаление группы мышц",
     *     description="Удаляет группу мышц и все связанные с ней упражнения",
     *     tags={"Muscle Groups"},
     *     @OA\Parameter(
     *         name="muscleGroup",
     *         in="path",
     *         description="ID группы мышц",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Группа мышц успешно удалена",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="null", example=null),
     *             @OA\Property(property="message", type="string", example="Группа мышц успешно удалена")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Группа мышц не найдена",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Группа мышц не найдена")
     *         )
     *     )
     * )
     */
    public function destroy(MuscleGroup $muscleGroup): JsonResponse
    {
        $this->muscleGroupService->delete($muscleGroup->id);
        
        return response()->json([
            'data' => null,
            'message' => 'Группа мышц успешно удалена'
        ]);
    }
}
