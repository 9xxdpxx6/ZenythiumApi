<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Exercise;
use App\Filters\ExerciseFilter;
use App\Traits\HasPagination;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

final class ExerciseService
{
    use HasPagination;
    /**
     * Получить все упражнения с фильтрацией и пагинацией
     * 
     * @param array $filters Массив фильтров для поиска упражнений
     * @param int|null $filters['user_id'] ID пользователя (обязательно для безопасности)
     * @param int|null $filters['muscle_group_id'] ID группы мышц
     * @param string|null $filters['name'] Название упражнения (поиск по частичному совпадению)
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
}
