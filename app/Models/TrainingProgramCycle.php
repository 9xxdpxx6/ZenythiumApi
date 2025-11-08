<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Модель цикла программы тренировок (шаблон)
 * 
 * @property int $id ID цикла
 * @property int $training_program_id ID программы
 * @property string $name Название цикла
 * @property int $order Порядок сортировки
 * @property \Carbon\Carbon $created_at Время создания
 * @property \Carbon\Carbon $updated_at Время обновления
 * 
 * @property-read TrainingProgram $trainingProgram Программа тренировок
 * @property-read \Illuminate\Database\Eloquent\Collection|TrainingProgramPlan[] $plans Планы цикла
 */
final class TrainingProgramCycle extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_program_id',
        'name',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    /**
     * Получить программу тренировок
     * 
     * @return BelongsTo Связь с моделью TrainingProgram
     */
    public function trainingProgram(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class);
    }

    /**
     * Получить планы цикла
     * 
     * @return HasMany Связь с коллекцией TrainingProgramPlan
     */
    public function plans(): HasMany
    {
        return $this->hasMany(TrainingProgramPlan::class)->orderBy('order');
    }
}
