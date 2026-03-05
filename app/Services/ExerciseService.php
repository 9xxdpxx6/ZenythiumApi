<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ExerciseSource;
use App\Support\BaseExercisePack;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Filters\ExerciseFilter;
use App\Traits\HasPagination;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class ExerciseService
{
    use HasPagination;
    /**
     * Получить все упражнения с фильтрацией и пагинацией
     * 
     * @param array $filters Массив фильтров для поиска упражнений
     * @param int|null $filters['user_id'] ID пользователя (обязательно для безопасности)
     * @param int|null $filters['muscle_group_id'] ID группы мышц
     * @param string|null $filters['search'] Поиск по названию и описанию упражнения (поиск по частичному совпадению)
     * @param int $filters['page'] Номер страницы (по умолчанию 1)
     * @param int $filters['per_page'] Количество элементов на странице (по умолчанию 15)
     * 
     * @return LengthAwarePaginator Пагинированный список упражнений
     * 
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Если упражнение не найдено
     */
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $filter = new ExerciseFilter($filters);
        $query = Exercise::query()->with('muscleGroup');
        
        // Если user_id не передан, возвращаем пустой результат для безопасности
        if (!isset($filters['user_id']) || $filters['user_id'] === null) {
            return new LengthAwarePaginator([], 0, 15, 1);
        }
        
        $filter->apply($query);

        return $this->applyPagination($query, $filters);
    }

    /**
     * Получить упражнение по ID
     * 
     * @param int $id ID упражнения
     * @param int|null $userId ID пользователя для проверки доступа (опционально)
     * 
     * @return Exercise|null Модель упражнения с загруженной связью группы мышц или null если не найдено
     */
    public function getById(int $id, ?int $userId = null): ?Exercise
    {
        $query = Exercise::query()->with('muscleGroup');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->find($id);
    }

    /**
     * Создать новое упражнение
     * 
     * @param array $data Данные для создания упражнения
     * @param string $data['name'] Название упражнения
     * @param string|null $data['description'] Описание упражнения
     * @param int $data['muscle_group_id'] ID группы мышц
     * @param int $data['user_id'] ID пользователя
     * 
     * @return Exercise Созданная модель упражнения
     * 
     * @throws \Illuminate\Database\QueryException При ошибке создания записи
     */
    public function create(array $data): Exercise
    {
        return Exercise::create($data);
    }

    /**
     * Обновить упражнение по ID
     * 
     * @param int $id ID упражнения
     * @param array $data Данные для обновления
     * @param int|null $userId ID пользователя для проверки доступа (опционально)
     * 
     * @return Exercise|null Обновленная модель упражнения с загруженной связью группы мышц или null если не найдено
     * 
     * @throws \Illuminate\Database\QueryException При ошибке обновления записи
     */
    public function update(int $id, array $data, ?int $userId = null): ?Exercise
    {
        $query = Exercise::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $exercise = $query->find($id);
        
        if (!$exercise) {
            return null;
        }
        
        $exercise->update($data);
        
        return $exercise->fresh(['muscleGroup']);
    }

    /**
     * Удалить упражнение по ID
     * 
     * @param int $id ID упражнения
     * @param int|null $userId ID пользователя для проверки доступа (опционально)
     * 
     * @return bool True если упражнение успешно удалено, false если не найдено
     */
    public function delete(int $id, ?int $userId = null): bool
    {
        $query = Exercise::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $exercise = $query->find($id);
        
        if (!$exercise) {
            return false;
        }
        
        return $exercise->delete();
    }

    /**
     * Установить базовый пакет упражнений для пользователя
     * 
     * Создаёт набор универсальных упражнений из BaseExercisePack.
     * Каждое созданное упражнение маркируется source=ExerciseSource::BASE_PACK для трекинга.
     * 
     * Дедупликация:
     *   — Если у пользователя уже есть упражнение с тем же (name + muscle_group_id), оно пропускается.
     *   — Повторный вызов безопасен (идемпотентность).
     * 
     * @param int $userId ID пользователя
     * 
     * @return array{created: int, skipped: int, total: int} Статистика установки
     * 
     * @throws \RuntimeException Если группы мышц не найдены в БД
     */
    public function installBasePack(int $userId): array
    {
        $exercises = BaseExercisePack::getExercises();

        // Загружаем все группы мышц одним запросом
        $muscleGroups = MuscleGroup::pluck('id', 'name');

        if ($muscleGroups->isEmpty()) {
            throw new \RuntimeException('Группы мышц не найдены в базе данных. Выполните сидер MuscleGroupSeeder.');
        }

        // Загружаем существующие упражнения пользователя (name + muscle_group_id)
        $existingExercises = Exercise::where('user_id', $userId)
            ->select('name', 'muscle_group_id')
            ->get()
            ->map(fn(Exercise $e): string => $e->name . '|' . $e->muscle_group_id)
            ->toArray();

        $existingSet = array_flip($existingExercises);

        $toInsert = [];
        $skipped = 0;
        $now = now();

        foreach ($exercises as $exerciseData) {
            $muscleGroupId = $muscleGroups->get($exerciseData['muscle_group']);

            if ($muscleGroupId === null) {
                $skipped++;
                continue;
            }

            $key = $exerciseData['name'] . '|' . $muscleGroupId;

            if (isset($existingSet[$key])) {
                $skipped++;
                continue;
            }

            $toInsert[] = [
                'user_id' => $userId,
                'name' => $exerciseData['name'],
                'description' => $exerciseData['description'],
                'muscle_group_id' => $muscleGroupId,
                'is_active' => true,
                'source' => ExerciseSource::BASE_PACK->value,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Помечаем как существующее, чтобы не дублировать внутри пакета
            $existingSet[$key] = true;
        }

        $created = 0;

        if (!empty($toInsert)) {
            // Вставляем пачками по 50 для оптимизации
            foreach (array_chunk($toInsert, 50) as $chunk) {
                DB::table('exercises')->insert($chunk);
                $created += count($chunk);
            }
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'total' => count($exercises),
        ];
    }

    /**
     * Откатить установку базового пакета упражнений
     * 
     * Удаляет все упражнения пользователя с source=ExerciseSource::BASE_PACK.
     * Работает корректно даже если пользователь переименовал упражнения — 
     * привязка по колонке source, а не по названию.
     * 
     * Упражнения, задействованные в тренировках (workout_sets), НЕ удаляются — 
     * они помечаются как неактивные (is_active=false) и очищается source.
     * 
     * @param int $userId ID пользователя
     * 
     * @return array{deleted: int, deactivated: int, total_found: int} Статистика отката
     */
    public function uninstallBasePack(int $userId): array
    {
        $basePackExercises = Exercise::where('user_id', $userId)
            ->where('source', ExerciseSource::BASE_PACK)
            ->get();

        if ($basePackExercises->isEmpty()) {
            return [
                'deleted' => 0,
                'deactivated' => 0,
                'total_found' => 0,
            ];
        }

        $deleted = 0;
        $deactivated = 0;

        DB::beginTransaction();

        try {
            foreach ($basePackExercises as $exercise) {
                // Проверяем, используется ли упражнение в подходах тренировок
                $hasWorkoutSets = DB::table('workout_sets')
                    ->where('exercise_id', $exercise->id)
                    ->exists();

                if ($hasWorkoutSets) {
                    // Не удаляем, а деактивируем и убираем маркер source
                    $exercise->update([
                        'is_active' => false,
                        'source' => null,
                    ]);
                    $deactivated++;
                } else {
                    // Удаляем связи с планами и само упражнение
                    DB::table('plan_exercises')
                        ->where('exercise_id', $exercise->id)
                        ->delete();

                    $exercise->delete();
                    $deleted++;
                }
            }

            DB::commit();

            return [
                'deleted' => $deleted,
                'deactivated' => $deactivated,
                'total_found' => $basePackExercises->count(),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Проверить статус установки базового пакета упражнений
     * 
     * Возвращает подробную информацию: количество установленных, общее в пакете,
     * и флаг installed (true если упражнения с source=ExerciseSource::BASE_PACK есть).
     * 
     * @param int $userId ID пользователя
     * 
     * @return array{installed: bool, installed_count: int, pack_size: int}
     */
    public function getBasePackStatus(int $userId): array
    {
        $installedCount = Exercise::where('user_id', $userId)
            ->where('source', ExerciseSource::BASE_PACK)
            ->count();

        return [
            'installed' => $installedCount > 0,
            'installed_count' => $installedCount,
            'pack_size' => BaseExercisePack::count(),
        ];
    }
}
