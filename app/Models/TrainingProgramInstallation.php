<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Модель установки программы тренировок
 * 
 * Представляет установку программы тренировок для конкретного пользователя.
 * 
 * @property int $id ID установки
 * @property int $user_id ID пользователя
 * @property int $training_program_id ID программы
 * @property int|null $installed_cycle_id ID созданного цикла
 * @property \Carbon\Carbon $created_at Время установки (создания записи)
 * @property \Carbon\Carbon $updated_at Время последнего обновления записи
 * 
 * @property-read \App\Models\User $user Пользователь
 * @property-read \App\Models\TrainingProgram $trainingProgram Программа
 * @property-read \App\Models\Cycle|null $installedCycle Созданный цикл
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\TrainingProgramInstallationItem[] $items Элементы установки
 */
final class TrainingProgramInstallation extends Model
{
    use HasFactory;

    protected $table = 'training_program_installations';

    protected $fillable = [
        'user_id',
        'training_program_id',
        'installed_cycle_id',
    ];

    /**
     * Получить пользователя
     * 
     * @return BelongsTo Связь с моделью User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

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
     * Получить созданный цикл
     * 
     * @return BelongsTo Связь с моделью Cycle
     */
    public function installedCycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class, 'installed_cycle_id');
    }

    /**
     * Получить элементы установки
     * 
     * @return HasMany Связь с коллекцией TrainingProgramInstallationItem
     */
    public function items(): HasMany
    {
        return $this->hasMany(TrainingProgramInstallationItem::class);
    }
}

