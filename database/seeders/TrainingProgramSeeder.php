<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\TrainingProgram;
use Database\Seeders\TrainingPrograms\BeginnerProgram;
use Database\Seeders\TrainingPrograms\TrainingProgramDataInterface;
use Illuminate\Database\Seeder;

final class TrainingProgramSeeder extends Seeder
{
    /**
     * Список классов программ для загрузки
     * 
     * Добавьте сюда новые классы программ при создании.
     */
    private const PROGRAM_CLASSES = [
        BeginnerProgram::class,
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::PROGRAM_CLASSES as $programClass) {
            try {
                if (!class_exists($programClass)) {
                    $this->command->warn("Класс {$programClass} не найден");
                    continue;
                }

                $programInstance = new $programClass();

                if (!($programInstance instanceof TrainingProgramDataInterface)) {
                    $this->command->warn("Класс {$programClass} не реализует TrainingProgramDataInterface");
                    continue;
                }

                // Получаем название класса для использования как база для названия программы
                $className = class_basename($programClass);
                $programName = $this->extractProgramName($className);

                // Получаем данные программы
                $programData = $programInstance->getData();
                
                // Вычисляем продолжительность (пока просто по количеству планов)
                $weeks = $this->calculateDurationWeeks($programData);

                // Создаем или обновляем запись программы
                $program = TrainingProgram::updateOrCreate(
                    ['name' => $programName],
                    [
                        'description' => $this->extractProgramDescription($className, $programData),
                        'author_id' => null,
                        'duration_weeks' => $weeks,
                        'is_active' => true,
                    ]
                );

                $this->command->info("Программа '{$programName}' успешно создана/обновлена (ID: {$program->id})");
            } catch (\Exception $e) {
                $this->command->error("Ошибка при обработке {$programClass}: " . $e->getMessage());
                $this->command->error($e->getTraceAsString());
            }
        }
    }

    /**
     * Извлечь название программы из имени класса
     */
    private function extractProgramName(string $className): string
    {
        // Убираем "Program" из конца и преобразуем в читаемый формат
        $name = str_replace('Program', '', $className);
        $name = preg_replace('/([a-z])([A-Z])/', '$1 $2', $name);
        return trim($name);
    }

    /**
     * Извлечь описание программы
     */
    private function extractProgramDescription(string $className, array $programData): ?string
    {
        // Можно добавить логику для извлечения описания из данных или класса
        return "Программа тренировок: " . $this->extractProgramName($className);
    }

    /**
     * Вычислить продолжительность программы в неделях
     * 
     * Базовая логика: если в цикле 3 плана, и они повторяются каждую неделю,
     * то для полного цикла нужно минимум 4-6 недель.
     * Для простоты берем количество планов + 2.
     */
    private function calculateDurationWeeks(array $programData): int
    {
        $totalPlans = 0;
        foreach ($programData['cycles'] ?? [] as $cycle) {
            $totalPlans += count($cycle['plans'] ?? []);
        }

        // Минимум 4 недели, максимум 12
        return max(4, min(12, $totalPlans + 2));
    }
}

