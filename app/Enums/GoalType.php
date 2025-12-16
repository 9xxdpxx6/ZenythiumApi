<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Тип цели
 */
enum GoalType: string
{
    // Количество тренировок
    case TOTAL_WORKOUTS = 'total_workouts';
    case COMPLETED_WORKOUTS = 'completed_workouts';

    // Вес тела
    case TARGET_WEIGHT = 'target_weight';
    case WEIGHT_LOSS = 'weight_loss';
    case WEIGHT_GAIN = 'weight_gain';

    // Объем тренировок
    case TOTAL_VOLUME = 'total_volume';
    case WEEKLY_VOLUME = 'weekly_volume';

    // Время тренировок
    case TOTAL_TRAINING_TIME = 'total_training_time';
    case WEEKLY_TRAINING_TIME = 'weekly_training_time';

    // Частота и регулярность
    case TRAINING_FREQUENCY = 'training_frequency';
    case TRAINING_STREAK = 'training_streak';

    // Конкретные упражнения
    case EXERCISE_MAX_WEIGHT = 'exercise_max_weight';
    case EXERCISE_MAX_REPS = 'exercise_max_reps';
    case EXERCISE_VOLUME = 'exercise_volume';

    /**
     * Получить все значения enum
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Проверить, требует ли тип цели упражнение
     *
     * @return bool
     */
    public function requiresExercise(): bool
    {
        return in_array($this, [
            self::EXERCISE_MAX_WEIGHT,
            self::EXERCISE_MAX_REPS,
            self::EXERCISE_VOLUME,
        ], true);
    }

    /**
     * Получить человекочитаемое название типа цели
     *
     * @return string
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::TOTAL_WORKOUTS => 'Всего тренировок',
            self::COMPLETED_WORKOUTS => 'Завершенных тренировок',
            self::TARGET_WEIGHT => 'Целевой вес',
            self::WEIGHT_LOSS => 'Похудение/сушка',
            self::WEIGHT_GAIN => 'Массанабор',
            self::TOTAL_VOLUME => 'Общий объем тренировок',
            self::WEEKLY_VOLUME => 'Недельный объем тренировок',
            self::TOTAL_TRAINING_TIME => 'Общее время тренировок',
            self::WEEKLY_TRAINING_TIME => 'Недельное время тренировок',
            self::TRAINING_FREQUENCY => 'Частота тренировок',
            self::TRAINING_STREAK => 'Серия тренировок подряд',
            self::EXERCISE_MAX_WEIGHT => 'Максимальный вес в упражнении',
            self::EXERCISE_MAX_REPS => 'Максимальное количество повторений',
            self::EXERCISE_VOLUME => 'Объем упражнения',
        };
    }
}




