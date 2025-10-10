<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Модель упражнения
 * 
 * Представляет упражнение, созданное пользователем и привязанное к группе мышц.
 * Содержит информацию о названии, описании и активности упражнения.
 * 
 * @property int $id ID упражнения
 * @property string $name Название упражнения
 * @property string|null $description Описание упражнения
 * @property int $muscle_group_id ID группы мышц
 * @property int $user_id ID пользователя-создателя
 * @property bool $is_active Активность упражнения
 * @property \Carbon\Carbon $created_at Время создания записи
 * @property \Carbon\Carbon $updated_at Время последнего обновления записи
 * 
 * @property-read \App\Models\MuscleGroup $muscleGroup Группа мышц
 * @property-read \App\Models\User $user Пользователь-создатель
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PlanExercise[] $planExercises Упражнения в планах
 */
final class Exercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'muscle_group_id',
        'user_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Получить группу мышц упражнения
     * 
     * @return BelongsTo Связь с моделью MuscleGroup
     */
    public function muscleGroup(): BelongsTo
    {
        return $this->belongsTo(MuscleGroup::class);
    }

    /**
     * Получить пользователя-создателя упражнения
     * 
     * @return BelongsTo Связь с моделью User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Получить упражнения в планах тренировок
     * 
     * @return HasMany Связь с коллекцией PlanExercise
     */
    public function planExercises(): HasMany
    {
        return $this->hasMany(PlanExercise::class);
    }
}
