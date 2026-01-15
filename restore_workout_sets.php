<?php

declare(strict_types=1);

/**
 * PHP СКРИПТ ДЛЯ БЕЗОПАСНОГО ВОССТАНОВЛЕНИЯ workout_sets
 * 
 * Использование:
 * php restore_workout_sets.php
 * 
 * Или через artisan:
 * php artisan tinker
 * require 'restore_workout_sets.php';
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
$workoutIds = [87, 91, 100, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 113, 114, 115, 116, 117, 118, 119, 120];

// Маппинг старых plan_exercise_id на (plan_id, exercise_id) из бэкапа
$planExercisesMapping = [
    // Plan 26 (Chest): plan_exercise_id 145-149
    145 => [26, 39],
    146 => [26, 2],
    147 => [26, 4],
    148 => [26, 5],
    149 => [26, 6],
    // Plan 27 (Arms): plan_exercise_id 150-155
    150 => [27, 16],
    151 => [27, 29],
    152 => [27, 28],
    153 => [27, 23],
    154 => [27, 24],
    155 => [27, 19],
    // Plan 28 (Back): plan_exercise_id 156-160
    156 => [28, 38],
    157 => [28, 11],
    158 => [28, 12],
    159 => [28, 10],
    160 => [28, 8],
    // Plan 29 (Shoulders): plan_exercise_id 161-165
    161 => [29, 16],
    162 => [29, 22],
    163 => [29, 19],
    164 => [29, 17],
    165 => [29, 20],
    // Plan 30 (Legs): plan_exercise_id 166-170
    166 => [30, 39],
    167 => [30, 31],
    168 => [30, 36],
    169 => [30, 35],
    170 => [30, 37],
];

// Данные из workout_sets_extract.sql
$backupData = [
    [853,87,145,50.00,20,'2025-12-01 10:43:19','2025-12-01 10:43:19'],
    [854,87,145,50.00,20,'2025-12-01 10:51:50','2025-12-01 10:51:50'],
    // ... добавьте остальные строки из workout_sets_extract.sql
];

// ============================================
// ВОССТАНОВЛЕНИЕ
// ============================================
DB::beginTransaction();

try {
    $restored = 0;
    $skipped = 0;
    $errors = [];

    foreach ($backupData as $row) {
        [$id, $workoutId, $oldPlanExerciseId, $weight, $reps, $createdAt, $updatedAt] = $row;

        // Проверяем, что workout_id в списке
        if (!in_array($workoutId, $workoutIds, true)) {
            continue;
        }

        // Получаем workout и проверяем user_id
        $workout = Workout::with('plan.cycle')->find($workoutId);
        if (!$workout || $workout->plan->cycle->user_id !== $userId || $workout->plan->cycle->end_date !== null) {
            $skipped++;
            continue;
        }

        // Получаем маппинг для старого plan_exercise_id
        if (!isset($planExercisesMapping[$oldPlanExerciseId])) {
            $errors[] = "Не найден маппинг для plan_exercise_id: {$oldPlanExerciseId}";
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
            $errors[] = "Не найден plan_exercise для plan_id {$planId}, exercise_id {$exerciseId}";
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

    echo "Восстановлено: {$restored}\n";
    echo "Пропущено: {$skipped}\n";
    if (!empty($errors)) {
        echo "Ошибки:\n";
        foreach ($errors as $error) {
            echo "  - {$error}\n";
        }
    }

    // Показываем статистику
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

    echo "\nСтатистика по планам:\n";
    foreach ($stats as $stat) {
        echo "  {$stat->name}: {$stat->workouts} тренировок, {$stat->sets} подходов\n";
    }

    // Спрашиваем подтверждение
    echo "\nПодтвердите восстановление (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);

    if (trim($line) === 'yes') {
        DB::commit();
        echo "Данные успешно восстановлены!\n";
    } else {
        DB::rollBack();
        echo "Операция отменена.\n";
    }
} catch (\Exception $e) {
    DB::rollBack();
    echo "ОШИБКА: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
