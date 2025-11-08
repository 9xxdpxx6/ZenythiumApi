<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Модель плана программы тренировок (шаблон)
 * 
 * @property int $id ID плана
 * @property int $training_program_cycle_id ID цикла программы
 * @property string $name Название плана
 * @property int $order Порядок сортировки
 * @property \Carbon\Carbon $created_at Время создания
 * @property \Carbon\Carbon $updated_at Время обновления
 * 
 * @property-read TrainingProgramCycle $cycle Цикл программы
 * @property-read \Illuminate\Database\Eloquent\Collection|TrainingProgramExercise[] $exercises Упражнения плана
 */
final class TrainingProgramPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_program_cycle_id',
        'name',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    /**
     * Получить цикл программы
     * 
     * @return BelongsTo Связь с моделью TrainingProgramCycle
     */
    public function cycle(): BelongsTo
    {
        return $this->belongsTo(TrainingProgramCycle::class, 'training_program_cycle_id');
    }

    /**
     * Получить упражнения плана
     * 
     * @return HasMany Связь с коллекцией TrainingProgramExercise
     */
    public function exercises(): HasMany
    {
        return $this->hasMany(TrainingProgramExercise::class)->orderBy('order');
    }
}
