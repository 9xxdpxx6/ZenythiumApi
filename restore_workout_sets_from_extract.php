<?php

declare(strict_types=1);

/**
 * PHP СКРИПТ ДЛЯ БЕЗОПАСНОГО ВОССТАНОВЛЕНИЯ workout_sets
 * 
 * Использование:
 * php restore_workout_sets_from_extract.php
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\WorkoutSet;
use App\Models\Workout;
use App\Models\PlanExercise;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// ============================================
// КОНФИГУРАЦИЯ
// ============================================
$userId = 1; // ЗАМЕНИТЕ НА ВАШ USER_ID

// Маппинг старых plan_exercise_id на (plan_id, exercise_id) из бэкапа
$planExercisesMapping = [
    145 => [26, 39], 146 => [26, 2], 147 => [26, 4], 148 => [26, 5], 149 => [26, 6],
    150 => [27, 16], 151 => [27, 29], 152 => [27, 28], 153 => [27, 23], 154 => [27, 24], 155 => [27, 19],
    156 => [28, 38], 157 => [28, 11], 158 => [28, 12], 159 => [28, 10], 160 => [28, 8],
    161 => [29, 16], 162 => [29, 22], 163 => [29, 19], 164 => [29, 17], 165 => [29, 20],
    166 => [30, 39], 167 => [30, 31], 168 => [30, 36], 169 => [30, 35], 170 => [30, 37],
];

// ============================================
// ПАРСИНГ workout_sets_extract.sql
// ============================================
$extractFile = __DIR__ . '/workout_sets_extract.sql';
if (!file_exists($extractFile)) {
    die("Файл {$extractFile} не найден!\n");
}

$content = file_get_contents($extractFile);
preg_match_all('/\((\d+),(\d+),(\d+),([\d.]+),(\d+),\'([^\']+)\',\'([^\']+)\'\)/', $content, $matches, PREG_SET_ORDER);

$backupData = [];
foreach ($matches as $match) {
    $backupData[] = [
        (int)$match[1],      // id
        (int)$match[2],      // workout_id
        (int)$match[3],      // plan_exercise_id (старый)
        (float)$match[4],    // weight
        (int)$match[5],      // reps
        $match[6],           // created_at
        $match[7],           // updated_at
    ];
}

echo "Загружено записей из бэкапа: " . count($backupData) . "\n";

// ============================================
// ВОССТАНОВЛЕНИЕ
// ============================================
DB::beginTransaction();

try {
    $restored = 0;
    $skipped = 0;
    $errors = [];
    $workoutIds = array_unique(array_column($backupData, 1));

    foreach ($backupData as $row) {
        [$id, $workoutId, $oldPlanExerciseId, $weight, $reps, $createdAt, $updatedAt] = $row;

        // Получаем workout и проверяем user_id
        $workout = Workout::with('plan.cycle')->find($workoutId);
        if (!$workout) {
            $skipped++;
            continue;
        }

        if ($workout->plan->cycle->user_id !== $userId || $workout->plan->cycle->end_date !== null) {
            $skipped++;
            continue;
        }

        // Получаем маппинг для старого plan_exercise_id
        if (!isset($planExercisesMapping[$oldPlanExerciseId])) {
            $errors[] = "Не найден маппинг для plan_exercise_id: {$oldPlanExerciseId} (workout_id: {$workoutId})";
            $skipped++;
            continue;
        }

        [$planId, $exerciseId] = $planExercisesMapping[$oldPlanExerciseId];

        // Проверяем, что plan_id совпадает
        if ($workout->plan_id !== $planId) {
            $errors[] = "Несоответствие plan_id для workout_id {$workoutId}: ожидался {$planId}, получен {$workout->plan_id}";
            $skipped++;
            continue;
        }

        // Находим новый plan_exercise_id
        $planExercise = PlanExercise::where('plan_id', $planId)
            ->where('exercise_id', $exerciseId)
            ->first();

        if (!$planExercise) {
            $errors[] = "Не найден plan_exercise для plan_id {$planId}, exercise_id {$exerciseId} (workout_id: {$workoutId})";
            $skipped++;
            continue;
        }

        // Проверяем на дубликаты
        $exists = WorkoutSet::where('workout_id', $workoutId)
            ->where('plan_exercise_id', $planExercise->id)
            ->where('weight', $weight)
            ->where('reps', $reps)
            ->exists();

        if ($exists) {
            $skipped++;
            continue;
        }

        // Создаем workout_set
        WorkoutSet::create([
            'workout_id' => $workoutId,
            'plan_exercise_id' => $planExercise->id,
            'weight' => $weight,
            'reps' => $reps,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ]);

        $restored++;
    }

    echo "\n=== РЕЗУЛЬТАТЫ ===\n";
    echo "Восстановлено: {$restored}\n";
    echo "Пропущено: {$skipped}\n";
    
    if (!empty($errors)) {
        echo "\nОшибки (первые 10):\n";
        foreach (array_slice($errors, 0, 10) as $error) {
            echo "  - {$error}\n";
        }
        if (count($errors) > 10) {
            echo "  ... и еще " . (count($errors) - 10) . " ошибок\n";
        }
    }

    // Статистика по планам
    $stats = DB::table('workout_sets')
        ->join('workouts', 'workout_sets.workout_id', '=', 'workouts.id')
        ->join('plans', 'workouts.plan_id', '=', 'plans.id')
        ->join('cycles', 'plans.cycle_id', '=', 'cycles.id')
        ->where('cycles.user_id', $userId)
        ->whereNull('cycles.end_date')
        ->whereIn('workouts.id', $workoutIds)
        ->selectRaw('plans.name, COUNT(DISTINCT workouts.id) as workouts, COUNT(workout_sets.id) as sets')
        ->groupBy('plans.id', 'plans.name')
        ->get();

    echo "\n=== СТАТИСТИКА ПО ПЛАНАМ ===\n";
    foreach ($stats as $stat) {
        echo "  {$stat->name}: {$stat->workouts} тренировок, {$stat->sets} подходов\n";
    }

    // Подтверждение
    echo "\nПодтвердите восстановление (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);

    if ($line === 'yes') {
        DB::commit();
        echo "\n✅ Данные успешно восстановлены!\n";
    } else {
        DB::rollBack();
        echo "\n❌ Операция отменена.\n";
    }
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ ОШИБКА: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
