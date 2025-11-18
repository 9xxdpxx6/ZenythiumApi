<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\TrainingProgram;
use App\Models\TrainingProgramCycle;
use App\Models\TrainingProgramPlan;
use App\Models\TrainingProgramExercise;
use Database\Seeders\TrainingPrograms\TrainingProgramDataInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

final class TrainingProgramSeeder extends Seeder
{
    /**
     * Заполнение базы данных программами тренировок
     * 
     * Автоматически находит все классы программ в директории TrainingPrograms,
     * которые реализуют TrainingProgramDataInterface, и переносит их структуру
     * (cycles, plans, exercises) в таблицы БД.
     * 
     * Структура программ хранится в БД, а не в PHP классах, что позволяет
     * изменять программы без изменения кода и создавать их через API.
     */
    public function run(): void
    {
        $programsPath = database_path('seeders/TrainingPrograms');
        
        if (!File::exists($programsPath)) {
            $this->command->warn("Директория {$programsPath} не найдена");
            return;
        }

        $files = File::files($programsPath);

        foreach ($files as $file) {
            $filename = $file->getFilenameWithoutExtension();
            
            // Пропускаем интерфейс
            if ($filename === 'TrainingProgramDataInterface') {
                continue;
            }

            // Пытаемся найти имя класса в файле, так как оно может не совпадать с именем файла
            $className = $this->findClassNameInFile($file->getPathname());
            
            if (!$className) {
                $this->command->warn("Не удалось найти класс в файле {$filename}");
                continue;
            }

            if (!class_exists($className)) {
                // Загружаем файл, если класс еще не загружен
                require_once $file->getPathname();
                
                if (!class_exists($className)) {
                    $this->command->warn("Класс {$className} не найден после загрузки файла");
                    continue;
                }
            }

            try {
                $programInstance = new $className();

                if (!($programInstance instanceof TrainingProgramDataInterface)) {
                    $this->command->warn("Класс {$className} не реализует TrainingProgramDataInterface");
                    continue;
                }

                // Получаем данные программы
                $programData = $programInstance->getData();
                
                // Извлекаем название и описание из данных программы
                $programName = $programData['name'] ?? $this->extractProgramName($filename);
                $programDescription = $programData['description'] ?? null;
                
                // Вычисляем продолжительность
                $weeks = $this->calculateDurationWeeks($programData);

                // Создаем или обновляем запись программы
                $program = TrainingProgram::updateOrCreate(
                    ['name' => $programName],
                    [
                        'description' => $programDescription,
                        'author_id' => null,
                        'duration_weeks' => $weeks,
                        'is_active' => true,
                    ]
                );

                // Сохраняем структуру программы в БД
                $this->saveProgramStructure($program, $programData);

                $this->command->info("Программа '{$programName}' успешно создана/обновлена (ID: {$program->id})");
            } catch (\Exception $e) {
                $this->command->error("Ошибка при обработке {$className}: " . $e->getMessage());
                $this->command->error($e->getTraceAsString());
            }
        }
    }

    /**
     * Сохранить структуру программы в БД
     * 
     * Переносит структуру программы (cycles, plans, exercises) из PHP класса в таблицы БД.
     * Старая структура удаляется перед сохранением новой.
     * 
     * @param TrainingProgram $program Программа тренировок
     * @param array $programData Данные программы из PHP класса (формат TrainingProgramDataInterface)
     * 
     * @return void
     */
    private function saveProgramStructure(TrainingProgram $program, array $programData): void
    {
        // Удаляем старую структуру программы
        TrainingProgramCycle::where('training_program_id', $program->id)->delete();

        // Сохраняем циклы
        foreach ($programData['cycles'] ?? [] as $cycleIndex => $cycleData) {
            $cycle = TrainingProgramCycle::create([
                'training_program_id' => $program->id,
                'name' => $cycleData['name'] ?? 'Цикл ' . ($cycleIndex + 1),
                'order' => $cycleIndex + 1,
            ]);

            // Сохраняем планы
            foreach ($cycleData['plans'] ?? [] as $planIndex => $planData) {
                $plan = TrainingProgramPlan::create([
                    'training_program_cycle_id' => $cycle->id,
                    'name' => $planData['name'] ?? 'План ' . ($planIndex + 1),
                    'order' => $planIndex + 1,
                ]);

                // Сохраняем упражнения
                foreach ($planData['exercises'] ?? [] as $exerciseIndex => $exerciseData) {
                    TrainingProgramExercise::create([
                        'training_program_plan_id' => $plan->id,
                        'name' => $exerciseData['name'] ?? 'Упражнение ' . ($exerciseIndex + 1),
                        'muscle_group_id' => $exerciseData['muscle_group_id'] ?? null,
                        'description' => $exerciseData['description'] ?? null,
                        'order' => $exerciseIndex + 1,
                    ]);
                }
            }
        }
    }

    /**
     * Найти имя класса в PHP файле
     * 
     * Использует token_get_all для поиска определения класса в файле.
     * 
     * @param string $filepath Путь к файлу
     * @return string|null Полное имя класса (с namespace) или null, если не найдено
     */
    private function findClassNameInFile(string $filepath): ?string
    {
        if (!file_exists($filepath)) {
            return null;
        }

        $content = file_get_contents($filepath);
        $tokens = token_get_all($content);
        
        $namespace = 'Database\\Seeders\\TrainingPrograms';
        $className = null;
        
        foreach ($tokens as $index => $token) {
            // Находим namespace
            if (is_array($token) && $token[0] === T_NAMESPACE) {
                $namespaceTokens = [];
                $i = $index + 2; // Пропускаем T_NAMESPACE и пробел
                while (isset($tokens[$i]) && is_array($tokens[$i]) && in_array($tokens[$i][0], [T_STRING, T_NS_SEPARATOR])) {
                    $namespaceTokens[] = $tokens[$i][1];
                    $i++;
                }
                if (!empty($namespaceTokens)) {
                    $namespace = implode('', $namespaceTokens);
                }
            }
            
            // Находим class
            if (is_array($token) && $token[0] === T_CLASS) {
                // Следующий токен после T_CLASS - это имя класса
                if (isset($tokens[$index + 2]) && is_array($tokens[$index + 2]) && $tokens[$index + 2][0] === T_STRING) {
                    $className = $tokens[$index + 2][1];
                    break;
                }
            }
        }
        
        if ($className === null) {
            return null;
        }
        
        return $namespace . '\\' . $className;
    }

    /**
     * Извлечь название программы из имени класса (fallback)
     */
    private function extractProgramName(string $className): string
    {
        // Убираем "Program" из конца и преобразуем в читаемый формат
        $name = str_replace('Program', '', $className);
        $name = preg_replace('/([a-z])([A-Z])/', '$1 $2', $name);
        return trim($name);
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
