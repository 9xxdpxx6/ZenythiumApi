<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель упражнения программы тренировок (шаблон)
 * 
 * @property int $id ID упражнения
 * @property int $training_program_plan_id ID плана программы
 * @property string $name Название упражнения
 * @property int|null $muscle_group_id ID группы мышц
 * @property string|null $description Описание упражнения
 * @property int $order Порядок сортировки
 * @property \Carbon\Carbon $created_at Время создания
 * @property \Carbon\Carbon $updated_at Время обновления
 * 
 * @property-read TrainingProgramPlan $plan План программы
 * @property-read MuscleGroup|null $muscleGroup Группа мышц
 */
final class TrainingProgramExercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_program_plan_id',
        'name',
        'muscle_group_id',
        'description',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    /**
     * Получить план программы
     * 
     * @return BelongsTo Связь с моделью TrainingProgramPlan
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(TrainingProgramPlan::class, 'training_program_plan_id');
    }

    /**
     * Получить группу мышц
     * 
     * @return BelongsTo Связь с моделью MuscleGroup
     */
    public function muscleGroup(): BelongsTo
    {
        return $this->belongsTo(MuscleGroup::class);
    }
}
