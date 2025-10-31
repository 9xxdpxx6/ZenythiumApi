<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TrainingProgramInstallationItemType;
use App\Models\Cycle;
use App\Models\Exercise;
use App\Models\Plan;
use App\Models\PlanExercise;
use App\Models\TrainingProgram;
use App\Models\TrainingProgramInstallation;
use App\Models\TrainingProgramInstallationItem;
use App\Filters\TrainingProgramFilter;
use App\Traits\HasPagination;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class TrainingProgramService
{
    use HasPagination;

    /**
     * Получить все программы тренировок с фильтрацией и пагинацией
     * 
     * @param array $filters Массив фильтров для поиска программ
     * @param int $filters['page'] Номер страницы (по умолчанию 1)
     * @param int $filters['per_page'] Количество элементов на странице (по умолчанию 15)
     * @param string|null $filters['search'] Поиск по названию и описанию
     * @param bool|null $filters['is_active'] Фильтр по активности
     * 
     * @return LengthAwarePaginator Пагинированный список программ
     */
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $filter = new TrainingProgramFilter($filters);
        $query = TrainingProgram::query()->with('author');
        
        $filter->apply($query);

        return $this->applyPagination($query, $filters);
    }

    /**
     * Получить программу тренировок по ID
     * 
     * @param int $id ID программы
     * 
     * @return TrainingProgram|null Модель программы или null если не найдена
     */
    public function getById(int $id): ?TrainingProgram
    {
        return TrainingProgram::query()
            ->with('author')
            ->find($id);
    }

    /**
     * Установить программу тренировок для пользователя
     * 
     * @param int $programId ID программы
     * @param int $userId ID пользователя
     * 
     * @return array Массив с информацией о созданных элементах:
     *   - install: TrainingProgramInstallation
     *   - cycle: Cycle|null
     *   - plans: Collection<Plan>
     *   - exercises: Collection<Exercise>
     * 
     * @throws \Exception При ошибке установки
     */
    public function install(int $programId, int $userId): array
    {
        $program = $this->getById($programId);
        
        if (!$program) {
            throw new \Exception('Программа тренировок не найдена');
        }

        if (!$program->is_active) {
            throw new \Exception('Программа неактивна');
        }

        // Проверяем, не установлена ли программа уже
        $existingInstall = TrainingProgramInstallation::where('user_id', $userId)
            ->where('training_program_id', $programId)
            ->first();

        if ($existingInstall) {
            throw new \Exception('Программа уже установлена');
        }

        // Получаем данные программы из сидера
        $programData = $this->getProgramData($programId);
        
        if (!$programData) {
            throw new \Exception('Данные программы не найдены');
        }

        DB::beginTransaction();
        
        try {
            // Создаем запись об установке
            $install = TrainingProgramInstallation::create([
                'user_id' => $userId,
                'training_program_id' => $programId,
            ]);

            $createdExercises = collect();
            $createdPlans = collect();
            $createdCycle = null;

            // Создаем цикл
            if (!empty($programData['cycles'])) {
                $cycleData = $programData['cycles'][0]; // Пока поддерживаем один цикл
                $cycleName = $this->resolveUniqueName(
                    Cycle::class,
                    $cycleData['name'],
                    $userId
                );

                $createdCycle = Cycle::create([
                    'user_id' => $userId,
                    'name' => $cycleName,
                    'start_date' => now()->toDateString(),
                    'end_date' => null,
                    'weeks' => $program->duration_weeks,
                ]);

                $this->saveInstallItem($install, TrainingProgramInstallationItemType::CYCLE, $createdCycle->id);

                // Создаем планы в цикле
                foreach ($cycleData['plans'] ?? [] as $planIndex => $planData) {
                    $planName = $this->resolveUniqueName(
                        Plan::class,
                        $planData['name'],
                        $userId
                    );

                    $plan = Plan::create([
                        'user_id' => $userId,
                        'cycle_id' => $createdCycle->id,
                        'name' => $planName,
                        'order' => $planIndex + 1,
                        'is_active' => true,
                    ]);

                    $this->saveInstallItem($install, TrainingProgramInstallationItemType::PLAN, $plan->id);
                    $createdPlans->push($plan);

                    // Создаем упражнения для плана
                    foreach ($planData['exercises'] ?? [] as $exerciseIndex => $exerciseData) {
                        $exercise = $this->resolveOrCreateExercise(
                            $exerciseData,
                            $userId,
                            $install
                        );

                        // Создаем связь упражнения с планом
                        PlanExercise::create([
                            'plan_id' => $plan->id,
                            'exercise_id' => $exercise->id,
                            'order' => $exerciseIndex + 1,
                        ]);

                        if (!$createdExercises->contains('id', $exercise->id)) {
                            $createdExercises->push($exercise);
                        }
                    }
                }
            }

            // Обновляем installed_cycle_id в install
            $install->update(['installed_cycle_id' => $createdCycle?->id]);

            DB::commit();

            return [
                'install' => $install,
                'cycle' => $createdCycle,
                'plans' => $createdPlans,
                'exercises' => $createdExercises,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка установки программы тренировок', [
                'program_id' => $programId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Отменить установку программы тренировок
     * 
     * @param int $installId ID установки
     * @param int $userId ID пользователя для проверки доступа
     * 
     * @return bool True если установка успешно отменена
     * 
     * @throws \Exception При ошибке отмены
     */
    public function uninstall(int $installId, int $userId): bool
    {
        $install = TrainingProgramInstallation::where('id', $installId)
            ->where('user_id', $userId)
            ->with('items')
            ->first();

        if (!$install) {
            throw new \Exception('Установка программы не найдена');
        }

        // Проверяем, есть ли тренировки в цикле перед удалением
        $items = $install->items;
        foreach ($items->where('item_type', TrainingProgramInstallationItemType::CYCLE->value) as $item) {
            $cycle = Cycle::find($item->item_id);
            if ($cycle && $cycle->user_id === $userId) {
                // Проверяем наличие тренировок в цикле
                $workoutsCount = $cycle->workouts()->count();
                if ($workoutsCount > 0) {
                    throw new \InvalidArgumentException('Невозможно удалить программу: в цикле "' . $cycle->name . '" есть ' . $workoutsCount . ' тренировок. Сначала удалите все тренировки.');
                }
            }
        }

        DB::beginTransaction();

        try {
            // Удаляем элементы в правильном порядке: планы -> циклы -> упражнения
            // Сначала удаляем планы (и их связи с упражнениями)
            foreach ($items->where('item_type', TrainingProgramInstallationItemType::PLAN->value) as $item) {
                $plan = Plan::find($item->item_id);
                if ($plan && $plan->user_id === $userId) {
                    // Удаляем связи упражнений с планом
                    PlanExercise::where('plan_id', $plan->id)->delete();
                    // Удаляем план
                    $plan->delete();
                }
            }

            // Затем удаляем циклы (проверка уже выполнена выше)
            foreach ($items->where('item_type', TrainingProgramInstallationItemType::CYCLE->value) as $item) {
                $cycle = Cycle::find($item->item_id);
                if ($cycle && $cycle->user_id === $userId) {
                    $cycle->delete();
                }
            }

            // Наконец, удаляем упражнения (только те, что были созданы при установке)
            foreach ($items->where('item_type', TrainingProgramInstallationItemType::EXERCISE->value) as $item) {
                $exercise = Exercise::find($item->item_id);
                if ($exercise && $exercise->user_id === $userId) {
                    $exercise->delete();
                }
            }

            // Удаляем саму установку (это каскадно удалит все items)
            $install->delete();

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка отмены установки программы', [
                'install_id' => $installId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Разрешить уникальное название для сущности
     * 
     * @param string $modelClass Класс модели
     * @param string $baseName Базовое название
     * @param int $userId ID пользователя
     * 
     * @return string Уникальное название
     */
    private function resolveUniqueName(string $modelClass, string $baseName, int $userId): string
    {
        $name = $baseName;
        $counter = 1;

        while ($modelClass::where('user_id', $userId)
            ->where('name', $name)
            ->exists()) {
            $name = $baseName . ' ' . $counter;
            $counter++;
        }

        return $name;
    }

    /**
     * Найти существующее упражнение или создать новое
     * 
     * @param array $exerciseData Данные упражнения из программы
     * @param int $userId ID пользователя
     * @param TrainingProgramInstallation $install Запись установки
     * 
     * @return Exercise Упражнение (существующее или созданное)
     */
    private function resolveOrCreateExercise(
        array $exerciseData,
        int $userId,
        TrainingProgramInstallation $install
    ): Exercise {
        $name = $exerciseData['name'];
        $muscleGroupId = $exerciseData['muscle_group_id'] ?? null;
        $description = $exerciseData['description'] ?? null;

        // Ищем существующее упражнение по name + muscle_group_id + user_id
        $existingExercise = Exercise::where('user_id', $userId)
            ->where('name', $name)
            ->where('muscle_group_id', $muscleGroupId)
            ->first();

        if ($existingExercise) {
            // Используем существующее упражнение
            return $existingExercise;
        }

        // Если упражнение с таким названием есть, но другая группа мышц
        $existingWithDifferentGroup = Exercise::where('user_id', $userId)
            ->where('name', $name)
            ->where('muscle_group_id', '!=', $muscleGroupId)
            ->first();

        if ($existingWithDifferentGroup && $muscleGroupId) {
            // Получаем название группы мышц
            $muscleGroup = \App\Models\MuscleGroup::find($muscleGroupId);
            $groupName = $muscleGroup ? $muscleGroup->name : '';

            // Добавляем название группы к названию упражнения
            $name = $name . ' (' . $groupName . ')';
        }

        // Создаем новое упражнение
        $exercise = Exercise::create([
            'user_id' => $userId,
            'name' => $name,
            'description' => $description,
            'muscle_group_id' => $muscleGroupId,
            'is_active' => true,
        ]);

        // Сохраняем в install_items
        $this->saveInstallItem($install, TrainingProgramInstallationItemType::EXERCISE, $exercise->id);

        return $exercise;
    }

    /**
     * Сохранить элемент установки
     * 
     * @param TrainingProgramInstallation $install Установка программы
     * @param TrainingProgramInstallationItemType $itemType Тип элемента
     * @param int $itemId ID элемента
     * 
     * @return void
     */
    private function saveInstallItem(
        TrainingProgramInstallation $install,
        TrainingProgramInstallationItemType $itemType,
        int $itemId
    ): void {
        TrainingProgramInstallationItem::create([
            'training_program_installation_id' => $install->id,
            'item_type' => $itemType,
            'item_id' => $itemId,
        ]);
    }

    /**
     * Получить структуру программы из сидера для детального просмотра
     * 
     * @param int $programId ID программы
     * 
     * @return array|null Структура программы (cycles, plans, exercises) или null
     */
    public function getProgramStructure(int $programId): ?array
    {
        return $this->getProgramData($programId);
    }

    /**
     * Получить данные программы из сидера
     * 
     * В реальной реализации это должно быть более гибко,
     * возможно через JSON файлы или другую структуру данных.
     * 
     * @param int $programId ID программы
     * 
     * @return array|null Данные программы или null
     */
    private function getProgramData(int $programId): ?array
    {
        $program = $this->getById($programId);
        
        if (!$program) {
            return null;
        }

        // Получаем список всех классов программ из сидера
        $programClasses = $this->getAvailableProgramClasses();

        // Ищем класс программы по названию
        foreach ($programClasses as $programClass) {
            if (!class_exists($programClass)) {
                continue;
            }

            $programInstance = new $programClass();

            if (!($programInstance instanceof \Database\Seeders\TrainingPrograms\TrainingProgramDataInterface)) {
                continue;
            }

            // Проверяем, соответствует ли название программы
            $className = class_basename($programClass);
            $programNameFromClass = $this->extractProgramName($className);
            
            // Сравниваем названия (можно улучшить сравнение)
            if ($this->namesMatch($program->name, $programNameFromClass)) {
                return $programInstance->getData();
            }
        }

        return null;
    }

    /**
     * Получить список доступных классов программ
     * 
     * @return array Массив имен классов
     */
    private function getAvailableProgramClasses(): array
    {
        // Используем те же классы, что и в TrainingProgramSeeder
        return [
            \Database\Seeders\TrainingPrograms\BeginnerProgram::class,
        ];
    }

    /**
     * Извлечь название программы из имени класса
     */
    private function extractProgramName(string $className): string
    {
        $name = str_replace('Program', '', $className);
        $name = preg_replace('/([a-z])([A-Z])/', '$1 $2', $name);
        return trim($name);
    }

    /**
     * Проверить, совпадают ли названия программ
     */
    private function namesMatch(string $name1, string $name2): bool
    {
        // Простое сравнение с приведением к нижнему регистру и удалением пробелов
        return mb_strtolower(trim($name1)) === mb_strtolower(trim($name2));
    }
}

