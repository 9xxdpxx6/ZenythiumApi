<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TrainingProgram;
use App\Models\TrainingProgramInstallation;

final class TrainingProgramExportService
{
    public function __construct(
        private readonly TrainingProgramService $trainingProgramService
    ) {}

    /**
     * Получить подробные данные программы для экспорта
     * 
     * @param TrainingProgram $program Программа для экспорта
     * 
     * @return array Массив с подробными данными программы
     */
    public function getDetailedData(TrainingProgram $program): array
    {
        $program->load('author');

        $structure = $this->trainingProgramService->getProgramStructure($program->id);
        
        $installationsCount = $program->installs()->count();

        return [
            'id' => $program->id,
            'name' => $program->name,
            'description' => $program->description,
            'author' => $program->author ? [
                'id' => $program->author->id,
                'name' => $program->author->name,
            ] : null,
            'duration_weeks' => $program->duration_weeks,
            'is_active' => $program->is_active,
            'installations_count' => $installationsCount,
            'structure' => $structure,
            'cycles_count' => $structure && isset($structure['cycles']) ? count($structure['cycles']) : 0,
            'plans_count' => $this->countPlans($structure),
            'exercises_count' => $this->countExercises($structure),
            'created_at' => $program->created_at?->toISOString(),
            'updated_at' => $program->updated_at?->toISOString(),
        ];
    }

    /**
     * Получить структурные данные программы для экспорта (только структура без статистики)
     * 
     * @param TrainingProgram $program Программа для экспорта
     * 
     * @return array Массив со структурными данными программы
     */
    public function getStructureData(TrainingProgram $program): array
    {
        $structure = $this->trainingProgramService->getProgramStructure($program->id);

        return [
            'name' => $program->name,
            'description' => $program->description,
            'duration_weeks' => $program->duration_weeks,
            'structure' => $structure,
        ];
    }

    /**
     * Подсчитать количество планов в структуре программы
     * 
     * @param array|null $structure Структура программы
     * 
     * @return int Количество планов
     */
    private function countPlans(?array $structure): int
    {
        if (!$structure || !isset($structure['cycles'])) {
            return 0;
        }

        $count = 0;
        foreach ($structure['cycles'] as $cycle) {
            if (isset($cycle['plans'])) {
                $count += count($cycle['plans']);
            }
        }

        return $count;
    }

    /**
     * Подсчитать количество упражнений в структуре программы
     * 
     * @param array|null $structure Структура программы
     * 
     * @return int Количество упражнений
     */
    private function countExercises(?array $structure): int
    {
        if (!$structure || !isset($structure['cycles'])) {
            return 0;
        }

        $count = 0;
        foreach ($structure['cycles'] as $cycle) {
            if (isset($cycle['plans'])) {
                foreach ($cycle['plans'] as $plan) {
                    if (isset($plan['exercises'])) {
                        $count += count($plan['exercises']);
                    }
                }
            }
        }

        return $count;
    }
}

